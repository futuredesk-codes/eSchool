<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\File;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! Auth::user()->can('leave-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        // Combine all users and sort by first name in ascending order
        $users = $teachers->concat($staff)->sortBy(function ($item) {
            return $item->user->first_name;
        })->values(); // Reset array keys after sorting

        $months = [
            1 => trans('January'),
            2 => trans('February'),
            3 => trans('March'),
            4 => trans('April'),
            5 => trans('May'),
            6 => trans('June'),
            7 => trans('July'),
            8 => trans('August'),
            9 => trans('September'),
            10 => trans('October'),
            11 => trans('November'),
            12 => trans('December'),
        ];

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.index', compact(
            'sessionYears',
            'currentSessionYearId',
            'currentSessionYear',
            'holiday_days',
            'users',
            'months',
            'public_holiday',
            'leaveMaster'
        ));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('leave-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'reason' => 'required',
                'from_date' => 'required',
                'to_date' => 'required|after_or_equal:from_date',
                'leave_master_id' => 'required',
                'type.*' => 'required|array',
                'files.*' => 'nullable',
            ],
            [
                'leave_master_id.required' => trans('Kindly contact the school admin to configure Leave Settings for the current session year'),
                'type.required' => 'Kindly select different dates as the ones mentioned are already allocated as holidays.',
            ]
        );

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            if ($request->type) {
                $data = [
                    'user_id' => Auth::user()->id,
                    'reason' => $request->reason,
                    'from_date' => date('Y-m-d', strtotime($request->from_date)),
                    'to_date' => date('Y-m-d', strtotime($request->to_date)),
                    'leave_master_id' => $request->leave_master_id,
                    'session_year_id' => $session_year_id ?? null,
                    'status' => '0',
                ];

                $leave = Leave::create($data);

                $leaveDetail = [];

                foreach ($request->type as $key => $type) {
                    $leaveDetail = new LeaveDetail;
                    $leaveDetail->leave_id = $leave->id;
                    $leaveDetail->date = date('Y-m-d', strtotime($key));
                    $leaveDetail->type = $type[0];
                    $leaveDetail->save();
                }

                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file_upload) {
                        $file = new File;
                        $file->modal_type = "App\Models\Leave";
                        $file->modal_id = $leave->id;
                        $file->file_name = $file_upload->getClientOriginalName();
                        $file->type = 1;
                        $file->file_url = $file_upload->store('leave', 'public');
                        $file->save();
                    }
                }

                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => trans('please_select_leave_type'),
                ];
            }
        } catch (\Throwable $e) {
            DB::rollback();
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        // dd($request->all());
        if (! Auth::user()->can('leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Validate request payload
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:id,from_date,to_date,reason,status,created_at,updated_at',
            'order' => 'nullable|string|in:ASC,DESC',
            'search' => 'nullable|string|max:255',
            'session_year_id' => 'nullable|integer|exists:session_years,id',
            'filter_upcoming' => 'nullable|string|in:Today,Tomorrow,Upcoming',
            'month_id' => 'nullable|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => trans('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;

        $sql = Leave::with('leave_detail', 'file')->where('user_id', Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                    });
                });
            });

        if ($session_year_id) {
            $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            });
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);

        if ($filter_upcoming) {
            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }
            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                });
            }
            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {

            $operate = '';
            $operate = '<a href='.route('leave-status.update', $row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';

            if ($row->status == 0) {
                $operate .= '<a href='.route('leave.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';
            }
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['reason'] = $row->reason;
            $tempRow['status'] = $row->status;
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['file'] = $row['file'];
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        // dd($bulkData);
        return response()->json($bulkData);
    }

    public function destroy($id)
    {
        if (! Auth::user()->can('leave-delete')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        try {
            $leave = Leave::find($id);

            if ($leave->file) {
                foreach ($leave->file as $file) {
                    if (Storage::disk('public')->exists($file->file_url)) {
                        Storage::disk('public')->delete($file->file_url);
                    }
                }
            }
            $leave->file()->delete();

            $leave->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e,
            ];
        }

        return response()->json($response);
    }

    public function leaveReportIndex()
    {
        if (! Auth::user()->can('leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $users = null;

        if (Auth::user()->can('leave-approve')) {
            $teachers = Teacher::with('user')->get();
            $staff = Staff::with('user')->get();

            // Combine all users and sort by first name in ascending order
            $users = $teachers->concat($staff)->sortBy(function ($item) {
                return $item->user->first_name;
            })->values(); // Reset array keys after sorting
        }

        $sessionYears = SessionYear::all();

        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        return view('leave.leave_details', compact('users', 'sessionYears', 'currentSessionYearId'));
    }

    public function leaveDetails(Request $request)
    {
        if (! Auth::user()->can('leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Validate request payload
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:id,from_date,to_date,reason,status,created_at,updated_at',
            'order' => 'nullable|string|in:ASC,DESC',
            'session_year_id' => 'nullable|integer|exists:session_years,id',
            'staff_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => trans('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $session_year_id = $request->session_year_id;

        $staff_id = $request->staff_id;
        if (! $staff_id) {
            $staff_id = Auth::user()->id;
        }

        $leaveMaster = LeaveMaster::with('session_year')->where('session_year_id', $session_year_id)->first();

        // Get months starting from session year
        $months = [
            1 => trans('January'),
            2 => trans('February'),
            3 => trans('March'),
            4 => trans('April'),
            5 => trans('May'),
            6 => trans('June'),
            7 => trans('July'),
            8 => trans('August'),
            9 => trans('September'),
            10 => trans('October'),
            11 => trans('November'),
            12 => trans('December'),
        ];

        $bulkData = [];
        $bulkData['total'] = count($months);
        $rows = [];
        $no = 1;

        foreach ($months as $key => $month) {
            DB::enableQueryLog();
            $leaves = LeaveDetail::whereMonth('date', $key)
                ->whereHas('leave', function ($q) use ($session_year_id, $staff_id) {
                    $q->where('user_id', $staff_id)->where('status', 1)
                        ->whereHas('leave_master', function ($q) use ($session_year_id) {
                            $q->where('session_year_id', $session_year_id);
                        });
                })->get();

            $allocated = 0;
            $total_used_leaves = 0;

            if ($leaveMaster) {

                $tempRow['allocated'] = $leaveMaster->total_leave;
                $allocated = $leaveMaster->total_leave;
            }
            $tempRow['lwp'] = '-';
            $lwp = 0;
            $total_leaves = $leaves->count();
            // dd($total_leaves);
            $total_used_leaves = $total_leaves - ($leaves->where('type', '!=', 'Full')->count() / 2);

            if ($allocated < $total_used_leaves) {
                $lwp = $total_used_leaves - $allocated;
                $tempRow['lwp'] = $lwp;
                $tempRow['used_cl'] = $total_used_leaves - $lwp;
            } else {
                $tempRow['used_cl'] = '-';
                if ($total_used_leaves) {
                    $tempRow['used_cl'] = $total_used_leaves;
                }
            }
            $tempRow['total'] = '-';
            if ($total_used_leaves) {
                $tempRow['total'] = $total_used_leaves;
            }

            if ($total_used_leaves >= $allocated) {
                $tempRow['remaining_cl'] = '-';
                $tempRow['remaining_total'] = '-';
            } else {
                $tempRow['remaining_cl'] = $total_used_leaves != 0 ? $allocated - $total_used_leaves : '-';
                $tempRow['remaining_total'] = $total_used_leaves != 0 ? $allocated - $total_used_leaves : '-';
            }

            $tempRow['no'] = $no++;
            $tempRow['month'] = $month;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function leaveRequestIndex()
    {
        if (! Auth::user()->can('leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        // Combine all users and sort by first name in ascending order
        $users = $teachers->concat($staff)->sortBy(function ($item) {
            return $item->user->first_name;
        })->values(); // Reset array keys after sorting

        $months = [
            0 => trans('All'),
            1 => trans('January'),
            2 => trans('February'),
            3 => trans('March'),
            4 => trans('April'),
            5 => trans('May'),
            6 => trans('June'),
            7 => trans('July'),
            8 => trans('August'),
            9 => trans('September'),
            10 => trans('October'),
            11 => trans('November'),
            12 => trans('December'),
        ];

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.leave_request', compact('sessionYears', 'currentSessionYearId', 'currentSessionYear', 'holiday_days', 'users', 'months', 'public_holiday'));
    }

    public function leaveRequestShow(Request $request)
    {
        if (! Auth::user()->can('leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Validate request payload
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:id,from_date,to_date,reason,status,created_at,updated_at',
            'order' => 'nullable|string|in:ASC,DESC',
            'search' => 'nullable|string|max:255',
            'session_year_id' => 'nullable|integer|exists:session_years,id',
            'filter_upcoming' => 'nullable|string|in:All,Today,Tomorrow,Upcoming',
            'month_id' => 'nullable|integer|min:1|max:12',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => trans('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;
        $user_id = $request->user_id;

        $sql = Leave::with('leave_detail', 'user', 'file')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%")->orwhereHas('user', function ($q) use ($search) {
                            $q->whereRaw('concat(first_name," ",last_name) like ?', "%$search%");
                        });
                    });
                });
            });

        if ($session_year_id) {
            $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            });
        }

        if ($filter_upcoming != 'All') {
            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }
            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                });
            }
            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        if ($user_id) {
            $sql->where('user_id', $user_id);
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);
        $total = $sql->count();

        $sql->orderBy('id', 'DESC')->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $operate = '<a href='.route('leave-status.update', $row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('leave.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->user->first_name.' '.$row->user->last_name;
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['file'] = $row->file;
            $tempRow['reason'] = $row->reason;
            $tempRow['status'] = $row->status;
            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function leaveStatusUpdate(Request $request)
    {
        if (! Auth::user()->can('leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        try {

            $leave = Leave::findOrFail($request->id);
            // Prevent status changes once finalized (Accepted/Rejected)
            if (in_array((string) $leave->status, ['1', '2'], true)) {
                return response()->json([
                    'error' => true,
                    'message' => trans('leave_status_already_finalized'),
                ]);
            }
            $leave->status = $request->status;
            $leave->save();

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    public function studentLeaveRequestIndex()
    {
        if (! Auth::user()->can('student-leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $class_sections = collect(); // default empty

        // condition for teacher
        if ($teacher = Auth::user()->teacher) {
            $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $currentSessionYearId)
                ->pluck('class_section_id');
            $class_sections = ClassSection::with('class.medium', 'section', 'classTeachers', 'class.streams')
                ->whereIn('id', $class_section_ids)->get();
        } else {
            // condition for super admin
            $class_sections = ClassSection::with('class.medium', 'section', 'classTeachers', 'class.streams')
                ->get();
        }

        $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        $users = $teachers->merge($staff);

        $months = [
            0 => 'All',
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.student_leave_request', compact(
            'sessionYears',
            'currentSessionYearId',
            'currentSessionYear',
            'holiday_days',
            'users',
            'months',
            'public_holiday',
            'class_sections'
        ));
    }

    public function getStudentLeaveClassSections(Request $request)
    {
        $session_year_id = $request->session_year_id;

        if ($teacher = Auth::user()->teacher) {
            $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id');
            $class_sections = ClassSection::with('class.medium', 'section', 'class.streams')
                ->whereIn('id', $class_section_ids)->get();
        } else {
            $class_sections = ClassSection::with('class.medium', 'section', 'class.streams')->get();
        }

        if ($class_sections->isEmpty()) {
            $options = '<option value="">'.trans('no_data_found').'</option>';
        } else {
            $options = '<option value="">All</option>';
            foreach ($class_sections as $class_section) {
                $options .= '<option value="'.$class_section->id.'">'.$class_section->full_name.'</option>';
            }
        }

        return response()->json(['class_sections' => $options]);
    }

    public function studentLeaveRequestList(Request $request)
    {
        if (! Auth::user()->can('student-leave-approve')) {
            return redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]);
        }

        // Validate request payload
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:id,from_date,to_date,reason,status,created_at,updated_at',
            'order' => 'nullable|string|in:ASC,DESC',
            'search' => 'nullable|string|max:255',
            'session_year_id' => 'required|integer|exists:session_years,id',
            'filter_upcoming' => 'nullable|string|in:All,Today,Tomorrow,Upcoming',
            'month_id' => 'nullable|integer|min:1|max:12',
            'class_id' => 'nullable|integer|exists:class_sections,id',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];
        }

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'DESC');
        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;
        $class_section_id = $request->class_id;

        $class_section_ids = collect();
        /*
        |--------------------------------------------------------------------------
        | NEW: Map of class_section_id => [semester_start, semester_end]
        | Used to apply per-class semester date boundaries correctly.
        |--------------------------------------------------------------------------
        */
        $classSemesterMap = collect(); // keyed by class_section_id

        /*
        |--------------------------------------------------------------------------
        | Teacher / Admin Access
        |--------------------------------------------------------------------------
        */
        if ($teacher = Auth::user()->teacher) {

            $classTeacherQuery = ClassTeacher::where('class_teacher_id', $teacher->id);

            if ($session_year_id) {
                $classTeacherQuery->where('session_year_id', $session_year_id);
            }

            if ($class_section_id) {
                $classTeacherQuery->where('class_section_id', $class_section_id);
                $class_section_ids = collect([$class_section_id]);
            } else {
                $class_section_ids = $classTeacherQuery->pluck('class_section_id')->unique();
            }

            /*
            |--------------------------------------------------------------------------
            | Build per-class semester map (only for the accessible class sections)
            |--------------------------------------------------------------------------
            |
            | Instead of a flat list of all semester date ranges, we now build a map:
            |   class_section_id => { start_date, end_date }
            |
            | This lets us filter each leave by whether the student's class section
            | has a matching semester for that leave's from_date — not just any semester.
            |
            */
            $classTeacherRecords = ClassTeacher::where('class_teacher_id', $teacher->id)
                ->whereIn('class_section_id', $class_section_ids)
                ->whereNotNull('semester_id')
                ->with('semester:id,start_date,end_date')  // eager load semester
                ->get(['class_section_id', 'semester_id']);

            foreach ($classTeacherRecords as $record) {
                if ($record->semester) {
                    // If a class section has multiple semesters, store all of them
                    $classSemesterMap->push([
                        'class_section_id' => $record->class_section_id,
                        'start_date' => $record->semester->start_date,
                        'end_date' => $record->semester->end_date,
                    ]);
                }
            }

        } else {
            // Admin: full access, no semester restriction
            $class_section_ids = ClassSection::pluck('id');
        }

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */
        $sql = Leave::with([
            'leave_detail',
            'file',
            'user.student',
            'user.student.studentSessions',
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhere('reason', 'LIKE', "%$search%")
                        ->orWhere('from_date', 'LIKE', "%$search%")
                        ->orWhere('to_date', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw(
                                'concat(first_name, " ", last_name) like ?',
                                ["%$search%"]
                            );
                        });
                });
            })
            ->whereHas('user', function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', 'Student');
                });
            })
            ->whereHas('user.student.studentSessions', function ($q) use ($class_section_ids, $session_year_id) {
                $q->whereIn('class_section_id', $class_section_ids)
                    ->where('session_year_id', $session_year_id);
            });

        /*
        |--------------------------------------------------------------------------
        | Session Year Filter
        |--------------------------------------------------------------------------
        */
        $sql->where('session_year_id', $session_year_id);

        /*
        |--------------------------------------------------------------------------
        | Per-Class Semester Date Range Filter
        |--------------------------------------------------------------------------
        |
        | OLD (broken): applied all semester date ranges as a flat OR, so Class 1's
        | teacher would also see leaves from Class 2's semester dates.
        |
        | NEW (correct): for each leave, check that the student's actual class section
        | has a semester whose date range covers the leave's from_date. This couples
        | the date boundary to the class membership, not just any semester.
        |
        */
        if ($classSemesterMap->isNotEmpty()) {
            $sql->where(function ($query) use ($classSemesterMap, $session_year_id) {
                foreach ($classSemesterMap as $entry) {
                    $query->orWhere(function ($q) use ($entry, $session_year_id) {
                        // Leave's from_date must fall within this semester's range...
                        $q->whereBetween('from_date', [
                            $entry['start_date'],
                            $entry['end_date'],
                        ])
                        // ...AND the student must actually belong to that specific class section.
                            ->whereHas('user.student.studentSessions', function ($ss) use ($entry, $session_year_id) {
                                $ss->where('class_section_id', $entry['class_section_id']);
                                $ss->where('session_year_id', $session_year_id);
                            });
                    });
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Upcoming Filters
        |--------------------------------------------------------------------------
        */
        if ($filter_upcoming != 'All') {

            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }

            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', $tomorrow_date);
                });
            }

            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Month Filter
        |--------------------------------------------------------------------------
        */
        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Class Section Filter
        |--------------------------------------------------------------------------
        */
        if ($class_section_id) {
            $sql->whereHas('user.student.studentSessions', function ($q) use ($class_section_id, $session_year_id) {
                $q->where('class_section_id', $class_section_id)
                    ->where('session_year_id', $session_year_id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Leave Counts
        |--------------------------------------------------------------------------
        */
        $sql->withCount([
            'leave_detail as full_leave' => fn ($q) => $q->where('type', 'Full'),
        ]);

        $sql->withCount([
            'leave_detail as half_leave' => fn ($q) => $q->where('type', '!=', 'Full'),
        ]);

        $total = $sql->count();

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $res = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        /*
        |--------------------------------------------------------------------------
        | Response Formatting
        |--------------------------------------------------------------------------
        */
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {

            $operate = '';

            if ($row->from_date >= Carbon::now()->format('Y-m-d')) {
                $operate .= '<a href="'.route('leave-status.update', $row->id).'"
                class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data"
                data-id="'.$row->id.'"
                title="Edit"
                data-toggle="modal"
                data-target="#editModal">
                    <i class="fa fa-eye"></i>
                </a>&nbsp;&nbsp;';
            }

            $operate .= '<a href="'.route('leave.destroy', $row->id).'"
            class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
            data-id="'.$row->id.'">
                <i class="fa fa-trash"></i>
            </a>';

            $tempRow = [
                'id' => $row->id,
                'no' => $no++,
                'user_id' => $row->user->id,
                'name' => $row->user->first_name.' '.$row->user->last_name,
                'from_date' => date('d-m-Y', strtotime($row->from_date)),
                'to_date' => date('d-m-Y', strtotime($row->to_date)),
                'days' => $row->full_leave + ($row->half_leave / 2),
                'leave_detail' => $row->leave_detail,
                'file' => $row->file,
                'reason' => $row->reason,
                'status' => $row->status,
                'reason_of_rejection' => $row->reason_of_rejection,
                'operate' => $operate,
                'created_at' => convertDateFormat($row->created_at, 'd-m-Y H:i:s'),
                'updated_at' => convertDateFormat($row->updated_at, 'd-m-Y H:i:s'),
            ];

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function studentLeaveStatusUpdate(Request $request)
    {
        if (! Auth::user()->can('student-leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        try {

            $leave = Leave::findOrFail($request->id);
            $leave->status = $request->status;
            // $leave->save();

            $student = Students::with('user')->where('user_id', $leave->user_id)->first();
            $father_id = Students::where('user_id', $leave->user_id)->pluck('father_id');
            $mother_id = Students::where('user_id', $leave->user_id)->pluck('mother_id');
            $guardian_id = Students::where('user_id', $leave->user_id)->pluck('guardian_id');

            $user = Parents::where('id', $father_id)->orwhere('id', $mother_id)->orwhere('id', $guardian_id)->pluck('user_id');

            $title = 'Leave Alert';

            $type = 'leave';
            $image = null;
            $userinfo = null;

            if ($request->status == 1) {
                $body = $student->user->first_name.' '.$student->user->last_name.' '.'leave has been approved.';

                $notification = new Notification;
                $notification->send_to = 3;
                $notification->session_year_id = $leave->session_year_id;
                $notification->title = $title;
                $notification->message = $body;
                $notification->type = $type;
                $notification->date = Carbon::now();
                $notification->is_custom = 0;
                $notification->save();

                foreach ($user as $data) {
                    $user_notification = new UserNotification;
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $data;
                    $user_notification->save();
                }
                sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            } elseif ($request->status == 2) {
                if ($request->reason) {
                    $leave->reason_of_rejection = $request->reason;
                    $body = $student->user->first_name.' '.$student->user->last_name.' '.'leave is rejected due to '.' '.$request->reason;
                } else {
                    $body = $student->user->first_name.' '.$student->user->last_name.' '.'leave is rejected.';
                }

                $notification = new Notification;
                $notification->send_to = 3;
                $notification->session_year_id = $leave->session_year_id;
                $notification->title = $title;
                $notification->message = $body;
                $notification->type = $type;
                $notification->date = Carbon::now();
                $notification->is_custom = 0;
                $notification->save();

                foreach ($user as $data) {
                    $user_notification = new UserNotification;
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $data;
                    $user_notification->save();
                }
                sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            }

            $leave->save();

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    public function staffLeaveIndex()
    {
        if (! Auth::user()->can('staff-leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $teacher_id = Auth::user()->teacher->id;
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class.medium', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();

        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        $users = $teachers->merge($staff);

        $months = [
            0 => trans('All'),
            1 => trans('January'),
            2 => trans('February'),
            3 => trans('March'),
            4 => trans('April'),
            5 => trans('May'),
            6 => trans('June'),
            7 => trans('July'),
            8 => trans('August'),
            9 => trans('September'),
            10 => trans('October'),
            11 => trans('November'),
            12 => trans('December'),
        ];

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.staff_leave', compact('sessionYears', 'currentSessionYearId', 'currentSessionYear', 'holiday_days', 'users', 'months', 'public_holiday', 'class_sections'));
    }

    public function staffLeaveList(Request $request)
    {
        if (! Auth::user()->can('staff-leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;

        $user = Auth::user();

        $sql = Leave::with('leave_detail', 'user', 'file')->where('user_id', '!=', $user->id)->where('status', 1)
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                    });
                });
            });

        if ($session_year_id) {
            $sql->where('session_year_id', $session_year_id);
        }

        if ($filter_upcoming != 'All') {
            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }
            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                });
            }
            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);
        $total = $sql->count();

        $sql->orderBy('id', 'DESC')->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $operate = '<a href='.route('leave-status.update', $row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('leave.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user->id;
            $tempRow['name'] = $row->user->first_name.' '.$row->user->last_name;
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
}
