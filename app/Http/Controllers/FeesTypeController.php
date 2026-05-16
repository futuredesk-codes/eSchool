<?php
// declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\Mediums;
use App\Models\Parents;
use App\Models\FeesPaid;
use App\Models\FeesType;
use App\Models\Settings;
use App\Models\Students;
use App\Models\FeesClass;
use App\Models\ClassSchool;
use App\Models\SessionYear;
use App\Models\ClassSection;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\FeesChoiceable;
use App\Models\InstallmentFee;
use App\Models\PaidInstallmentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\UserNotification;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\StudentSessions;

class FeesTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('fees-type')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        return view('fees.fees_types');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('fees-type')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            // 'choiceable' => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $fees_type = new FeesType();
            $fees_type->name = $request->name;
            $fees_type->description = $request->description;
            // $fees_type->choiceable = $request->choiceable;
            $fees_type->save();
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully'),
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (!Auth::user()->can('fees-type')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $sql = FeesType::select('*');
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('description', 'LIKE', "%$search%")
                ->orwhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orwhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%");
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $feesTypeIds = $res->pluck('id')->toArray();
        $typesWithClasses = FeesClass::whereIn('fees_type_id', $feesTypeIds)->pluck('fees_type_id')->unique()->toArray();
        $typesWithChoiceables = FeesChoiceable::whereIn('fees_type_id', $feesTypeIds)->pluck('fees_type_id')->unique()->toArray();

        foreach ($res as $row) {
            $relatedData = [];
            if (in_array($row->id, $typesWithClasses)) {
                $relatedData[] = 'fee classes';
            }
            if (in_array($row->id, $typesWithChoiceables)) {
                $relatedData[] = 'fee choiceables';
            }

            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="' . trans('edit') . '" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="' . route('fees-type.destroy', $row->id) . '"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    title="' . trans('delete') . '"
                    data-id="' . $row->id . '"'
                . (!empty($relatedData) ? " data-related='" . json_encode($relatedData) . "'" : '') .
                '>
                    <i class="fa fa-trash"></i>
                </a>';


            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            // $tempRow['choiceable'] = $row->choiceable;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('fees-type')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'edit_name' => 'required',
            'edit_description' => 'nullable',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $fees_type = FeesType::findOrFail($id);
            $fees_type->name = $request->edit_name;
            $fees_type->description = $request->edit_description;
            $fees_type->save();
            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully'),
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
            );
        }
        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('fees-type')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $feesType = FeesType::findOrFail($id);

            // Cascade handled at model level
            $feesType->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function feesClassListIndex()
    {
        if (!Auth::user()->can('fees-classes')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections', 'streams')->get();
        $fees_type = FeesType::orderBy('id', 'ASC')->pluck('name', 'id');
        $fees_type_data = FeesType::get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return response(view('fees.fees_class', compact('classes', 'fees_type', 'fees_type_data', 'mediums', 'session_years')));
    }

    public function feesClassList()
    {
        if (!Auth::user()->can('fees-classes')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';

        if (isset($_GET['session_year_id']))
            $session_year_id = $_GET['session_year_id'];
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];


        $sql = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')
            ->with([
                'fees_class' => function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                },
                'medium',
                'streams'
            ]);

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%");
        }
        if (isset($_GET['medium_id']) && !empty($_GET['medium_id'])) {
            $sql = $sql->where('medium_id', $_GET['medium_id']);
        }
        $total = $sql->count();

        $sql->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {

            $row = (object)$row;
            $operate = '<a href=' . route('class.edit', $row->id) . ' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="' . trans('edit') . '" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->id;
            $tempRow['class_name'] = $row->name . ' ' . $row->medium->name;
            $tempRow['stream_name'] = $row->streams->name ?? '-';
            if (sizeof($row->fees_class)) {
                $total_amount = 0;
                $base_amount = 0;
                $fees_type_table = array();
                foreach ($row->fees_class as $fees_details) {
                    $fees_type_table[] = array(
                        'id' => $fees_details->id,
                        'fees_name' => $fees_details->fees_type->name,
                        'amount' => $fees_details->amount,
                        'choiceable' => $fees_details->choiceable,
                        'fees_type_id' => $fees_details->fees_type->id,
                    );
                    if ($fees_details->choiceable == 0) {
                        $base_amount += $fees_details->amount;
                    }
                    $total_amount += $fees_details->amount;
                }
                $tempRow['fees_type'] = isset($fees_type_table) ? $fees_type_table : ' ';
                $tempRow['base_amount'] = round($base_amount, 2);
                $tempRow['total_amount'] = round($total_amount, 2);
            } else {
                $tempRow['fees_type'] = [];
                $tempRow['base_amount'] = "-";
                $tempRow['total_amount'] = "-";
            }
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function updateFeesClass(Request $request)
    {
        if (!Auth::user()->can('fees-classes')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validation_rules = array(
            'class_id' => 'required|numeric',
            'edit_fees_type.*.fees_type_id' => 'required',
            'edit_fees_type.*.amount' => 'required:edit_fees_type',
            'edit_fees_type.*.choiceable' => 'required|in:0,1',
            "session_year_id" => 'required'
        );
        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            // //Update Fees Type For Class first
            if ($request->edit_fees_type) {
                foreach ($request->edit_fees_type as $row) {
                    $edit_fees_type = FeesClass::findOrFail($row['fees_class_id']);
                    $edit_fees_type->fees_type_id = $row['fees_type_id'];
                    $edit_fees_type->amount = $row['amount'];
                    $edit_fees_type->choiceable = $row['choiceable'];
                    $edit_fees_type->update();
                }
            }

            //Add New Fees Type For Class
            if ($request->fees_type) {
                $fees_type = array();
                foreach ($request->fees_type as $row) {
                    $fees_type[] = array(
                        'class_id' => $request->class_id,
                        'session_year_id' => $request->session_year_id,
                        'fees_type_id' => $row['fees_type_id'],
                        'choiceable' => $row['choiceable'],
                        'amount' => $row['amount'],
                    );
                }
                FeesClass::insert($fees_type);
            }
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully'),
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }

    public function removeFeesClass($id)
    {
        if (!Auth::user()->can('fees-classes')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $feesClass = FeesClass::findOrFail($id);

            // Cascade handled at model level
            $feesClass->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function feesPaidListIndex()
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections', 'streams')->get();
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();

        // // Get current session year
        // $session_year = getSettings('session_year');
        // $session_year_id = $session_year['session_year'];

        // // Get installments for current session year
        //     ->orderBy('due_date')
        //     ->get();

        return response(view('fees.fees_paid', compact('classes', 'mediums', 'session_year_all')));
    }

    public function feesPaidList()
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $session_year_id = null;
        if (isset($_GET['session_year_id']) && $_GET['session_year_id'] !== '') {
            $session_year_id = $_GET['session_year_id'];
        }

        // Base query: use StudentSessions so ALL enrolled students appear,
        // regardless of whether they have paid fees yet.
        // This allows admin to record first-time payments for any enrolled student.
        $sessionQuery = StudentSessions::with([
            'student.user:id,first_name,last_name',
            'student.father',
            'student.mother',
            'student.guardian',
            'class_section.class.medium',
            'class_section.class.streams',
            'class_section.section',
        ])->where('session_year_id', $session_year_id);

        // Filter by class
        if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $class_section_ids = ClassSection::where('class_id', $class_id)->pluck('id');
            $sessionQuery->whereIn('class_section_id', $class_section_ids);
        }

        // Filter by payment mode (via fees_paid)
        if (isset($_GET['mode']) && ($_GET['mode'] == 0 || $_GET['mode'] == 1 || $_GET['mode'] == 2)) {
            $mode = $_GET['mode'];
            $sessionQuery->whereHas('student.fees_paid', function ($q) use ($mode, $session_year_id) {
                $q->where('mode', $mode);
                if ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }
            });
        }

        // Search by student name or id
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sessionQuery->whereHas('student', function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        // Only show students whose class has compulsory fees configured in fees_classes.
        // If a class has no fees set up, those students should not appear in this list.
        $sessionQuery->whereHas('class_section.class.fees_class', function ($q) use ($session_year_id) {
            $q->where('choiceable', 0)
                ->where('session_year_id', $session_year_id);
        });

        $total = $sessionQuery->count();
        $sessionQuery->orderBy($sort, $order)->skip($offset)->take($limit);
        $sessionResults = $sessionQuery->get();

        // Build a Students-like result set from StudentSessions for the rest of the loop
        // We load fees_paid per student scoped to the session year for display
        $studentIds = $sessionResults->pluck('student_id');
        $feesPaidMap = FeesPaid::whereIn('student_id', $studentIds)
            ->when($session_year_id, fn($q) => $q->where('session_year_id', $session_year_id))
            ->with('class', 'session_year', 'payment_transaction')
            ->get()
            ->keyBy('student_id');

        // Re-map session results into a Students model structure expected by the loop below
        $res = $sessionResults->map(function ($ss) use ($feesPaidMap) {
            $student = $ss->student;
            if (!$student) return null;
            // Inject the session-scoped class_section onto the student object
            $student->setRelation('class_section', $ss->class_section);
            // Inject session-scoped fees_paid (may be null = not paid yet)
            $student->setRelation('fees_paid', $feesPaidMap->get($student->id));
            return $student;
        })->filter()->values();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = "";
            // Resolve session year context (use a local var to avoid corrupting the outer $session_year_id)
            if (!empty($session_year_id)) {
                $effective_session_year_id = $session_year_id;
                $session_year = SessionYear::where('id', $session_year_id)->first();
            } else {
                // No filter selected: derive session year from fees_paid record if available
                $session_year = $row->fees_paid->session_year ?? null;
                $effective_session_year_id = $session_year?->id;
            }

            $due_date = $session_year->fee_due_date ?? null;
            $is_installaments_enabled = $session_year->include_fee_installments ?? 0;

            $current_date = Carbon::now()->format('d-m-Y');
            $due_charges = 0;
            $base_amount_with_due_charges = 0;

            $payment_transaction = null;

            if ($row->fees_paid) {
                $payment_transaction = PaymentTransaction::where(['student_id' => $row->id, 'class_id' => $row->class_section->class_id, 'session_year_id' => $effective_session_year_id])->latest()->first();
            }

            // Base Amount
            $base_amount = FeesClass::where(['class_id' => $row->class_section->class_id, 'choiceable' => 0, 'session_year_id' => $session_year_id])
                ->selectRaw('SUM(amount) as base_amount')->first();
            $base_amount = $base_amount['base_amount'];

            // if due charges is applicable
            if ($due_date && strtotime($current_date) > strtotime($due_date)) {
                $due_charges = $session_year->fee_due_charges;
                $charges = (($due_charges) * ($base_amount) / 100);
                $base_amount_with_due_charges = $base_amount + $charges;
            }

            // Get the fees data
            $compulsory_fees = FeesClass::where([
                'class_id'       => $row->class_section->class_id,
                'choiceable'     => 0,
                'session_year_id' => $session_year_id,
            ])->with('fees_type')->get();

            $choiceable_fees = FeesClass::where([
                'class_id'       => $row->class_section->class_id,
                'choiceable'     => 1,
                'session_year_id' => $session_year_id,
            ])->with('fees_type')->get();

            $installment_data = InstallmentFee::where('session_year_id', $effective_session_year_id)->get();

            //Get Paid Fees

            $operate = "";
            // check that fees paid is not empty
            if (isset($row->fees_paid) && !empty($row->fees_paid)) {
                // checks that fees paid's session year matches the current session year then allow to modify the fees payments or else show only clear and pdf option
                if ($row->fees_paid->session_year_id == $effective_session_year_id) {
                    $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-left">';
                    $operate .= '<a href="#"class="compulsory-data dropdown-item" data-id=' . $row->id . ' title="' . trans('compulsory') . ' ' . trans('fees') . '" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>' . trans('compulsory') . ' ' . trans('fees') . '</a><div class="dropdown-divider"></div>';
                    $operate .= '<a href="#" class="optional-data dropdown-item" data-id=' . $row->id . ' title="' . trans('optional') . ' ' . trans('fees') . '" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>' . trans('optional') . ' ' . trans('fees') . '</a>';
                    $operate .= '</div></div>&nbsp;&nbsp;';
                    $operate .= '<a href=' . route('fees.paid.clear.data', $row->fees_paid->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" title="' . trans('clear') . '" data-id=' . $row->fees_paid->id . '><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
                    $operate .= '<a href=' . route('fees.paid.receipt.pdf', $row->fees_paid->id) . ' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id=' . $row->fees_paid->id . ' title="' . trans('generate_pdf') . ' ' . trans('fees') . '"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                } else {
                    $operate .= '<a href=' . route('fees.paid.clear.data', $row->fees_paid->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" title="' . trans('clear') . '" data-id=' . $row->fees_paid->id . '><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
                    $operate .= '<a href=' . route('fees.paid.receipt.pdf', $row->fees_paid->id) . ' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id=' . $row->fees_paid->id . ' title="' . trans('generate_pdf') . ' ' . trans('fees') . '"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                }
            } else {
                $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-left">';
                $operate .= '<a href="#"class="compulsory-data dropdown-item" data-id=' . $row->id . ' title="' . trans('compulsory') . ' ' . trans('fees') . '" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>' . trans('compulsory') . ' ' . trans('fees') . '</a><div class="dropdown-divider"></div>';
                $operate .= '<a href="#" class="optional-data dropdown-item" data-id=' . $row->id . ' title="' . trans('optional') . ' ' . trans('fees') . '" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>' . trans('optional') . ' ' . trans('fees') . '</a>';
                $operate .= '</div></div>&nbsp;&nbsp;';
            }

            $tempRow['id'] = null;
            $tempRow['student_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['father_id'] = $row->father_id;
            $tempRow['mother_id'] = $row->mother_id;
            $tempRow['student_name'] = $row->user->first_name . ' ' . $row->user->last_name;
            $tempRow['class_id'] = $row->class_section->class_id;
            $tempRow['class_name'] = $row->class_section->class->name . '-' . $row->class_section->section->name . ' ' . $row->class_section->class->medium->name;
            $tempRow['stream_name'] = $row->class_section->class->streams->name ?? '-';
            $tempRow['compulsory_fees'] = sizeof($compulsory_fees) ? $compulsory_fees : null;
            $tempRow['is_installaments_enabled'] = $is_installaments_enabled;

            $paid_installment_data = PaidInstallmentFee::where(['student_id' => $row->id, 'session_year_id' => $effective_session_year_id, 'status' => 1])->first();
            $tempRow['is_installment_paid'] = $paid_installment_data ? 1 : 0;

            if (isset($installment_data) && !empty($installment_data)) {
                $tempRow['installment_data'] = array();
                foreach ($installment_data as $data) {
                    // Paid Installment Data
                    $paid_installment = PaidInstallmentFee::where(['student_id' => $row->id, 'installment_fee_id' => $data->id, 'status' => 1])->first();

                    if (strtotime($current_date) >= strtotime($data->due_date)) {
                        $tempRow['installment_data'][] = array(
                            'id' => $data->id,
                            'name' => $data->name,
                            'due_date' => $data->due_date,
                            'due_charges' => $data->due_charges,
                            'due_charges_applicable' => 1,
                            'paid' => $paid_installment->status ?? 0,
                            'amount' => $paid_installment->amount ?? '',
                            'paid_id' => $paid_installment ? $paid_installment->id : '',
                            'paid_on' => $paid_installment ? date('d-m-Y', strtotime($paid_installment->date)) : '',
                        );
                    } else {
                        $tempRow['installment_data'][] = array(
                            'id' => $data->id,
                            'name' => $data->name,
                            'due_date' => $data->due_date,
                            'due_charges' => $data->due_charges,
                            'due_charges_applicable' => 0,
                            'paid' => $paid_installment->status ?? 0,
                            'amount' => $paid_installment->amount ?? '',
                            'paid_id' => $paid_installment ? $paid_installment->id : '',
                            'paid_on' => $paid_installment ? date('d-m-Y', strtotime($paid_installment->date ?? '')) : '',
                        );
                    }
                }
            }
            if (isset($choiceable_fees) && !empty($choiceable_fees)) {
                $tempRow['choiceable_fees'] = array();
                $paid_choiceable_fees_query = FeesChoiceable::where(['class_id' => $row->class_section->class_id, 'student_id' => $row->id, 'session_year_id' => $effective_session_year_id, 'status' => 1]);
                foreach ($choiceable_fees as $data) {
                    //Clone the Query To Avoid Extra Addition of Where Fees Type ID
                    $paid_choiceable_data = (clone $paid_choiceable_fees_query)->where('fees_type_id', $data->fees_type_id);
                    if ($paid_choiceable_data->count()) {
                        $tempRow['choiceable_fees'][] = array(
                            'id' => $data->id,
                            'name' => $data->fees_type->name,
                            'class_id' => $data->class_id,
                            'fees_type_id' => $data->fees_type_id,
                            'choiceable' => $data->choiceable,
                            'amount' => $data->amount,
                            'is_paid' => 1,
                            'paid_id' => $paid_choiceable_data->first()->id,
                            'date' => $paid_choiceable_data->first()->date,
                        );
                    } else {
                        $tempRow['choiceable_fees'][] = array(
                            'id' => $data->id,
                            'name' => $data->fees_type->name,
                            'class_id' => $data->class_id,
                            'fees_type_id' => $data->fees_type_id,
                            'choiceable' => $data->choiceable,
                            'amount' => $data->amount,
                            'is_paid' => 0,
                        );
                    }
                }
            }
            $tempRow['base_amount'] = $base_amount;
            $tempRow['base_amount_with_due_charges'] = $base_amount_with_due_charges;
            $tempRow['due_charges'] = array(
                'date' => $due_date ? date('d-m-Y', strtotime($due_date)) : null,
                'charges' => $charges ?? null,
            );
            $tempRow['fees_status'] = $row->fees_paid->is_fully_paid ?? null;
            // "Total Fees" shows the configured fee amount from fees_classes (the total a student must pay),
            // not what has been paid so far. This removes the empty column for unpaid students.
            $tempRow['total_fees'] = $base_amount ?? 0;
            // Separately expose what has actually been paid for reference
            $tempRow['fees_paid_amount'] = $row->fees_paid->total_amount ?? null;
            $tempRow['current_date'] = $current_date;
            $tempRow['date'] = $row->fees_paid != null && $row->fees_paid->date != null ? date('d-m-Y', strtotime($row->fees_paid->date)) : '-';
            $tempRow['session_year_name'] = $row->fees_paid->session_year->name ?? null;
            $tempRow['mode'] = $payment_transaction->mode ?? null;
            $tempRow['type_of_fee'] = $payment_transaction->type_of_fee ?? null;
            $tempRow['cheque_no'] = $payment_transaction->cheque_no ?? null;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function clearFeesPaidData($id)
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $fees_paid_data = FeesPaid::find($id);

            // get the ids from fees paid to remove the fees choiced data
            $student_id = $fees_paid_data->student_id;
            $class_id = $fees_paid_data->class_id;
            $session_year_id = $fees_paid_data->session_year_id;

            $fees_paid_data->delete();

            FeesChoiceable::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();
            PaidInstallmentFee::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();
            PaymentTransaction::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();

            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function feesConfigIndex()
    {
        if (!Auth::user()->can('fees-config')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $settings = getSettings();
        $domain =  request()->getSchemeAndHttpHost();
        return view('fees.fees_config', compact('settings', 'domain'));
    }

    public function feesConfigUpdate(Request $request)
    {
        if (!Auth::user()->can('fees-config')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'razorpay_status' => 'required',
            'razorpay_secret_key' => 'required_if:razorpay_status,1|nullable',
            'razorpay_api_key' => 'required_if:razorpay_status,1|nullable',
            'razorpay_webhook_secret' => 'required_if:razorpay_status,1|nullable',
            'razorpay_webhook_url' => 'required_if:razorpay_status,1|nullable',
            'razorpay_currency_code' => 'required_if:razorpay_status,1|nullable',
            'stripe_status' => 'required',
            'stripe_publishable_key' => 'required_if:stripe_status,1|nullable',
            'stripe_secret_key' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_secret' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_url' => 'required_if:stripe_status,1|nullable',
            'stripe_currency_code' => 'required_if:razorpay_status,1|nullable',
            'paystack_status' => 'required',
            'paystack_public_key' => 'required_if:paystack_status,1|nullable',
            'paystack_secret_key' => 'required_if:paystack_status,1|nullable',
            'paystack_webhook_url' => 'required_if:paystack_status,1|nullable',
            'paystack_currency_code' => 'required_if:razorpay_status,1|nullable',
            'flutterwave_status' => 'required',
            'flutterwave_public_key' => 'required_if:flutterwave_status,1|nullable',
            'flutterwave_secret_key' => 'required_if:flutterwave_status,1|nullable',
            'flutterwave_webhook_url' => 'required_if:flutterwave_status,1|nullable',
            'flutterwave_currency_code' => 'required_if:razorpay_status,1|nullable',
            'currency_code' => 'required',
            'currency_symbol' => 'required',
            'compulsory_fee_payment_mode' => 'required',
            'is_student_can_pay_fees' => 'required',

        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {

            //razorpay_status
            if (Settings::where('type', 'razorpay_status')->exists()) {
                $data = [
                    'message' => $request->razorpay_status
                ];
                Settings::where('type', 'razorpay_status')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'razorpay_status';
                $setting->message = $request->razorpay_status;
                $setting->save();
            }

            if ($request->razorpay_status) {
                //razorpay_secret_key
                if (Settings::where('type', 'razorpay_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_secret_key)
                    ];
                    Settings::where('type', 'razorpay_secret_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_secret_key';
                    $setting->message = trim($request->razorpay_secret_key);
                    $setting->save();
                }

                //razorpay_api_key
                if (Settings::where('type', 'razorpay_api_key')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_api_key)
                    ];
                    Settings::where('type', 'razorpay_api_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_api_key';
                    $setting->message = trim($request->razorpay_api_key);
                    $setting->save();
                }

                //razorpay_webhook_secret
                if (Settings::where('type', 'razorpay_webhook_secret')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_webhook_secret)
                    ];
                    Settings::where('type', 'razorpay_webhook_secret')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_webhook_secret';
                    $setting->message = trim($request->razorpay_webhook_secret);
                    $setting->save();
                }

                //razorpay_webhook_url
                if (Settings::where('type', 'razorpay_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_webhook_url)
                    ];
                    Settings::where('type', 'razorpay_webhook_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_webhook_url';
                    $setting->message = trim($request->razorpay_webhook_url);
                    $setting->save();
                }

                //razorpay_webhook_url
                if (Settings::where('type', 'razorpay_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_currency_code)
                    ];
                    Settings::where('type', 'razorpay_currency_code')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_currency_code';
                    $setting->message = trim($request->razorpay_currency_code);
                    $setting->save();
                }

                $env_update = changeEnv([
                    'RAZORPAY_SECRET_KEY' => trim($request->razorpay_secret_key),
                    'RAZORPAY_API_KEY' => trim($request->razorpay_api_key),
                    'RAZORPAY_WEBHOOK_SECRET' => trim($request->razorpay_webhook_secret),
                    'RAZORPAY_WEBHOOK_URL' => trim($request->razorpay_webhook_url),
                ]);

                if ($env_update) {
                    $response = array(
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    );
                }
            }


            //stripe_status
            if (Settings::where('type', 'stripe_status')->exists()) {
                $data = [
                    'message' => $request->stripe_status
                ];
                Settings::where('type', 'stripe_status')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'stripe_status';
                $setting->message = $request->stripe_status;
                $setting->save();
            }

            if ($request->stripe_status) {

                //stripe_publishable_key
                if (Settings::where('type', 'stripe_publishable_key')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_publishable_key)
                    ];
                    Settings::where('type', 'stripe_publishable_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_publishable_key';
                    $setting->message = trim($request->stripe_publishable_key);
                    $setting->save();
                }

                //stripe_secret_key
                if (Settings::where('type', 'stripe_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_secret_key)
                    ];
                    Settings::where('type', 'stripe_secret_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_secret_key';
                    $setting->message = trim($request->stripe_secret_key);
                    $setting->save();
                }

                //stripe_webhook_secret
                if (Settings::where('type', 'stripe_webhook_secret')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_webhook_secret)
                    ];
                    Settings::where('type', 'stripe_webhook_secret')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_webhook_secret';
                    $setting->message = trim($request->stripe_webhook_secret);
                    $setting->save();
                }

                //stripe_webhook_url
                if (Settings::where('type', 'stripe_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_webhook_url)
                    ];
                    Settings::where('type', 'stripe_webhook_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_webhook_url';
                    $setting->message = trim($request->stripe_webhook_url);
                    $setting->save();
                }

                //razorpay_webhook_url
                if (Settings::where('type', 'stripe_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_currency_code)
                    ];
                    Settings::where('type', 'stripe_currency_code')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_currency_code';
                    $setting->message = trim($request->stripe_currency_code);
                    $setting->save();
                }


                $env_update = changeEnv([
                    'STRIPE_PUBLISHABLE_KEY' => trim($request->stripe_publishable_key),
                    'STRIPE_SECRET_KEY' => trim($request->stripe_secret_key),
                    'STRIPE_WEBHOOK_SECRET' => trim($request->stripe_webhook_secret),
                    'STRIPE_WEBHOOK_URL' => trim($request->stripe_webhook_url),
                ]);

                if ($env_update) {
                    $response = array(
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    );
                }
            }

            //paystack_status
            if (Settings::where('type', 'paystack_status')->exists()) {
                $data = [
                    'message' => $request->paystack_status
                ];
                Settings::where('type', 'paystack_status')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'paystack_status';
                $setting->message = $request->paystack_status;
                $setting->save();
            }

            if ($request->paystack_status) {

                //paystack_public_key
                if (Settings::where('type', 'paystack_public_key')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_public_key)
                    ];
                    Settings::where('type', 'paystack_public_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'paystack_public_key';
                    $setting->message = trim($request->paystack_public_key);
                    $setting->save();
                }

                //paystack_secret_key
                if (Settings::where('type', 'paystack_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_secret_key)
                    ];
                    Settings::where('type', 'paystack_secret_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'paystack_secret_key';
                    $setting->message = trim($request->paystack_secret_key);
                    $setting->save();
                }

                //paystack_webhook_url
                if (Settings::where('type', 'paystack_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_webhook_url)
                    ];
                    Settings::where('type', 'paystack_webhook_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'paystack_webhook_url';
                    $setting->message = trim($request->paystack_webhook_url);
                    $setting->save();
                }

                //paystack_webhook_url
                if (Settings::where('type', 'paystack_payment_url')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_payment_url)
                    ];
                    Settings::where('type', 'paystack_payment_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'paystack_payment_url';
                    $setting->message = trim($request->paystack_payment_url);
                    $setting->save();
                }

                //paystack_currency_code
                if (Settings::where('type', 'paystack_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_currency_code)
                    ];
                    Settings::where('type', 'paystack_currency_code')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'paystack_currency_code';
                    $setting->message = trim($request->paystack_currency_code);
                    $setting->save();
                }

                $env_update = changeEnv([
                    'PAYSTACK_PUBLIC_KEY' => trim($request->paystack_public_key),
                    'PAYSTACK_SECRET_KEY' => trim($request->paystack_secret_key),
                    'PAYSTACK_WEBHOOK_URL' => trim($request->paystack_webhook_url),
                    'PAYSTACK_PAYMENT_URL' => trim($request->paystack_payment_url)
                ]);

                if ($env_update) {
                    $response = array(
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    );
                }
            }

            //flutterwave_status
            if (Settings::where('type', 'flutterwave_status')->exists()) {
                $data = [
                    'message' => $request->flutterwave_status
                ];
                Settings::where('type', 'flutterwave_status')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'flutterwave_status';
                $setting->message = $request->flutterwave_status;
                $setting->save();
            }

            if ($request->flutterwave_status) {

                //flutterwave_public_key
                if (Settings::where('type', 'flutterwave_public_key')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_public_key)
                    ];
                    Settings::where('type', 'flutterwave_public_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'flutterwave_public_key';
                    $setting->message = trim($request->flutterwave_public_key);
                    $setting->save();
                }

                //flutterwave_secret_key
                if (Settings::where('type', 'flutterwave_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_secret_key)
                    ];
                    Settings::where('type', 'flutterwave_secret_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'flutterwave_secret_key';
                    $setting->message = trim($request->flutterwave_secret_key);
                    $setting->save();
                }

                // flutterwave_encryption_key
                if (Settings::where('type', 'flutterwave_hash_key')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_hash_key)
                    ];
                    Settings::where('type', 'flutterwave_hash_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'flutterwave_hash_key';
                    $setting->message = trim($request->flutterwave_hash_key);
                    $setting->save();
                }

                //flutterwave_webhook_url
                if (Settings::where('type', 'flutterwave_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_webhook_url)
                    ];
                    Settings::where('type', 'flutterwave_webhook_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'flutterwave_webhook_url';
                    $setting->message = trim($request->flutterwave_webhook_url);
                    $setting->save();
                }

                //flutterwave_currency_code
                if (Settings::where('type', 'flutterwave_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_currency_code)
                    ];
                    Settings::where('type', 'flutterwave_currency_code')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'flutterwave_currency_code';
                    $setting->message = trim($request->flutterwave_currency_code);
                    $setting->save();
                }

                $env_update = changeEnv([
                    'FLW_PUBLIC_KEY' => trim($request->flutterwave_public_key),
                    'FLW_SECRET_KEY' => trim($request->flutterwave_secret_key),
                    'FLW_SECRET_HASH' => trim($request->flutterwave_hash_key),
                    'FLW_WEBHOOK_URL' => trim($request->flutterwave_webhook_url),
                ]);

                if ($env_update) {
                    $response = array(
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    );
                }
            }

            //currency_code
            if (Settings::where('type', 'currency_code')->exists()) {
                $data = [
                    'message' => $request->currency_code
                ];
                Settings::where('type', 'currency_code')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'currency_code';
                $setting->message = $request->currency_code;
                $setting->save();
            }

            //currency_symbol
            if (Settings::where('type', 'currency_symbol')->exists()) {
                $data = [
                    'message' => $request->currency_symbol
                ];
                Settings::where('type', 'currency_symbol')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'currency_symbol';
                $setting->message = $request->currency_symbol;
                $setting->save();
            }

            //currency_symbol
            if (Settings::where('type', 'compulsory_fee_payment_mode')->exists()) {
                $data = [
                    'message' => $request->compulsory_fee_payment_mode
                ];
                Settings::where('type', 'compulsory_fee_payment_mode')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'compulsory_fee_payment_mode';
                $setting->message = $request->compulsory_fee_payment_mode;
                $setting->save();
            }

            //currency_symbol
            if (Settings::where('type', 'is_student_can_pay_fees')->exists()) {
                $data = [
                    'message' => $request->is_student_can_pay_fees
                ];
                Settings::where('type', 'is_student_can_pay_fees')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'is_student_can_pay_fees';
                $setting->message = $request->is_student_can_pay_fees;
                $setting->save();
            }

            // Handle installment fees configuration
            if (isset($request->enable_fees_installment)) {
                if (Settings::where('type', 'enable_fees_installment')->exists()) {
                    $data = [
                        'message' => $request->enable_fees_installment
                    ];
                    Settings::where('type', 'enable_fees_installment')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'enable_fees_installment';
                    $setting->message = $request->enable_fees_installment;
                    $setting->save();
                }
            }

            if (isset($request->default_due_charges)) {
                if (Settings::where('type', 'default_due_charges')->exists()) {
                    $data = [
                        'message' => $request->default_due_charges
                    ];
                    Settings::where('type', 'default_due_charges')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'default_due_charges';
                    $setting->message = $request->default_due_charges;
                    $setting->save();
                }
            }

            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully'),
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function feesTransactionsLogsIndex(Request $request)
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections', 'streams')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        return response(view('fees.fees_transaction_logs', compact('classes', 'mediums', 'session_year_all')));
    }
    public function feesTransactionsLogsList()
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        //Fetching Students Data on Basis of Class Section ID with Realtion fees paid
        $sql = PaymentTransaction::with('student', 'session_year');

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('order_id', 'LIKE', "%$search%")
                ->orwhere('payment_id', 'LIKE', "%$search%")
                ->orWhereHas('student.user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
        }
        if (isset($_GET['session_year_id']) && !empty($_GET['session_year_id'])) {
            $sql = $sql->where('session_year_id', $_GET['session_year_id']);
        }
        if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $sql = $sql->where('class_id', $class_id);
        }
        if (isset($_GET['payment_status']) && $_GET['payment_status'] == 0 || $_GET['payment_status'] == 1 || $_GET['payment_status'] == 2) {
            $sql->where('payment_status', $_GET['payment_status']);
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
            $tempRow['student_id'] = $row->student_id;
            $tempRow['student_name'] = $row->student->user->first_name . ' ' . $row->student->user->last_name;
            $tempRow['total_fees'] = $row->total_amount;
            $tempRow['payment_gateway'] = $row->payment_gateway;
            $tempRow['payment_status'] = $row->payment_status;
            $tempRow['order_id'] = $row->order_id;
            $tempRow['mode'] = $row->mode;
            $tempRow['cheque_no'] = $row->cheque_no;
            $tempRow['payment_id'] = $row->payment_id;
            $tempRow['payment_signature'] = $row->payment_signature;
            $tempRow['session_year_id'] = $row->session_year_id;
            $tempRow['session_year_name'] = $row->session_year->name;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function feesPaidReceiptPDF($id)
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $logo = env('LOGO2');
            $logo = public_path('/storage/' . $logo);
            $school_name = env('APP_NAME');
            $school_address = getSettings('school_address');
            $school_address = $school_address['school_address'];
            $currency_symbol = getSettings('currency_symbol');
            if (isset($currency_symbol) && sizeof($currency_symbol)) {
                $currency_symbol = $currency_symbol['currency_symbol'];
            } else {
                $currency_symbol = null;
            }

            //Getting the Fees Paid Data
            $fees_paid = FeesPaid::where('id', $id)->with('student.user:id,first_name,last_name', 'student.class_section', 'session_year')->get()->first();
            // dd($fees_paid->toArray());
            // Variables
            $student_id = $fees_paid->student_id;
            $class_id = $fees_paid->class_id;
            $session_year_id = $fees_paid->session_year_id;

            $optional_fees_type_id = FeesClass::where(['class_id' => $class_id, 'choiceable' => 1])->pluck('fees_type_id');

            // Paid Installment Data
            $paid_installment = PaidInstallmentFee::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'status' => 1])->with('installment_fee')->get();

            //Fees Choiceable Data
            $fees_choiceable = FeesChoiceable::whereIn('fees_type_id', $optional_fees_type_id)->where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'status' => 1])->with('fees_type')->orderby('id', 'asc')->get();

            //Fees Class Data
            $fees_class = FeesClass::where(['class_id' => $class_id, 'choiceable' => 0])->with('fees_type')->get();

            //Session Year Data
            $session_year = SessionYear::where('id', $session_year_id)->first();

            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'school_name', 'fees_paid', 'paid_installment', 'fees_choiceable', 'currency_symbol', 'school_address', 'fees_class', 'session_year'));
            return $pdf->stream('fees-receipt.pdf');
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
            );
            return response()->json($response);
        }
    }

    // Pay Optional Fees Offline
    public function optionalFeesPaidStore(Request $request)
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'mode' => 'required|in:0,1',
            'session_year_id' => 'required'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        $session_year_id = $request->input('session_year_id');

        try {
            $date = date('Y-m-d H:i:s', strtotime($request->date));

            // Get the Father Id for Payment Transaction Table
            $father_id = Students::where('id', $request->student_id)->pluck('father_id')->first();
            $guardian_id = Students::where('id', $request->student_id)->pluck('guardian_id')->first();

            $user_id = Parents::where('id', $father_id)->pluck('user_id')->first();
            // Add Data in Payment Transaction Of Optional Payment Transaction
            $payment_transaction_store = new PaymentTransaction();
            $payment_transaction_store->student_id = $request->student_id;
            $payment_transaction_store->class_id = $request->class_id;
            $payment_transaction_store->parent_id = $father_id ?? $guardian_id;
            $payment_transaction_store->mode = $request->mode;
            $payment_transaction_store->cheque_no = (isset($request->cheque_no) && !empty($request->cheque_no) && $request->mode == 1) ? $request->cheque_no : null;
            $payment_transaction_store->type_of_fee = 2;
            $payment_transaction_store->payment_status = 1;
            $payment_transaction_store->total_amount = $request->total_amount;
            $payment_transaction_store->session_year_id = $session_year_id;
            $payment_transaction_store->save();


            // Add Data in Array of Optional Fees
            $optional_fees_store = array();
            foreach ($request->optional_fees_type_data as $fees_type_data) {
                if (isset($fees_type_data['id']) && !empty($fees_type_data['id'])) {
                    $optional_fees_store[] = array(
                        'student_id' => $request->student_id,
                        'class_id' => $request->class_id,
                        'fees_type_id' => $fees_type_data['id'],
                        'is_due_charges' => 0,
                        'total_amount' => $fees_type_data['amount'],
                        'session_year_id' => $session_year_id,
                        'date' => $date,
                        'status' => 1,
                        'payment_transaction_id' => $payment_transaction_store->id
                    );
                }
            }

            // Add Data in Fees Choiceable Of Optional Payment
            FeesChoiceable::insert($optional_fees_store);

            // Add Data in Fees Paid Of Optional Payment Transaction
            $update_fees_paid_query = FeesPaid::where(['student_id' => $request->student_id, 'class_id' => $request->class_id, 'session_year_id' => $session_year_id]);

            $fees_paid = $update_fees_paid_query->firstOrNew();
            $fees_paid->total_amount += $request->total_amount;
            $fees_paid->date = $date;
            if (!$fees_paid->exists) {
                $fees_paid->student_id = $request->student_id;
                $fees_paid->class_id = $request->class_id;
                $fees_paid->is_fully_paid = 0;
                $fees_paid->session_year_id = $session_year_id;
            }
            $fees_paid->save();
            $user[] = $user_id;
            $body = 'Amount :- ' . $request->total_amount;
            $type = 'Online';
            $image = null;
            $userinfo = null;

            $notification = new Notification();
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 2;
            $notification->title = 'Payment Success';
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();
            foreach ($user as $data) {
                $user_notification = new UserNotification();
                $user_notification->notification_id = $notification->id;
                $user_notification->user_id = $data;
                $user_notification->save();
            }

            sendSimpleNotification($user, 'Payment Success', $body, $type, $image, $userinfo);

            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function feesPaidRemoveChoiceableFees($id)
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $fees_choiceable = FeesChoiceable::find($id);

            //Get Detials
            $student_id = $fees_choiceable->student_id;
            $class_id = $fees_choiceable->class_id;
            $session_year_id = $fees_choiceable->session_year_id;
            $fees_choiceable_amount = $fees_choiceable->total_amount;
            $fees_payment_transaction_id = $fees_choiceable->payment_transaction_id;

            // Delete Fees Choiceable Entry
            $fees_choiceable->delete();

            // Check the Payment Transaction ID Is Not Null
            if (isset($fees_payment_transaction_id) && !empty($fees_payment_transaction_id)) {
                // Check the Payment Transaction Entry
                $fees_payment_db = PaymentTransaction::where('id', $fees_payment_transaction_id);
                // Get the Payment Transaction Amount
                $fees_transaction_amount = $fees_payment_db->pluck('total_amount')->first();

                // Reduce the amount of Deleted Choiceable Fees in Payment Transaction
                $updated_transaction_fees_amount = $fees_transaction_amount  - $fees_choiceable_amount;

                // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
                if ($updated_transaction_fees_amount != 0) {
                    $fees_transaction_update = PaymentTransaction::find($fees_payment_transaction_id);
                    $fees_transaction_update->total_amount = $updated_transaction_fees_amount;
                    $fees_transaction_update->save();
                } else {
                    $fees_transaction_update = PaymentTransaction::where('id', $fees_payment_transaction_id)->delete();
                }
            }


            // Check the Fees Paid Entry
            $fees_paid_db = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
            // Get the Fees Paid ID
            $fees_paid_id = $fees_paid_db->pluck('id')->first();
            // Get the Fees Paid Amount
            $fees_paid_amount = $fees_paid_db->pluck('total_amount')->first();

            // Reduce the amount of Deleted Choiceable Fees in Fees Paid
            $updated_fees_paid_amount = $fees_paid_amount  - $fees_choiceable_amount;

            // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
            if ($updated_fees_paid_amount != 0) {
                $fees_paid_update = FeesPaid::find($fees_paid_id);
                $fees_paid_update->total_amount = $updated_fees_paid_amount;
                $fees_paid_update->save();
            } else {
                $fees_paid_update = FeesPaid::where('id', $fees_paid_id)->delete();
            }

            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function compulsoryFeesPaidStore(Request $request)
    {
        // dd($request->all());
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'mode' => 'required|in:0,1',
            'session_year_id' => 'required'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }

        $session_year_id = $request->input('session_year_id');

        try {
            $date = date('Y-m-d H:i:s', strtotime($request->date));

            // // Enforce sequential installment payment: earlier installments must be paid first
            // if (isset($request->installment_fees) && is_array($request->installment_fees) && count($request->installment_fees) > 0) {
            //     // Fetch all installments for the class/session ordered by due_date/id
            //         ->orderBy('due_date')
            //         ->orderBy('id')
            //         ->get(['id']);
            //     $orderedIds = $allInstallments->pluck('id')->values();

            //     // Determine the lowest-index installment being paid now
            //     $requestedIds = collect($request->installment_fees)->pluck('id')->filter()->values();
            //     if ($requestedIds->count() > 0) {
            //         $minRequestedIndex = $orderedIds->search($requestedIds->min());
            //         // Ensure all installments before this index are already paid
            //         $requiredPriorIds = $orderedIds->slice(0, $minRequestedIndex)->all();
            //         if (!empty($requiredPriorIds)) {
            //                 ->where(['student_id' => $request->student_id, 'class_id' => $request->class_id, 'session_year_id' => $session_year_id, 'status' => 1])
            //                 ->pluck('installment_fee_id')
            //                 ->all();
            //             $missing = array_diff($requiredPriorIds, $unpaidPrior);
            //             if (!empty($missing)) {
            //                 return response()->json([
            //                     'error' => true,
            //                     'message' => trans('please_pay_previous_installments_first')
            //                 ]);
            //             }
            //         }
            //     }
            // }

            // Get the Father Id for Payment Transaction Table
            $father_id = Students::where('id', $request->student_id)->pluck('father_id')->first();
            $guardian_id = Students::where('id', $request->student_id)->pluck('guardian_id')->first();

            $user_id = Parents::where('id', $father_id)->pluck('user_id')->first();

            // Add Data in Payment Transaction Of Optional Payment Transaction
            $payment_transaction_store = new PaymentTransaction();
            $payment_transaction_store->student_id = $request->student_id;
            $payment_transaction_store->class_id = $request->class_id;
            $payment_transaction_store->parent_id = $father_id ?? $guardian_id;
            $payment_transaction_store->mode = $request->mode;
            $payment_transaction_store->cheque_no = (isset($request->cheque_no) &&
                !empty($request->cheque_no)) ? $request->cheque_no : null;
            $payment_transaction_store->type_of_fee = isset($request->installment_fees) &&
                !empty($request->installment_fees) ? 1 : 0;
            $payment_transaction_store->payment_status = 1;
            $payment_transaction_store->date = $date;
            $payment_transaction_store->total_amount = $request->total_amount;
            $payment_transaction_store->session_year_id = $session_year_id;
            $payment_transaction_store->save();

            $installment_fees_store = [];
            $is_fully_paid = 0;

            if ($request->filled('installment_fees') && is_array($request->installment_fees)) {
                foreach ($request->installment_fees as $data) {
                    if (!empty($data['id'])) {
                        $installment_fees_store[] = [
                            'student_id' => $request->student_id,
                            'parent_id' => $father_id ?? $guardian_id,
                            'class_id' => $request->class_id,
                            'installment_fee_id' => $data['id'],
                            'amount' => $data['amount'],
                            'session_year_id' => $session_year_id,
                            'date' => $date,
                            'status' => 1,
                            'due_charges' => $data['due_charges'] ?? null,
                            'payment_transaction_id' => $payment_transaction_store->id
                        ];
                        $is_fully_paid = $data['fully_paid'] ?? 0;
                    }
                }

                if (!empty($installment_fees_store)) {
                    PaidInstallmentFee::insert($installment_fees_store);
                }
            }


            if ($request->installment_mode == 0) {
                $is_fully_paid = 1;
            }
            // Add Data in Fees Paid Of Optional Payment Transaction
            $update_fees_paid_query = FeesPaid::where([
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'session_year_id' => $session_year_id
            ]);

            $fees_paid = $update_fees_paid_query->firstOrNew();
            $fees_paid->total_amount += $request->total_amount;
            $fees_paid->date = $date;
            $fees_paid->is_fully_paid = $is_fully_paid;

            if ($request->installment_mode == 0) {
                $fees_paid->due_charges = $request->due_charges_whole_year;
            } else {
                $fees_paid->due_charges = null;
            }
            if (!$fees_paid->exists) {
                $fees_paid->student_id = $request->student_id;
                $fees_paid->class_id = $request->class_id;
                $fees_paid->session_year_id = $session_year_id;
            }
            $fees_paid->save();
            $user[] = $user_id;
            $body = 'Amount :- ' . $request->total_amount;
            $type = 'Online';
            $image = null;
            $userinfo = null;

            $notification = new Notification();
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 2;
            $notification->title = 'Payment Success';
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();
            foreach ($user as $data) {
                $user_notification = new UserNotification();
                $user_notification->notification_id = $notification->id;
                $user_notification->user_id = $data;
                $user_notification->save();
            }

            sendSimpleNotification($user, 'Payment Success', $body, $type, $image, $userinfo);

            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully')
            );
        } catch (\Throwable $e) {

            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                $response = array(
                    'error' => false,
                    'message' => "Data Stored successfully. But App push notification not send."
                );
            } else {
                DB::rollBack();
                $response = array(
                    'error' => true,
                    'message' => trans('error_occurred')
                );
            }
        }
        return response()->json($response);
    }

    public function feesPaidRemoveInstallmentFees($id)
    {
        if (!Auth::user()->can('fees-paid')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $paid_installment_fee_db = PaidInstallmentFee::find($id);

            //Get Detials
            $student_id = $paid_installment_fee_db->student_id;
            $class_id = $paid_installment_fee_db->class_id;
            $session_year_id = $paid_installment_fee_db->session_year_id;
            $installment_fee_amount = $paid_installment_fee_db->amount;
            $fees_payment_transaction_id = $paid_installment_fee_db->payment_transaction_id;

            // Delete Fees Installment Paid Entry
            $paid_installment_fee_db->delete();

            // Check the Payment Transaction ID Is Not Null
            if (isset($fees_payment_transaction_id) && !empty($fees_payment_transaction_id)) {
                // Check the Payment Transaction Entry
                $fees_payment_db = PaymentTransaction::where('id', $fees_payment_transaction_id);
                // Get the Payment Transaction Amount
                $fees_transaction_amount = $fees_payment_db->pluck('total_amount')->first();

                // Reduce the amount of Deleted Choiceable Fees in Payment Transaction
                $updated_transaction_fees_amount = $fees_transaction_amount  - $installment_fee_amount;

                // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
                if ($updated_transaction_fees_amount != 0) {
                    $fees_transaction_update = PaymentTransaction::find($fees_payment_transaction_id);
                    $fees_transaction_update->total_amount = $updated_transaction_fees_amount;
                    $fees_transaction_update->save();
                } else {
                    $fees_transaction_update = PaymentTransaction::where('id', $fees_payment_transaction_id)->delete();
                }
            }


            // Check the Fees Paid Entry
            $fees_paid_db = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
            // Get the Fees Paid ID
            $fees_paid_id = $fees_paid_db->pluck('id')->first();
            // Get the Fees Paid Amount
            $fees_paid_amount = $fees_paid_db->pluck('total_amount')->first();

            // Reduce the amount of Deleted Choiceable Fees in Fees Paid
            $updated_fees_paid_amount = $fees_paid_amount  - $installment_fee_amount;

            // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
            if ($updated_fees_paid_amount != 0) {
                $fees_paid_update = FeesPaid::find($fees_paid_id);
                $fees_paid_update->total_amount = $updated_fees_paid_amount;
                $fees_paid_update->is_fully_paid = 0;
                $fees_paid_update->save();
            } else {
                $fees_paid_update = FeesPaid::where('id', $fees_paid_id)->delete();
            }

            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
}
