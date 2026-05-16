<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Parents;
use App\Models\Students;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\SessionYear;
use App\Models\Announcement;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\ClassSessionConfig;
use App\Models\Notification;
use App\Models\LeaveMaster;
use Illuminate\Http\Request;
use App\Models\StudentSessions;
use App\Exports\StudentsExport;
use App\Models\UserNotification;
use App\Imports\AttendanceImport;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('attendance-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $session_year_setting = getSettings('session_year');
        $currentSessionYear = SessionYear::find($session_year_setting['session_year']);
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->where('session_year_id', $currentSessionYear->id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();

        $currentSemester = Semester::where('session_year_id', $currentSessionYear->id)->get()->first(function ($semester) {
            return $semester->current;
        });

        $classIdsWithSemesterIncluded = ClassSessionConfig::where('session_year_id', $currentSessionYear->id)
            ->where('include_semesters', true)
            ->pluck('class_id')
            ->all();

        $sessionStartDate = is_string($currentSessionYear->start_date)
            ? date('d-m-Y', strtotime($currentSessionYear->start_date))
            : $currentSessionYear->start_date->format('d-m-Y');

        $sessionEndDate = is_string($currentSessionYear->end_date)
            ? date('d-m-Y', strtotime($currentSessionYear->end_date))
            : $currentSessionYear->end_date->format('d-m-Y');

        $semesterStartDate = $currentSemester
            ? (is_string($currentSemester->start_date)
                ? date('d-m-Y', strtotime($currentSemester->start_date))
                : $currentSemester->start_date->format('d-m-Y'))
            : null;

        $semesterEndDate = $currentSemester
            ? (is_string($currentSemester->end_date)
                ? date('d-m-Y', strtotime($currentSemester->end_date))
                : $currentSemester->end_date->format('d-m-Y'))
            : null;

        $classSectionDateLimits = [];
        foreach ($class_sections as $section) {
            $useSemesterDates = $currentSemester && in_array($section->class_id, $classIdsWithSemesterIncluded);

            $classSectionDateLimits[$section->id] = [
                'start_date' => $useSemesterDates ? $semesterStartDate : $sessionStartDate,
                'end_date' => $useSemesterDates ? $semesterEndDate : $sessionEndDate,
            ];
        }

        // return response()->json($currentSemester);
        return view('attendance.index', compact('class_sections', 'currentSessionYear', 'currentSemester', 'classSectionDateLimits'));
    }


    public function view()
    {
        if (!Auth::user()->can('attendance-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        $session_year_setting = getSettings('session_year');
        $currentSessionYearId = $session_year_setting['session_year'];

        return view('attendance.view', compact('class_sections', 'session_years', 'currentSessionYearId'));
    }

    public function getAttendanceData(Request $request)
    {
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();
        $response = Attendance::select('type')->where(['date' => $date, 'class_section_id' => $request->class_section_id])->pluck('type')->first();
        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('attendance-create') && !Auth::user()->can('attendance-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message')
            ];
            return response()->json($response);
        }

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'student_id' => 'required|array|min:1',
            'date' => 'required|date_format:d-m-Y',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first()
            ];
            return response()->json($response);
        }

        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = $request->class_section_id;
            $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();
            $student_ids = $request->student_id;

            // Fetch existing attendance records for this date and class_section
            $existingAttendances = Attendance::where([
                'date' => $date,
                'class_section_id' => $class_section_id,
                'session_year_id' => $session_year_id
            ])->get()->keyBy('student_id');

            foreach ($student_ids as $student_id) {
                $attendance = $existingAttendances->has($student_id)
                    ? $existingAttendances->get($student_id)
                    : new Attendance();

                $attendance->class_section_id = $class_section_id;
                $attendance->student_id = $student_id;
                $attendance->session_year_id = $session_year_id;
                $attendance->date = $date;

                // Handle holiday attendance
                if ($request->has('holiday') && $request->holiday == 3) {
                    $attendance->type = $request->holiday;
                } else {
                    $typeKey = "type" . $student_id;
                    $attendance->type = $request->has($typeKey) ? $request->$typeKey : null;
                }

                // Set status to 1 (present/recorded)
                $attendance->status = 1;
                $attendance->save();

                // Send notification if student is absent (type == 0)
                if (
                    (!isset($attendance->getOriginal()['type']) || $attendance->getOriginal()['type'] != 0) // Only send if not already absent
                    && isset($attendance->type) && $attendance->type == 0
                ) {
                    $student = Students::with('user')->find($student_id);
                    if ($student && $student->user) {
                        $father_id = $student->father_id;
                        $mother_id = $student->mother_id;
                        $guardian_id = $student->guardian_id;

                        $userIds = Parents::whereIn('id', array_filter([$father_id, $mother_id, $guardian_id]))
                            ->pluck('user_id');

                        if ($userIds->count() > 0) {
                            $title = 'Attendance Alert';
                            $body = $student->user->first_name . ' ' . $student->user->last_name . ' is Absent on ' . date('d-m-Y', strtotime($date));
                            $type = 'attendance';
                            $image = null;
                            $userinfo = null;

                            $notification = new Notification();
                            $notification->session_year_id = $session_year_id;
                            $notification->send_to = 4;
                            $notification->title = $title;
                            $notification->message = $body;
                            $notification->type = $type;
                            $notification->date = Carbon::now();
                            $notification->is_custom = 0;
                            $notification->save();

                            foreach ($userIds as $userId) {
                                $user_notification = new UserNotification();
                                $user_notification->notification_id = $notification->id;
                                $user_notification->user_id = $userId;
                                $user_notification->save();
                            }

                            sendSimpleNotification($userIds, $title, $body, $type, $image, $userinfo);
                        }
                    }
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully')
            ];
        } catch (\Exception $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage()
            ];
        }
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if (!Auth::user()->can('attendance-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 200;
        $sort = 'roll_number';
        $order = 'ASC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $class_section_id = $request->class_section_id;
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();
        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];
        $current_date = Carbon::now()->toDateString();

        // Get existing attendance records for the selected date
        $existingAttendance = Attendance::where([
            'date' => $date,
            'class_section_id' => $class_section_id,
            'session_year_id' => $session_year_id
        ])->pluck('type', 'student_id')->toArray();

        // Get leave records for the selected date
        $leaveUserIds = Leave::with('leave_detail')
            ->where('status', 1)
            ->whereHas('leave_detail', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })
            ->pluck('user_id')
            ->toArray();

        // Get students for the class section
        // $sql = Students::with('user')->where('class_section_id', $class_section_id)->whereHas('user', function ($q) {
        //     $q->where('status', 1);
        // });

        $sql = Students::with('user')
            ->whereHas('studentSessions', function ($q) use ($session_year_id, $class_section_id) {
                $q->where('session_year_id', $session_year_id)
                    ->where('class_section_id', $class_section_id);
            });

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                            ->orWhere('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {
            $isOnLeave = in_array($row->user_id, $leaveUserIds);
            $existingType = isset($existingAttendance[$row->id]) ? $existingAttendance[$row->id] : null;

            if ($isOnLeave) {
                $type = '<div class="form-check-inline"><label class="form-check-label">
                <input type="hidden" class="type" name="type' . $row->id . '" value="0"><span class="badge badge-danger">On Leave</span>
                </label></div></div>';
            } else {
                $type = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                <input required type="radio" class="type" name="type' . $row->id . '" value="1" ' . ($existingType === 1 ? 'checked' : '') . '>Present
                </label></div>';
                $type .= '<div class="form-check-inline"><label class="form-check-label">
                <input type="radio" class="type" name="type' . $row->id . '" value="0" ' . ($existingType === 0 ? 'checked' : '') . '>Absent
                </label></div></div>';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = "<input type='text' name='student_id[]' class='form-control' readonly value=" . $row->id . ">";
            $tempRow['admission_no'] = $row->admission_no;
            $tempRow['roll_no'] = $row->roll_number;
            $tempRow['name'] = $row->user->first_name . ' ' . $row->user->last_name;
            $tempRow['type'] = $type;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function attendance_show(Request $request)
    {
        if (!Auth::user()->can('attendance-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 200;
        $sort = 'student_id';
        $order = 'ASC';
        $session_year_id = 0;

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];
        if (isset($_GET['session_year_id']))
            $session_year_id = $_GET['session_year_id'];

        $class_section_id = $request->class_section_id;
        $attendance_type = $request->attendance_type;
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'date' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }

        $sql = Attendance::where('date', $date)
            ->where('session_year_id', $session_year_id)
            ->where('class_section_id', $class_section_id)
            ->with('student');

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('student_id', 'LIKE', "%$search%")
                ->orWhereHas('student.user', function ($q) use ($search) {
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'")
                        ->orwhere('users.first_name', 'LIKE', "%$search%")
                        ->orwhere('users.last_name', 'LIKE', "%$search%");
                })
                ->orWhereHas('student', function ($q) use ($search) {
                    $q->where('admission_no', 'LIKE', "%$search%")
                        ->orwhere('id', 'LIKE', "%$search%")
                        ->orwhere('user_id', 'LIKE', "%$search%")
                        ->orwhere('roll_number', 'LIKE', "%$search%");
                });
        }
        if (isset($attendance_type) && $attendance_type != '') {
            $sql->where('type', $attendance_type);
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $row) {
            $type = $row->type;
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = $row->student_id;
            $tempRow['user_id'] = $row->student->user_id;
            $tempRow['admission_no'] = $row->student->admission_no;
            $tempRow['roll_no'] = $row->student->roll_number;
            $tempRow['name'] = $row->student->user->first_name . ' ' . $row->student->user->last_name;
            $tempRow['type'] = ($type == 1) ? '<label class="badge badge-info"> Present</label>' : (($type == 3) ? '<label class="badge badge-success"> Holiday</label>' : '<label class="badge badge-danger"> Absent</label>');
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function createBulkData()
    {
        if (!Auth::user()->can('attendance-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();
        return view('attendance.add_bulk_data', compact('class_sections'));
    }

    public function storeBulkData(Request $request)
    {
        if (!Auth::user()->can('attendance-create') || !Auth::user()->can('attendance-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'file' => 'required|mimes:csv,txt'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $class_section_id = $request->class_section_id;
            $date = $request->date;
            Excel::import(new AttendanceImport($class_section_id, $date), $request->file);
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully')
            ];
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
            );
        }
        return response()->json($response);
    }

    public function studentExport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        $class_section_id = $request->class_section_id;
        $date = $request->date;

        return Excel::download(new StudentsExport($class_section_id, $date), 'student_list.csv');
    }

    public function attendaceReportIndex()
    {
        if (!Auth::user()->can('attendance-report')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();
        $user = Auth::user();

        if ($user->hasRole('Teacher')) {
            $teacher_id = Auth::user()->teacher->id;
            $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
            $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();
        } else {
            $class_sections = ClassSection::with('class', 'section', 'streams')->get();
        }

        return view('attendance.report', compact('class_sections', 'sessionYears', 'currentSessionYearId', 'currentSessionYear',));
    }

    public function attendanceReportShow(Request $request)
    {
        if (!Auth::user()->can('attendance-report')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 200;
        $sort = 'student_id';
        $order = 'ASC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $total_days = 0;
        $session_year_id = $request->session_year_id;
        $class_section_id = $request->class_section_id;
        $semester_id = $request->semester_id;

        // Validate required parameters
        if (empty($class_section_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Class section is required',
                'total' => 0,
                'rows' => []
            ]);
        }

        if (empty($session_year_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Session year is required',
                'total' => 0,
                'rows' => []
            ]);
        }

        $currentSessionYear = SessionYear::where('id', $session_year_id)->first();

        if (!$currentSessionYear) {
            return response()->json([
                'error' => true,
                'message' => 'Session year not found',
                'total' => 0,
                'rows' => []
            ]);
        }

        // Verify class section exists
        $classSection = ClassSection::find($class_section_id);
        if (!$classSection) {
            return response()->json([
                'error' => true,
                'message' => 'Class section not found',
                'total' => 0,
                'rows' => []
            ]);
        }

        // For teachers, verify they are assigned to this class section
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher_id = $user->teacher->id;
            $classTeacherQuery = ClassTeacher::where('class_section_id', $class_section_id)
                ->where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id);

            if (!empty($semester_id)) {
                $classTeacherQuery->where('semester_id', $semester_id);
            }

            $isTeacherAssigned = $classTeacherQuery->exists();

            if (!$isTeacherAssigned) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are not assigned to this class section',
                    'total' => 0,
                    'rows' => []
                ]);
            }
        }

        $currentDate = Carbon::now();
        $startDate = Carbon::parse($currentSessionYear->start_date);
        $endDate = Carbon::parse($currentSessionYear->end_date);

        if (!empty($semester_id)) {
            $selectedSemester = Semester::where('id', $semester_id)
                ->where('session_year_id', $session_year_id)
                ->first();

            if ($selectedSemester) {
                $startDate = Carbon::parse($selectedSemester->start_date);
                $endDate = Carbon::parse($selectedSemester->end_date);
            }
        }

        $rangeEndDate = $endDate->lt($currentDate) ? $endDate : $currentDate;

        // Get holiday_days from LeaveMaster
        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYear->id)->first();
        $holiday_days = ''; // Empty string by default

        if ($leaveMaster && $leaveMaster->holiday_days) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $holiday_days = explode(',', $holiday_days);

        $holiday_date = Attendance::where('session_year_id', $currentSessionYear->id)->where('type', 3)->distinct()->pluck('date')->toArray();

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();
        $holidays = array_unique(array_merge($holiday_date, $holiday));

        // Create a date range from the start date to the current date
        $dateRange = new Collection();
        if ($startDate->lte($rangeEndDate)) {
            for ($date = $startDate->copy(); $date->lte($rangeEndDate); $date->addDay()) {
                $dateRange->push($date->format('Y-m-d'));
            }
        }

        $filteredDateRange = $dateRange->filter(function ($date) use ($holiday_days) {
            $carbonDate = Carbon::parse($date);
            return !in_array($carbonDate->format('l'), $holiday_days);
        });

        $workingDays = $filteredDateRange->diff($holidays);

        $total_days = $workingDays->count();

        $sql = Students::with('user')
            ->where('class_section_id', $class_section_id)
            ->whereHas('studentSessions', function ($q) use ($currentSessionYear, $class_section_id) {
                $q->where('session_year_id', $currentSessionYear->id)
                    ->where('status', 1)
                    ->where('class_section_id', $class_section_id);
            });

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                            ->orWhere('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $row) {

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = "<input type='text' name='student_id[]' class='form-control' readonly value=" . $row->id . ">";
            $tempRow['admission_no'] = $row->admission_no;
            $tempRow['roll_no'] = $row->roll_number;
            $tempRow['name'] = $row->user->first_name . ' ' . $row->user->last_name;
            $tempRow['total_days'] = $total_days ?? 0;

            $present_days = Attendance::where('student_id', $row->id)->where('session_year_id',  $currentSessionYear->id)->whereIn('date', $workingDays)->where('type', 1)->count();
            $tempRow['present_days'] = $present_days ?? 0;
            $absent_days = Attendance::where('student_id', $row->id)->where('session_year_id',  $currentSessionYear->id)->whereIn('date', $workingDays)->where('type', 0)->count();
            $tempRow['absent_days'] = $absent_days ?? 0;

            if ($total_days > 0) {
                $percentage = ($present_days / $total_days) * 100;
            } else {
                $percentage = 0; // Avoid division by zero
            }
            $tempRow['percentage'] = round($percentage, 2);

            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
