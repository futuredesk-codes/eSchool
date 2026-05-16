<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $classes = ClassSchool::with('medium', 'streams')->get();
        $subjects = Subject::orderBy('id', 'DESC')->get();
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();

        return response(view('exams.index', compact('classes', 'subjects', 'session_year_all')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|array',
            'class_id.*' => 'required|exists:classes,id',
            'name' => 'required',
            'session_year_id' => 'required|exists:session_years,id',
            'description' => 'nullable',
            'semester_id' => 'nullable|exists:semesters,id',
        ], [
            'class_id.required' => 'Please select at least one class.',
            'class_id.array' => 'Class selection must be an array.',
            'class_id.*.required' => 'Class selection is required.',
            'class_id.*.exists' => 'Selected class is invalid.',
            'session_year_id.exists' => 'Selected session year is invalid.',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $exam = new Exam;
            $exam->name = trim($request->name);
            $exam->description = trim($request->description ?? '');
            $exam->session_year_id = $request->session_year_id;
            $exam->semester_id = $request->semester_id ?: null;
            $exam->save();

            if ($request->class_id && is_array($request->class_id)) {
                $exam_classes = [];
                foreach ($request->class_id as $class_id) {
                    // Verify class exists
                    $classExists = ClassSchool::find($class_id);
                    if (! $classExists) {
                        throw new \Exception("Class with ID {$class_id} does not exist.");
                    }

                    $exam_classes[] = [
                        'exam_id' => $exam->id,
                        'class_id' => $class_id,
                        'session_year_id' => $request->session_year_id,
                    ];
                }

                if (! empty($exam_classes)) {
                    ExamClass::insert($exam_classes);
                }
            }

            DB::commit();
            ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show()
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';
        $session_year_id = $_GET['session_year_id'];

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

        $sql = Exam::where('session_year_id', $session_year_id)
            ->with('exam_classes.class.medium', 'exam_classes.class.streams', 'session_year', 'timetable');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%");
                $timestamp = strtotime($search);
                if ($timestamp !== false) {
                    $date = date('Y-m-d H:i:s', $timestamp);

                    $query->orWhere('created_at', 'LIKE', "%$date%")
                        ->orWhere('updated_at', 'LIKE', "%$date%");
                }
                $query->orWhereHas('session_year', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                });
            });
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $examIds = $res->pluck('id')->toArray();
        $examsWithTimetables = ExamTimetable::whereIn('exam_id', $examIds)->pluck('exam_id')->unique()->toArray();
        $examsWithResults = ExamResult::whereIn('exam_id', $examIds)->pluck('exam_id')->unique()->toArray();
        $examsWithClasses = ExamClass::whereIn('exam_id', $examIds)->pluck('exam_id')->unique()->toArray();

        $examTimetableIds = ExamTimetable::whereIn('exam_id', $examIds)->pluck('id')->toArray();
        $examsWithMarks = ! empty($examTimetableIds)
            ? ExamMarks::whereIn('exam_timetable_id', $examTimetableIds)
                ->join('exam_timetables', 'exam_marks.exam_timetable_id', '=', 'exam_timetables.id')
                ->pluck('exam_timetables.exam_id')->unique()->toArray()
            : [];

        foreach ($res as $row) {
            $operate = '';
            if ($row->publish == 0) {
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-success btn-rounded btn-icon publish-exam-result" data-id='.$row->id.' title="Publish Exam Result"><i class="fa fa-check-circle"></i></a>&nbsp;&nbsp;';
            } else {
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-warning btn-rounded btn-icon publish-exam-result" data-id='.$row->id.' title="Unpublish Exam Result"><i class="fa fa-times-circle"></i></a>&nbsp;&nbsp;';
            }
            if (count($row->timetable)) {
                foreach ($row->exam_classes as $data) {
                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $data->exam_id, 'class_id' => $data->class_id])->first();
                    $starting_date = $starting_date_db['min(date)'];
                    $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $data->exam_id, 'class_id' => $data->class_id])->first();
                    $ending_date = $ending_date_db['max(date)'];
                    $currentTime = Carbon::now();
                    $current_date = date($currentTime->toDateString());
                    if ($current_date >= $starting_date && $current_date <= $ending_date) {
                        $exam_status = '1'; // Upcoming = 0 , On Going = 1 , Completed = 2
                    } elseif ($current_date < $starting_date) {
                        $exam_status = '0'; // Upcoming = 0 , On Going = 1 , Completed = 2
                    } else {
                        $exam_status = '2'; // Upcoming = 0 , On Going = 1 , Completed = 2
                    }
                }
            }
            if (isset($exam_status)) {
                if ($exam_status == 0) {
                    $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                }
            } else {
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            }
            $relatedData = [];
            if (in_array($row->id, $examsWithTimetables)) {
                $relatedData[] = 'exam timetables';
            }
            if (in_array($row->id, $examsWithMarks)) {
                $relatedData[] = 'exam marks';
            }
            if (in_array($row->id, $examsWithResults)) {
                $relatedData[] = 'exam results';
            }
            if (in_array($row->id, $examsWithClasses)) {
                $relatedData[] = 'exam classes';
            }

            $operate .= '
                <a
                    href="'.route('exams.destroy', $row->id).'"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    data-id="'.$row->id.'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            $tempRow['class_name'] = [];
            foreach ($row->exam_classes as $exam_class) {
                $tempRow['class_name'][] = $exam_class->class->name.'-'.$exam_class->class->medium->name.' '.($exam_class->class->streams->name ?? '');
            }
            $tempRow['class_id'] = $row->exam_classes->pluck('class.id');
            $tempRow['session_year_name'] = $row->session_year->name;
            $tempRow['timetable'] = $row->timetable;
            $tempRow['publish'] = $row->publish;
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
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'class_id' => 'required|array',
            'class_id.*' => 'required|exists:class_sections,id',
        ], [
            'class_id.required' => 'The class field is required.',
            'class_id.*.required' => 'The class field is required.',
            'class_id.*.exists' => 'The selected class is invalid.',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $exam = Exam::with('exam_classes')->find($id);
            $exam->name = $request->name;
            $exam->description = $request->description;
            $exam->semester_id = $request->semester_id ?: null;
            $exam->save();

            $all_exam_classes_id = ExamClass::whereIn('class_id', $request->class_id)
                ->where('exam_id', $request->edit_id)->pluck('class_id')->toArray();
            $delete_exam_classes = $exam->exam_classes->pluck('class_id')->toArray();
            $exam_classes = [];

            foreach ($request->class_id as $class_id) {
                if (! in_array($class_id, $all_exam_classes_id)) {
                    $exam_classes[] = [
                        'exam_id' => $exam->id,
                        'class_id' => $class_id,
                        'session_year_id' => $exam->session_year_id,
                    ];
                } else {
                    unset($delete_exam_classes[array_search($class_id, $delete_exam_classes)]);
                }
            }
            ExamClass::insert($exam_classes);

            // //Remaining Data in $all_exam_classes_id should be deleted
            ExamClass::whereIn('class_id', $delete_exam_classes)->where('exam_id', $id)->delete();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('exam-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $exam = Exam::findOrFail($id);

            // Cascade handled in model
            $exam->delete();

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

    public function publishExamResult($id)
    {
        try {
            $exam_marks_db = ExamTimetable::where('exam_id', $id)->with('exam_marks')->get();

            foreach ($exam_marks_db as $data) {
                if ($data->exam_marks->count() == 0) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('marks_are_not_submitted'),
                    ]);
                }
            }

            $exam = Exam::with([
                'marks' => function ($query) {
                    $query->selectRaw('
                        SUM(total_marks) as total_marks,
                        SUM(obtained_marks) as total_obtained_marks,
                        student_id
                    ')
                        ->groupBy('student_id');
                },
                'timetable' => function ($query) {
                    $query->selectRaw('exam_id')
                        ->groupBy('class_id');
                },
            ])
                ->with('exam_classes')
                ->where('id', $id)
                ->first();

            if (! $exam) {
                return response()->json([
                    'error' => true,
                    'message' => trans('exam_not_found'),
                ]);
            }

            foreach ($exam->exam_classes as $data) {

                $starting_date = ExamTimetable::where([
                    'exam_id' => $data->exam_id,
                    'class_id' => $data->class_id,
                ])
                    ->min('date');

                $ending_date = ExamTimetable::where([
                    'exam_id' => $data->exam_id,
                    'class_id' => $data->class_id,
                ])
                    ->max('date');

                $current_date = Carbon::now()->toDateString();

                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = 1; // Ongoing
                } elseif ($current_date < $starting_date) {
                    $exam_status = 0; // Upcoming
                } else {
                    $exam_status = 2; // Completed
                }

                break;
            }

            $size_of_timetable_array = $exam->timetable->count();
            $size_of_marks_array = $exam->marks->count();

            if (
                $exam_status == 2 &&
                $size_of_timetable_array != 0 &&
                $size_of_marks_array != 0
            ) {

                if ($exam->publish == 0) {

                    $studentIds = $exam->marks
                        ->pluck('student_id')
                        ->unique()
                        ->toArray();

                    /**
                     * Fetch class_section_id from student_sessions
                     */
                    $studentSessions = StudentSessions::whereIn('student_id', $studentIds)
                        ->where('session_year_id', $exam->session_year_id)
                        ->get()
                        ->keyBy('student_id');

                    $exam_result = [];

                    foreach ($exam->marks as $exam_marks) {

                        $percentage = (
                            $exam_marks->total_obtained_marks * 100
                        ) / $exam_marks->total_marks;

                        $grade = findExamGrade(
                            $exam->session_year_id,
                            $percentage
                        );

                        if ($grade == null) {

                            return response()->json([
                                'error' => true,
                                'message' => trans('grades_data_does_not_exists'),
                            ]);
                        }

                        $studentSession = $studentSessions->get($exam_marks->student_id);

                        if (! $studentSession) {

                            return response()->json([
                                'error' => true,
                                'message' => 'Student session not found.',
                            ]);
                        }

                        $exam_result[] = [
                            'exam_id' => $exam->id,
                            'class_section_id' => $studentSession->class_section_id,
                            'student_id' => $exam_marks->student_id,
                            'total_marks' => $exam_marks->total_marks,
                            'obtained_marks' => $exam_marks->total_obtained_marks,
                            'percentage' => round($percentage, 2),
                            'grade' => $grade,
                            'session_year_id' => $exam->session_year_id,
                        ];
                    }

                    ExamResult::insert($exam_result);

                    $exam->publish = 1;

                } else {

                    ExamResult::where('exam_id', $id)->delete();

                    $exam->publish = 0;
                }

                $exam->save();

                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                ];

            } else {

                $response = [
                    'error' => true,
                    'message' => trans('exam_not_completed_yet'),
                ];
            }
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    // public function uploadMarks()
    // {
    //     if (! Auth::user()->can('exam-upload-marks')) {
    //         $response = [
    //             'message' => trans('no_permission_message'),
    //         ];

    //         return redirect(route('home'))->withErrors($response);
    //     }

    //     $session_year_id = getSettings('session_year')['session_year'];
    //     $currentSemesterId = Semester::where('session_year_id', $session_year_id)->get()->first(function ($semester) {
    //         return $semester->current;
    //     })->id;

    //     $teacher_id = Auth::user()->teacher->id;

    //     $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)
    //         ->where('session_year_id', $session_year_id)
    //         ->where(function ($query) use ($currentSemesterId) {

    //             $query->whereNull('semester_id');

    //             if ($currentSemesterId) {
    //                 $query->orWhere('semester_id', $currentSemesterId);
    //             }
    //         })
    //         ->pluck('class_section_id');

    //     $class_ids = ClassSection::whereIn('id', $class_section_id)
    //         ->pluck('class_id');

    //     $classes = ClassSection::with(
    //         'class',
    //         'section',
    //         'class.medium',
    //         'streams'
    //     )
    //         ->whereIn('id', $class_section_id)
    //         ->whereIn('class_id', $class_ids)
    //         ->get();

    //     return response(view('exams.upload-marks', compact('classes')));
    // }

    public function uploadMarks()
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $session_year_id = getSettings('session_year')['session_year'];
        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('class_section_id');
        $class_ids = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
        $classes = ClassSection::with('class', 'section', 'class.medium', 'streams')
            ->whereIn('id', $class_section_id)->whereIn('class_id', $class_ids)->get();

        return response(view('exams.upload-marks', compact('classes')));
    }

    public function getExamSubjects($exam_id)
    {
        try {
            $teacher_id = Auth::user()->teacher->id;
            $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
            $class_id = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
            $subjects = ExamTimetable::with('subject')->where('exam_id', $exam_id)->whereIn('class_id', $class_id)->get();
            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $subjects,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function marksList(Request $request)
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        if (! $request->exam_id || ! $request->subject_id || ! $request->class_id || ! $request->class_section_id) {
            return false;
        }
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

        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = ClassSection::where('id', $request->class_section_id)->where('class_id', $request->class_id)->pluck('id');

        $exam = Exam::findOrFail($request->exam_id);
        $session_year_id = $exam->session_year_id;

        $exam_timetable_id = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('id')->first();

        $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id])->first();
        $starting_date = $starting_date_db['min(date)'];
        $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id])->first();
        $ending_date = $ending_date_db['max(date)'];
        $currentTime = Carbon::now();
        $current_date = date($currentTime->toDateString());
        if ($current_date >= $starting_date && $current_date <= $ending_date) {
            $exam_status = '1'; // Upcoming = 0 , On Going = 1 , Completed = 2
        } elseif ($current_date < $starting_date) {
            $exam_status = '0'; // Upcoming = 0 , On Going = 1 , Completed = 2
        } else {
            $exam_status = '2'; // Upcoming = 0 , On Going = 1 , Completed = 2
        }

        if ($exam_status != 2) {
            $response = [
                'error' => true,
                'message' => trans('exam_not_completed_yet'),
            ];

            return response()->json($response);
        }

        // Fetching Students Data on Basis of Class Section ID and Session Year with Relation Exam Marks
        $studentIds = StudentSessions::where('class_section_id', $class_section_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('student_id');

        $sql = Students::with(['user:id,first_name,last_name'])->with(['class_section.class.allSubjects' => function ($q) use ($request) {
            $q->where('subject_id', $request->subject_id)->with('subject');
        }])->with(['exam_marks' => function ($q) use ($exam_timetable_id) {
            $q->where('exam_timetable_id', $exam_timetable_id);
        }])->whereIn('id', $studentIds);

        $subject_total_marks = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('total_marks');

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%")
                    ->orWhere('mobile', 'LIKE', "%$search%");
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        $class_subject = ClassSubject::where('subject_id', $request->subject_id)->where('class_id', $request->class_id)->where('session_year_id', $session_year_id)->first();

        foreach ($res as $row) {
            if ($class_subject == null) {
                continue;
            }

            if ($class_subject->type == 'Elective') {
                $student_subject = StudentSubject::where('student_id', $row->id)->where('subject_id', $request->subject_id)->where('class_section_id', $row->class_section_id)->where('session_year_id', $session_year_id)->first();
                if ($student_subject) {
                    $operate = '<a href='.route('exams.edit', $row->id).' class="btn btn-xs btn-secondary btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                    $operate .= '<a href='.route('exams.destroy', $row->id).' class="btn btn-xs btn-secondary btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';
                    $tempRow['id'] = $row->id;
                    $tempRow['no'] = $no++;
                    $tempRow['student_name'] = $row->user->first_name.' '.$row->user->last_name;
                    $tempRow['student_id'] = $row->id;
                    foreach ($subject_total_marks as $total_marks) {
                        $tempRow['total_marks'] = $total_marks;
                    }
                    foreach ($row->exam_marks as $exam_result) {
                        $tempRow['exam_marks_id'] = $exam_result ? $exam_result->id : '';
                        $tempRow['obtained_marks'] = $exam_result ? $exam_result->obtained_marks : '';
                    }
                    $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
                    $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
                    $tempRow['operate'] = $operate;
                    $rows[] = $tempRow;
                }
            } else {
                $operate = '<a href='.route('exams.edit', $row->id).' class="btn btn-xs btn-secondary btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                $operate .= '<a href='.route('exams.destroy', $row->id).' class="btn btn-xs btn-secondary btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';
                $tempRow['id'] = $row->id;
                $tempRow['no'] = $no++;
                $tempRow['student_name'] = $row->user->first_name.' '.$row->user->last_name;
                $tempRow['student_id'] = $row->id;
                foreach ($subject_total_marks as $total_marks) {
                    $tempRow['total_marks'] = $total_marks;
                }
                foreach ($row->exam_marks as $exam_result) {

                    $tempRow['exam_marks_id'] = $exam_result ? $exam_result->id : '';
                    $tempRow['obtained_marks'] = $exam_result ? $exam_result->obtained_marks : '';
                }
                $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
                $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function submitMarks(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|numeric',
            'class_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'exam_marks.*.student_id' => 'required|numeric',
            'exam_marks.*.obtained_marks' => 'required|numeric|lte:exam_marks.*.total_marks',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        try {
            $teacher_id = Auth::user()->teacher->id;
            $exam_timetable = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id])->where('subject_id', $request->subject_id)->firstOrFail();

            foreach ($request->exam_marks as $exam_marks) {
                $passing_marks = $exam_timetable->passing_marks;
                if ($exam_marks['obtained_marks'] >= $passing_marks) {
                    $status = 1;
                } else {
                    $status = 0;
                }
                $marks_percentage = ($exam_marks['obtained_marks'] / $exam_marks['total_marks']) * 100;
                $exam_grade = findExamGrade($exam_timetable->session_year_id, $marks_percentage);

                if ($exam_grade == null) {
                    $response = [
                        'error' => true,
                        'message' => trans('grades_data_does_not_exists'),
                    ];

                    return response()->json($response);
                }

                ExamMarks::updateOrInsert(
                    ['id' => isset($exam_marks['exam_marks_id']) ? $exam_marks['exam_marks_id'] : null],
                    ['exam_timetable_id' => $exam_timetable->id, 'student_id' => $exam_marks['student_id'], 'subject_id' => $request->subject_id, 'obtained_marks' => $exam_marks['obtained_marks'], 'passing_status' => $status, 'session_year_id' => $exam_timetable->session_year_id, 'grade' => $exam_grade]
                );
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e,
            ];
        }

        return response()->json($response);
    }

    public function getSubjectByExam($class_id, $exam_id)
    {
        try {
            $exam_timetable = ExamTimetable::with('subject')->where('class_id', $class_id)->where('exam_id', $exam_id)->get();
            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exam_timetable,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function deleteTimetable($id)
    {
        try {
            $exam_timetable = ExamTimetable::find($id);
            $exam_timetable->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function viewExamResult()
    {
        if (! Auth::user()->can('view-exam-result')) {
            return redirect(route('home'))->withErrors(['message' => trans('no_permission_message')]);
        }

        $currentSessionYearId = getSettings('session_year')['session_year'] ?? null;
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        $allowedSemesterMap = [];

        if (Auth::user()->hasRole('Super Admin')) {
            $classes = ClassSection::with([
                'class', 'section', 'class.medium', 'class.streams', 'streams',
                'class.allSubjects' => fn ($q) => $q
                    ->select('id', 'class_id', 'semester_id', 'session_year_id')
                    ->whereNotNull('semester_id')
                    ->when($currentSessionYearId, fn ($q) => $q->where('session_year_id', $currentSessionYearId)),
                'class.allSubjects.semester:id,name',
            ])->get();

            $allowedSemesterMap = $classes->mapWithKeys(fn ($cs) => [
                $cs->id => $cs->class->allSubjects
                    ->pluck('semester')
                    ->filter()
                    ->unique('id')
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                    ->values()
                    ->toArray(),
            ])->filter()->toArray();

        } else {
            $teacher_id = Auth::user()->teacher->id;

            $classTeacherRecords = ClassTeacher::with('semester:id,name')
                ->where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $currentSessionYearId)
                ->get();

            $classes = ClassSection::with(['class', 'section', 'class.medium', 'class.streams', 'streams'])
                ->whereIn('id', $classTeacherRecords->pluck('class_section_id')->unique())
                ->get();

            $allowedSemesterMap = $classTeacherRecords
                ->filter(fn ($r) => ! is_null($r->semester_id) && $r->semester)
                ->groupBy('class_section_id')
                ->map(fn ($group) => $group
                    ->map(fn ($r) => ['id' => $r->semester_id, 'name' => $r->semester->name])
                    ->unique('id')
                    ->values()
                    ->toArray()
                )
                ->toArray();
        }

        $classOptions = $classes->map(fn ($d) => [
            'id' => $d->id,
            'class_id' => $d->class->id,
            'label' => trim(
                $d->class->name.' - '.$d->section->name.' '.
                $d->class->medium->name.' '.
                ($d->class->streams->name ?? '')
            ),
        ]);

        return view('exams.view-result', compact(
            'classes', 'session_years', 'currentSessionYearId', 'allowedSemesterMap', 'classOptions'
        ));
    }

    public function getClassOptionsForSession(Request $request)
    {
        $sessionYearId = $request->session_year_id;
        $allowedSemesterMap = [];

        if (Auth::user()->hasRole('Super Admin')) {
            $classes = ClassSection::with([
                'class', 'section', 'class.medium', 'class.streams', 'streams',
                'class.allSubjects' => fn ($q) => $q
                    ->select('id', 'class_id', 'semester_id', 'session_year_id')
                    ->whereNotNull('semester_id')
                    ->where('session_year_id', $sessionYearId),
                'class.allSubjects.semester:id,name',
            ])->get();

            $allowedSemesterMap = $classes->mapWithKeys(fn ($cs) => [
                $cs->id => $cs->class->allSubjects
                    ->pluck('semester')->filter()->unique('id')
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                    ->values()->toArray(),
            ])->filter()->toArray();

        } else {
            $teacher_id = Auth::user()->teacher->id;

            $classTeacherRecords = ClassTeacher::with('semester:id,name')
                ->where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $sessionYearId)
                ->get();

            $classes = ClassSection::with(['class', 'section', 'class.medium', 'class.streams', 'streams'])
                ->whereIn('id', $classTeacherRecords->pluck('class_section_id')->unique())
                ->get();

            $allowedSemesterMap = $classTeacherRecords
                ->filter(fn ($r) => ! is_null($r->semester_id) && $r->semester)
                ->groupBy('class_section_id')
                ->map(fn ($group) => $group
                    ->map(fn ($r) => ['id' => $r->semester_id, 'name' => $r->semester->name])
                    ->unique('id')->values()->toArray()
                )->toArray();
        }

        $classOptions = $classes->map(fn ($d) => [
            'id' => $d->id,
            'class_id' => $d->class->id,
            'label' => trim(
                $d->class->name.' - '.$d->section->name.' '.
                $d->class->medium->name.' '.
                ($d->class->streams->name ?? '')
            ),
        ]);

        return response()->json([
            'classes' => $classOptions,
            'allowedSemesterMap' => $allowedSemesterMap,
        ]);
    }

    public function indexGrades()
    {
        if (! Auth::user()->can('grade-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        $grades = collect();

        return response(view('exams.exam-grade', compact('grades', 'session_years')));
    }

    public function getGradesBySessionYear($sessionYearId)
    {
        $grades = Grade::where('session_year_id', $sessionYearId)
            ->orderBy('starting_range')
            ->get([
                'id',
                'starting_range',
                'ending_range',
                'grade',
            ]);

        return response()->json([
            'success' => true,
            'grades' => $grades,
        ]);
    }

    public function createGrades(Request $request)
    {
        if (! Auth::user()->can('grade-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'grade.*.starting_range' => 'required|numeric|between:0,100',
            'grade.*.ending_range' => 'required|numeric|between:0,100|gt:grade.*.starting_range',
            'grade.*.grades' => 'required',
            'session_year_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            foreach ($request->grade as $grade) {
                Grade::updateOrInsert(
                    ['id' => isset($grade['id']) ? $grade['id'] : null],
                    [
                        'starting_range' => $grade['starting_range'],
                        'ending_range' => $grade['ending_range'],
                        'grade' => $grade['grades'],
                        'session_year_id' => $request->session_year_id,
                    ]
                );
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function destroyGrades($id)
    {
        if (! Auth::user()->can('grade-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $grade = Grade::find($id);
            $grade->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getExamResultIndex()
    {
        if (! Auth::user()->can('exam-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_ids = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
        $classes = ClassSection::with('class', 'section', 'class.medium', 'streams')->whereIn('id', $class_section_id)
            ->whereIn('class_id', $class_ids)->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        // $exams = Exam::where('publish', 1)->get();

        return view('exams.show_exam_result', compact('classes', 'session_years', 'class_section_id'));
    }

    public function fetchExamResultList(Request $request)
    {
        if (! Auth::user()->can('view-exam-result')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
                'total' => 0,
                'rows' => [],
            ]);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'session_year_id' => 'required|numeric',
            'semester_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'total' => 0,
                'rows' => [],
            ]);
        }

        try {
            $offset = $request->get('offset', 0);
            $limit = $request->get('limit', 10);
            $sort = $request->get('sort', 'id');
            $order = $request->get('order', 'DESC');
            $search = $request->get('search');

            $examResults = ExamResult::with('exam')
                ->where([
                    'exam_id' => $request->exam_id,
                    'class_section_id' => $request->class_section_id,
                    'session_year_id' => $request->session_year_id,
                ])
                ->when($request->semester_id, function ($query) use ($request) {
                    $query->whereHas('exam', function ($q) use ($request) {
                        $q->where('semester_id', $request->semester_id);
                    });
                })
                ->get()
                ->keyBy('student_id');

            $sql = StudentSessions::with([
                'student.user',
                'class_section.class.medium',
                'class_section.class.streams',
                'class_section.section',
            ])
                ->where('session_year_id', $request->session_year_id)
                ->where('class_section_id', $request->class_section_id);

            /* ---------- SEARCH ---------- */
            if (! empty($search)) {
                $sql->whereHas('student', function ($q) use ($search) {
                    $q->where('admission_no', 'LIKE', "%$search%")
                        ->orWhere('roll_number', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhereRaw("concat(first_name,' ',last_name) LIKE ?", ["%$search%"])
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        });
                });
            }

            $total = $sql->count();

            $results = $sql->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get();

            /* ---------- RESPONSE ---------- */
            $rows = [];
            $no = 1;
            $dateFormat = getSettings('date_formate');

            foreach ($results as $session) {

                $student = $session->student;
                if (! $student) {
                    continue;
                }

                $exam = $examResults->get($student->id);

                $operate = '';
                if (Auth::user()->can('generate-result')) {
                    $operate = '<a href="'.route('generate.exam.result', [
                        'id' => $student->id,
                        'semester_id' => $request->semester_id,
                        'exam_id' => $request->exam_id,
                        'session_year_id' => $request->session_year_id,
                    ]).'" 
                        class="btn btn-xs btn-gradient-success btn-rounded btn-icon" 
                        data-id="'.$student->id.'" title="Generate Result">
                        <i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                }

                $rows[] = [
                    'id' => $student->id,
                    'no' => $no++,
                    'user_id' => $student->user_id,
                    'student_name' => $student->user->first_name.' '.$student->user->last_name,
                    'admission_no' => $student->admission_no,
                    'total_marks' => $exam->total_marks ?? null,
                    'obtained_marks' => $exam->obtained_marks ?? null,
                    'grade' => $exam->grade ?? null,
                    'percentage' => $exam->percentage ?? null,
                    'operate' => $operate,
                ];
            }

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'total' => 0,
                'rows' => [],
            ]);
        }
    }

    public function showExamResult(Request $request)
    {
        if (! Auth::user()->can('exam-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Validate required parameters
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'session_year_id' => 'required|numeric',
        ], [
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'Selected exam does not exist.',
            'class_section_id.required' => 'Please select a class section.',
            'class_section_id.exists' => 'Selected class section does not exist.',
            'session_year_id.required' => 'Please select a session year.',
        ]);

        if ($validator->fails()) {
            // Sagar : Commented this code to prevent popup on Page load in teacher panel
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'total' => 0,
                'rows' => [],
            ]);
        }

        // Verify teacher has access to this class section
        $teacher_id = Auth::user()->teacher->id;
        $teacherClassSections = ClassTeacher::where('class_teacher_id', $teacher_id)
            ->pluck('class_section_id')
            ->toArray();

        if (! in_array($request->class_section_id, $teacherClassSections)) {
            return response()->json([
                'error' => true,
                'message' => 'You do not have access to this class section.',
                'total' => 0,
                'rows' => [],
            ]);
        }

        try {
            if ($request->exam_id) {
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

                $exam_timetable_id = ExamTimetable::where('session_year_id', $request->session_year_id)
                    ->where('exam_id', $request->exam_id)->pluck('id');

                if ($exam_timetable_id->isEmpty()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'No exam timetable found for the selected exam.',
                        'total' => 0,
                        'rows' => [],
                    ]);
                }

                $sql = ExamResult::where('session_year_id', $request->session_year_id)
                    ->with('student.user:id,first_name,last_name', 'session_year:id,name')
                    ->with(['student.exam_marks' => function ($q) use ($exam_timetable_id) {
                        $q->whereIn('exam_timetable_id', $exam_timetable_id)->with('timetable', 'subject:id,name');
                    }])
                    ->where(['exam_id' => $request->exam_id, 'class_section_id' => $request->class_section_id]);

                if (isset($_GET['search']) && ! empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $sql = $sql->where('id', 'LIKE', "%$search%")
                        ->orwhere('total_marks', 'LIKE', "%$search%")
                        ->orwhere('grade', 'LIKE', "%$search%")
                        ->orwhere('obtained_marks', 'LIKE', "%$search%")
                        ->orwhere('percentage', 'LIKE', "%$search%")
                        ->orwhere('created_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                        ->orwhere('updated_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                        ->orWhereHas('student.user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                        })->where('exam_id', $request->exam_id)
                        ->orWhereHas('session_year', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        });
                }
                $total = $sql->count();

                $sql->orderBy($sort, $order)->skip($offset)->take($limit);
                $res = $sql->get();

                $bulkData = [];
                $bulkData['total'] = $total;
                $rows = [];
                $tempRow = [];
                $no = 1;
                foreach ($res as $row) {
                    $operate = '';
                    $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id="'.$row->id.'" data-student_id ="'.$row->student_id.'" title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

                    $tempRow['id'] = $row->id;
                    $tempRow['no'] = $no++;
                    $tempRow['student_id'] = $row->student_id;
                    $tempRow['student_name'] = $row->student->user->first_name.' '.$row->student->user->last_name;
                    $tempRow['total_marks'] = $row->total_marks;
                    $tempRow['obtained_marks'] = $row->obtained_marks;
                    $tempRow['percentage'] = $row->percentage;
                    $tempRow['grade'] = $row->grade;
                    $tempRow['session_year_name'] = $row->session_year->name;
                    $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
                    $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
                    $tempRow['operate'] = $operate;
                    $tempRow['data'] = $row->student->exam_marks;
                    $rows[] = $tempRow;
                }

                $bulkData['rows'] = $rows;

                return response()->json($bulkData);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Exam ID is required.',
                    'total' => 0,
                    'rows' => [],
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error loading exam results: '.$e->getMessage(),
                'total' => 0,
                'rows' => [],
            ]);
        }
    }

    public function updateExamResultMarks(Request $request)
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'edit.*.marks_id' => 'required|numeric',
            'edit.*.obtained_marks' => 'required|numeric|lte:edit.*.total_marks',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $teacher_id = Auth::user()->teacher->id;

            foreach ($request->edit as $data) {
                $class_id = ExamClass::where('exam_id', $data['exam_id'])->pluck('class_id')->first();
                $marks_db = ExamMarks::find($data['marks_id']);
                $marks_db->obtained_marks = $data['obtained_marks'];

                $passing_marks = $data['passing_marks'];
                if ($data['obtained_marks'] >= $passing_marks) {
                    $marks_db->passing_status = 1;
                } else {
                    $marks_db->passing_status = 0;
                }

                $marks_percentage = ($data['obtained_marks'] / $data['total_marks']) * 100;
                $results = ExamResult::where('exam_id', $data['exam_id'])
                    ->where('student_id', $data['student_id'])
                    ->get(['id', 'session_year_id']);

                $exam_result_id = $results->pluck('id');
                $session_year_id = $results->pluck('session_year_id')->first();

                $grade = findExamGrade($session_year_id, $marks_percentage);
                if ($grade == null) {
                    $response = [
                        'error' => true,
                        'message' => trans('grades_data_does_not_exists'),
                    ];

                    return response()->json($response);
                }
                $marks_db->grade = $grade;
                $marks_db->save();

                // $exam_result_id = ExamResult::where(['exam_id' => $data['exam_id'], 'student_id' => $data['student_id']])->pluck('id');

                $exam = Exam::with(['marks' => function ($query) use ($data) {
                    $query->with('student:id,class_section_id')->selectRaw('SUM(obtained_marks) as total_obtained_marks,student_id')->where('student_id', $data['student_id'])->groupBy('student_id');
                }, 'timetable' => function ($query) use ($data, $class_id) {
                    $query->selectRaw('exam_id,SUM(total_marks) as total_marks')->where(['exam_id' => $data['exam_id'], 'class_id' => $class_id]);
                }])->where('id', $data['exam_id'])->first();

                foreach ($exam->marks as $exam_marks) {
                    $percentage = ($exam_marks['total_obtained_marks'] * 100) / $exam->timetable[0]['total_marks'];

                    $grade = findExamGrade($exam->session_year_id, $percentage);
                    if ($grade == null) {
                        $response = [
                            'error' => true,
                            'message' => trans('grades_data_does_not_exists'),
                        ];

                        return response()->json($response);
                    }

                    $exam_result_db = ExamResult::find($exam_result_id)->first();
                    $exam_result_db->obtained_marks = $exam_marks['total_obtained_marks'];
                    $exam_result_db->percentage = round($percentage, 2);
                    $exam_result_db->grade = $grade;
                    $exam_result_db->save();

                    $response = [
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    ];
                }
            }
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getExamByClass($class_id)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'class_section_id' => 'required|integer|exists:class_sections,id',
                'isPublish' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ]);
            }

            $class_section_id = request('class_section_id');

            // Default false
            $isPublish = request()->has('isPublish')
                ? request('isPublish')
                : false;

            $exams = [];
            $session_year_id = getSettings('session_year')['session_year'];

            $teacher_id = Auth::user()->teacher->id;

            // Check whether this class has semester configuration
            $hasSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            // Verify teacher has access to this class section
            $classTeacherQuery = ClassTeacher::where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id)
                ->where('class_section_id', $class_section_id);

            // Teacher semester access for this section
            $teacherSemesterIds = $classTeacherQuery
                ->whereNotNull('semester_id')
                ->pluck('semester_id')
                ->unique()
                ->toArray();

            $exam_data = Exam::with([
                'exam_classes' => function ($q) use ($class_id) {
                    $q->where('class_id', $class_id);
                },
            ])
                ->with([
                    'timetable' => function ($q) use ($class_id) {
                        $q->where('class_id', $class_id);
                    },
                ])
                ->where('publish', $isPublish)
                ->where('session_year_id', $session_year_id)

                // Semester-based filtering
                ->when($hasSemester, function ($query) use ($teacherSemesterIds) {
                    $query->whereIn('semester_id', $teacherSemesterIds);
                })
                ->get();

            foreach ($exam_data as $data) {

                if (count($data->timetable)) {

                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))
                        ->where('exam_id', $data->id)
                        ->where('class_id', $class_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    $starting_date = $starting_date_db['min(date)'];

                    $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))
                        ->where('exam_id', $data->id)
                        ->where('class_id', $class_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    $ending_date = $ending_date_db['max(date)'];

                    $currentTime = Carbon::now();
                    $current_date = date($currentTime->toDateString());

                    if ($current_date >= $starting_date && $current_date <= $ending_date) {
                        $exam_status = '1';
                    } elseif ($current_date < $starting_date) {
                        $exam_status = '0';
                    } else {
                        $exam_status = '2';
                    }

                    // Completed exams only
                    if ($exam_status == 2) {
                        $exams[] = $data;
                    }
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exams,
            ];

        } catch (Throwable $e) {

            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getPublishExam(Request $request, $class_section_id)
    {
        try {
            $class_id = ClassSection::where('id', $class_section_id)->value('class_id');
            $semester_id = $request->query('semester_id');

            $exam_data = ExamClass::with('exam')
                ->where('class_id', $class_id)
                ->whereHas('exam', function ($query) use ($semester_id) {
                    $query->where('publish', 1)
                        ->when($semester_id, function ($q) use ($semester_id) {
                            $q->where('semester_id', $semester_id);
                        });
                })->get();

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exam_data,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function deleteExamClass($exam_id, $class_id)
    {
        $exam_class = ExamClass::where('exam_id', $exam_id)->where('class_id', $class_id)->first();

        $exam_class->delete();
        $response = [
            'error' => false,
            'message' => trans('data_delete_successfully'),
        ];

        return response()->json($response);
    }
}
