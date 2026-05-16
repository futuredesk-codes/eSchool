<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamQuestionOption;
use App\Models\OnlineExamStudentAnswer;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\StudentOnlineExamStatus;
use App\Models\Students;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class OnlineExamController extends Controller
{
    public function index()
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // all session year
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return response(view('online_exam.index', compact('session_years')));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'online_exam_based_on' => 'required|in:0,1',
            'class_id' => 'required_if:online_exam_based_on,0|nullable',
            'subject_class_id' => 'required_if:online_exam_based_on,0|nullable',
            'title_class' => 'required_if:online_exam_based_on,0|nullable',
            'exam_key_class' => 'required_if:online_exam_based_on,0|numeric|unique:online_exams,exam_key,NULL,id,deleted_at,NULL|nullable',
            'duration_class' => 'required_if:online_exam_based_on,0|numeric|gte:1|nullable',
            'start_date_class' => 'required_if:online_exam_based_on,0|nullable',
            'end_date_class' => 'required_if:online_exam_based_on,0|after:start_date_class|nullable',

            'class_section_id' => 'required_if:online_exam_based_on,1|nullable',
            'session_year_id' => 'required',
            'subject_class_section_id' => 'required_if:online_exam_based_on,1|nullable',
            'title_class_section' => 'required_if:online_exam_based_on,1|nullable',
            'exam_key_class_section' => 'required_if:online_exam_based_on,1|nullable|numeric|unique:online_exams,exam_key,NULL,id,deleted_at,NULL',
            'duration_class_section' => 'required_if:online_exam_based_on,1|nullable|numeric|gte:1',
            'start_date_class_section' => 'required_if:online_exam_based_on,1|nullable',
            'end_date_class_section' => 'required_if:online_exam_based_on,1|after:start_date_class_section|nullable',
        ], [
            'class_id.required_if' => trans('class_required_when_online_exam_is_class_based'),
            'subject_class_id.required_if' => trans('subject_required'),
            'title_class.required_if' => trans('title_required'),
            'exam_key_class.required_if' => trans('exam_key_required'),
            'exam_key_class.unique' => trans('exam_key_already_taken'),
            'duration_class.required_if' => trans('duration_required'),
            'duration_class.gte' => trans('duration_should_be_greater_than_or_equal_to_1'),
            'start_date_class.required_if' => trans('start_date_required'),
            'end_date_class.required_if' => trans('end_date_required'),
            'end_date_class.after' => trans('end_date_should_be_date_after_start_date'),

            'class_section_id.required_if' => trans('class_section_required_when_online_exam_is_class_section_based'),
            'subject_class_section_id.required_if' => trans('subject_required'),
            'title_class_section.required_if' => trans('title_required'),
            'exam_key_class_section.required_if' => trans('exam_key_required'),
            'exam_key_class_section.unique' => trans('exam_key_already_taken'),
            'duration_class_section.required_if' => trans('duration_required'),
            'duration_class_section.gte' => trans('duration_should_be_greater_than_or_equal_to_1'),
            'start_date_class_section.required_if' => trans('start_date_required'),
            'end_date_class_section.required_if' => trans('end_date_required'),
            'end_date_class_section.after' => trans('end_date_should_be_date_after_start_date'),
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $session_year_id = $request->session_year_id;

            $online_exam_create = new OnlineExam;
            if ($request->online_exam_based_on) {
                // store class section based online exam
                $online_exam_create->model_type = 'App\Models\ClassSection';
                $online_exam_create->model_id = $request->class_section_id;
                $online_exam_create->subject_id = $request->subject_class_section_id;
                $online_exam_create->title = safe_htmlspecialchars($request->title_class_section);
                $online_exam_create->exam_key = $request->exam_key_class_section;
                $online_exam_create->duration = $request->duration_class_section;
                $online_exam_create->start_date = $request->start_date_class_section;
                $online_exam_create->end_date = $request->end_date_class_section;
                $online_exam_create->session_year_id = $session_year_id;
                $online_exam_create->semester_id = $request->semester_id;
                $online_exam_create->save();
            } else {
                // store class based online exam
                $online_exam_create->model_type = 'App\Models\ClassSchool';
                $online_exam_create->model_id = $request->class_id;
                $online_exam_create->subject_id = $request->subject_class_id;
                $online_exam_create->title = safe_htmlspecialchars($request->title_class);
                $online_exam_create->exam_key = $request->exam_key_class;
                $online_exam_create->duration = $request->duration_class;
                $online_exam_create->start_date = $request->start_date_class;
                $online_exam_create->end_date = $request->end_date_class;
                $online_exam_create->session_year_id = $session_year_id;
                $online_exam_create->semester_id = $request->semester_id;
                $online_exam_create->save();
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

    public function show($id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        // Get teacher's assigned subjects and class sections
        $teacher_id = Auth::user()->teacher->id;
        $subject_teacher = SubjectTeacher::where('teacher_id', $teacher_id);
        $assigned_class_sections = $subject_teacher->pluck('class_section_id')->toArray();
        $assigned_subjects = $subject_teacher->pluck('subject_id')->toArray();

        $sql = OnlineExam::with('subject', 'question_choice', 'model')
            // Filter by current session year
            ->where('session_year_id', $session_year_id)
            // Filter by teacher's assigned subjects
            ->where(function ($query) use ($assigned_class_sections, $assigned_subjects) {
                $query->whereIn('subject_id', $assigned_subjects)
                    ->where(function ($q) use ($assigned_class_sections) {
                        $q->where(function ($q1) use ($assigned_class_sections) {
                            // Filter class section based exams
                            $q1->where('model_type', 'App\Models\ClassSection')
                                ->whereIn('model_id', $assigned_class_sections);
                        })->orWhere(function ($q2) use ($assigned_class_sections) {
                            // Filter class based exams where teacher has any section
                            $q2->where('model_type', 'App\Models\ClassSchool')
                                ->whereIn('model_id', function ($subquery) use ($assigned_class_sections) {
                                    $subquery->select('class_id')
                                        ->from('class_sections')
                                        ->whereIn('id', $assigned_class_sections);
                                });
                        });
                    });
            })
            // search query
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhere('title', 'LIKE', "%$search%")
                        ->orWhere('exam_key', 'LIKE', "%$search%")
                        ->orWhere('duration', 'LIKE', "%$search%")
                        ->orWhere('start_date', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                        ->orWhere('end_date', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                        ->orWhere('created_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                        ->orWhere('updated_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                        ->orWhereHas('subject', function ($query) use ($search) {
                            $query->where('name', 'LIKE', "%$search%")
                                ->orWhere('type', 'LIKE', "%$search%");
                        });
                });
            })
            // class data filter
            ->when(request('class_id') != null, function ($query) use ($assigned_class_sections) {
                $classId = request('class_id');
                $query->where(function ($query) use ($classId, $assigned_class_sections) {
                    // Only allow filtering by classes where teacher has access
                    $classSectionIds = ClassSection::where('class_id', $classId)
                        ->whereIn('id', $assigned_class_sections)
                        ->pluck('id');

                    $query->whereIn('model_id', $classSectionIds)
                        ->where('model_type', 'App\Models\ClassSection')
                        ->orWhere(function ($query) use ($classId) {
                            $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $classId]);
                        });
                });
            })
            // subject data filter - only allow filtering by assigned subjects
            ->when(request('subject_id') != null, function ($query) use ($assigned_subjects) {
                $query->where('subject_id', request('subject_id'))
                    ->whereIn('subject_id', $assigned_subjects);
            })
            // semester data filter
            ->when(request('semester_id') != null, function ($query) {
                $query->where('semester_id', request('semester_id'));
            });

        // Get total before applying pagination
        $total = $sql->count();

        // Apply pagination
        $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $examIds = $res->pluck('id')->toArray();
        $examsWithQuestionChoices = OnlineExamQuestionChoice::whereIn('online_exam_id', $examIds)->pluck('online_exam_id')->unique()->toArray();
        $examsWithStudentAnswers = OnlineExamStudentAnswer::whereIn('online_exam_id', $examIds)->pluck('online_exam_id')->unique()->toArray();
        $examsWithStatus = StudentOnlineExamStatus::whereIn('online_exam_id', $examIds)->pluck('online_exam_id')->unique()->toArray();

        foreach ($res as $row) {
            // Only show edit/delete buttons for exams in current session
            $operate = '';
            // Add Questions button with margin
            $operate = '<div class="d-flex justify-content-around align-items-center">';
            $operate .= '<a href="'.route('exam.questions.index', ['id' => $row->id]).'" class="btn btn-xs btn-gradient-info btn-rounded btn-icon add-questions mx-1" data-online_exam_id='.$row->id.' data-url='.url('online-exam-question.index').' title="Add Questions"><i class="fa fa-question-circle"></i></a>';

            if ($row->session_year_id == $session_year_id) {
                // Edit button with margin
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data mx-1" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>';

                // Delete button with margin
                $relatedData = [];
                if (in_array($row->id, $examsWithQuestionChoices)) {
                    $relatedData[] = 'question choices';
                }
                if (in_array($row->id, $examsWithStudentAnswers)) {
                    $relatedData[] = 'student answers';
                }
                if (in_array($row->id, $examsWithStatus)) {
                    $relatedData[] = 'exam status';
                }

                $operate .= '
                    <a
                        href="'.route('online-exam.destroy', $row->id).'"
                        class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form mx-1"
                        data-id="'.$row->id.'"
                        title="Delete"'
                    .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                    '>
                        <i class="fa fa-trash"></i>
                    </a>';
            }

            // View Results button with margin
            $operate .= '<a href="'.route('online-exam.result.index', ['id' => $row->id]).'" class="btn btn-xs btn-gradient-success btn-rounded btn-icon view-result mx-1" title="View Results"><i class="fa fa-file-text-o"></i></a>';
            $operate .= '</div>';

            $tempRow['online_exam_id'] = $row->id;
            $tempRow['no'] = $no++;

            if ($row->model_type == 'App\Models\ClassSection') {
                // if online exam based on is Class Section
                $tempRow['online_exam_belongs_to'] = 1;
                $tempRow['class_section_id'] = $row->model_id;
                $class_section_data = ClassSection::where('id', $row->model_id)->with('class.medium', 'section', 'class.streams')->first();
                $tempRow['class_name'] = $class_section_data->class->name.' - '.$class_section_data->section->name.' '.$class_section_data->class->medium->name.' '.($class_section_data->class->streams->name ?? '');
            } else {
                // if online exam based on is Class
                $tempRow['online_exam_belongs_to'] = 0;
                $tempRow['class_id'] = $row->model_id;
                $class_data = ClassSchool::where('id', $row->model_id)->with(['medium', 'streams'])->first();
                $tempRow['class_name'] = $class_data->name.' '.$class_data->medium->name.' '.($class_data->streams->name ?? ' ');
            }
            $tempRow['subject_id'] = $row->subject_id;
            $tempRow['subject_name'] = $row->subject->name.' - '.$row->subject->type;
            $tempRow['semester_id'] = $row->semester_id;
            $tempRow['semester_name'] = $row->semester->name ?? ' - ';
            $tempRow['title'] = safe_htmlspecialchars_decode($row->title);
            $tempRow['exam_key'] = $row->exam_key;
            $tempRow['duration'] = $row->duration;
            $tempRow['start_date'] = convertDateFormat($row->start_date, 'd-m-Y H:i:s');
            $tempRow['end_date'] = convertDateFormat($row->end_date, 'd-m-Y H:i:s');
            $tempRow['total_questions'] = $row->question_choice->count();
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'edit_title' => 'required',
            'edit_exam_key' => 'required|numeric|unique:online_exams,exam_key,'.$id.',id,deleted_at,NULL',
            'edit_duration' => 'required|numeric|gte:1',
            'edit_start_date' => 'required|date',
            'edit_end_date' => 'required|after:edit_start_date',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $update_online_exam = OnlineExam::findOrFail($id);
            $update_online_exam->title = $request->edit_title;
            $update_online_exam->exam_key = $request->edit_exam_key;
            $update_online_exam->duration = $request->edit_duration;
            $update_online_exam->start_date = $request->edit_start_date;
            $update_online_exam->end_date = $request->edit_end_date;
            $update_online_exam->semester_id = $request->semester_id;
            $update_online_exam->save();

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function destroy($id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $onlineExam = OnlineExam::findOrFail($id);

            // Deep cascade handled at model level
            $onlineExam->delete();

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

    public function getClassSectionsBySessionYear(Request $request)
    {
        try {
            $teacher_id = Auth::user()->teacher->id;
            $session_year_id = $request->session_year_id;

            $subject_teacher = SubjectTeacher::where('teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id);

            $class_section_ids = $subject_teacher->pluck('class_section_id')->unique();

            // Get class sections
            $class_sections = ClassSection::whereIn('id', $class_section_ids)
                ->with(['class.medium', 'section', 'class.streams', 'class.sessionConfigs' => function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }])
                ->get()
                ->map(function ($cs) use ($session_year_id) {
                    return [
                        'id' => $cs->id,
                        'name' => $cs->class->name.' '.$cs->section->name.' - '.$cs->class->medium->name.' '.($cs->class->streams->name ?? ''),
                        'include_semesters' => $cs->class->getIncludeSemestersForSession($session_year_id) ?? false,
                    ];
                });

            // Get unique classes
            $class_ids = ClassSection::whereIn('id', $class_section_ids)->pluck('class_id')->unique();
            $classes = ClassSchool::whereIn('id', $class_ids)
                ->with(['medium', 'streams', 'sessionConfigs' => function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }])
                ->get()
                ->map(function ($c) use ($session_year_id) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name.' '.$c->medium->name.' '.($c->streams->name ?? ''),
                        'include_semesters' => $c->getIncludeSemestersForSession($session_year_id) ?? false,
                    ];
                });

            // Get all subjects for this session year assigned to teacher
            $subject_ids = SubjectTeacher::where('teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id)
                ->pluck('subject_id')->unique();
            $subjects = Subject::whereIn('id', $subject_ids)->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name.' - '.$s->type,
                ];
            });

            // Get semesters
            $semesters = Semester::where('session_year_id', $session_year_id)->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                ];
            });

            return response()->json([
                'error' => false,
                'data' => [
                    'class_sections' => $class_sections->values(),
                    'classes' => $classes->values(),
                    'subjects' => $subjects->values(),
                    'semesters' => $semesters->values(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    /**
     * Return only the classes / class-sections where the teacher has a subject
     * assigned for the given session year + semester combination.
     *
     * When semester_id is empty the query is restricted to SubjectTeacher rows
     * whose semester_id IS NULL (non-semester classes).
     * When semester_id is provided the query is restricted to that semester.
     *
     * GET /get-class-sections-by-semester?session_year_id=&semester_id=
     */
    public function getClassSectionsBySemester(Request $request)
    {
        try {
            $teacher_id = Auth::user()->teacher->id;
            $session_year_id = $request->session_year_id;
            $semester_id = $request->semester_id; // may be null / empty string

            // Build the base SubjectTeacher query for this teacher + session year
            $stQuery = SubjectTeacher::where('teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id);

            if ($semester_id) {
                // Teacher must have a subject in this specific semester
                $stQuery->where('semester_id', $semester_id);
            } else {
                // Teacher must have a subject with no semester (non-semester class)
                $stQuery->whereNull('semester_id');
            }

            $class_section_ids = $stQuery->pluck('class_section_id')->unique();

            // Get class sections
            $class_sections = ClassSection::whereIn('id', $class_section_ids)
                ->with(['class.medium', 'section', 'class.streams', 'class.sessionConfigs' => function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }])
                ->get()
                ->map(function ($cs) use ($session_year_id) {
                    return [
                        'id'   => $cs->id,
                        'name' => $cs->class->name.' '.$cs->section->name.' - '.$cs->class->medium->name.' '.($cs->class->streams->name ?? ''),
                        'include_semesters' => $cs->class->getIncludeSemestersForSession($session_year_id) ?? false,
                    ];
                });

            // Get unique classes
            $class_ids = ClassSection::whereIn('id', $class_section_ids)->pluck('class_id')->unique();
            $classes = ClassSchool::whereIn('id', $class_ids)
                ->with(['medium', 'streams', 'sessionConfigs' => function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }])
                ->get()
                ->map(function ($c) use ($session_year_id) {
                    return [
                        'id'   => $c->id,
                        'name' => $c->name.' '.$c->medium->name.' '.($c->streams->name ?? ''),
                        'include_semesters' => $c->getIncludeSemestersForSession($session_year_id) ?? false,
                    ];
                });

            return response()->json([
                'error' => false,
                'data'  => [
                    'class_sections' => $class_sections->values(),
                    'classes'        => $classes->values(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error'   => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function getSubjects(Request $request)
    {
        try {
            $teacher_id = Auth::user()->teacher->id;
            $subjects = [];
            $session_year_id = $request->session_year_id;
            $semester_id = $request->semester_id;
            if ($request->based_on) {
                // return the subjects based on class section
                $query = SubjectTeacher::where(['class_section_id' => $request->class_section_id, 'teacher_id' => $teacher_id])
                    ->where('session_year_id', $session_year_id);
                if ($semester_id) {
                    $query = $query->where('semester_id', $semester_id);
                }
                $subject_id = $query->pluck('subject_id');
                $subjects = Subject::whereIn('id', $subject_id)->get();
            } else {
                // return the subjects based on class
                $class_section_id = ClassSection::where('class_id', $request->class_id)->pluck('id');
                $query = SubjectTeacher::whereIn('class_section_id', $class_section_id)
                    ->where('session_year_id', $session_year_id)
                    ->where('teacher_id', $teacher_id);
                if ($semester_id) {
                    $query = $query->where('semester_id', $semester_id);
                }
                $subject_id = $query->pluck('subject_id');
                $subjects = Subject::whereIn('id', $subject_id)->get();
            }
            $response = [
                'error' => false,
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

    public function examQuestionsIndex()
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $online_exam_id = $_GET['id'];
        $online_exam_db = OnlineExam::where('id', $online_exam_id)->with('subject')->first();

        // checks wheather exam belongs to class section or class
        if ($online_exam_db->model_type == 'App\Models\ClassSection') {
            // get the class section data
            $class_section_data = ClassSection::where('id', $online_exam_db->model_id)->with('class.medium', 'section')->first();
            // make array of class section's data
            $class_data = [
                'id' => $online_exam_db->model_id,
                'class_name' => $class_section_data->class->name.' '.$class_section_data->section->name.' - '.$class_section_data->class->medium->name,
            ];
        } else {
            // get the class data
            $class_db_data = ClassSchool::where('id', $online_exam_db->model_id)->with('medium')->first();
            // make array of class's data
            $class_data = [
                'id' => $online_exam_db->model_id,
                'class_name' => $class_db_data->name.' '.$class_db_data->medium->name,
            ];
        }

        $exam_questions = OnlineExamQuestionChoice::where('online_exam_id', $online_exam_id)->with('online_exam', 'questions')->get();

        return response(view('online_exam.exam_questions', compact('online_exam_db', 'exam_questions', 'class_data')));
    }

    public function storeExamQuestionChoices(Request $request)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'online_exam_id' => 'required',
                'question_type' => 'required|in:0,1',
                'question' => 'required_if:question_type,0',
                'option.*' => 'required_if:question_type,0',
                'equestion' => 'required_if:question_type,1',
                'eoption.*' => 'required_if:question_type,1',
                'answer.*' => 'required_if:question_type,0',
                'image' => 'nullable|mimes:jpeg,png,jpg|image|max:3048',
            ],
            [
                'question.required_if' => __('question_is_required'),
                'option.*.required_if' => __('all_options_are_required'),
                'equestion.required_if' => __('question_is_required'),
                'eoption.*.required_if' => __('all_options_are_required'),
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
            $online_exam_data = OnlineExam::where('id', $request->online_exam_id)->first();

            if ($online_exam_data->model_type == 'App\Models\ClassSection') {
                $class_id = ClassSection::where('id', $online_exam_data->model_id)->pluck('class_id')->first();
            } else {
                $class_id = $online_exam_data->model_id;
            }
            $class_subject_id = ClassSubject::where(['class_id' => $class_id, 'subject_id' => $online_exam_data->subject_id])->pluck('id')->first();
            if ($request->question_type == 1) {
                $question_store = new OnlineExamQuestion;
                $question_store->class_subject_id = $class_subject_id;
                $question_store->question_type = $request->question_type;
                $question_store->session_year_id = $online_exam_data->session_year_id;
                $question_store->question = safe_htmlspecialchars($request->equestion);
                $question_store->note = safe_htmlspecialchars($request->note);
                if ($request->hasFile('image')) {
                    $image = $request->file('image');

                    // made file name with combination of current time
                    $file_name = time().'-'.$image->getClientOriginalName();

                    // made file path to store in database
                    $file_path = 'online-exam-questions/'.$file_name;

                    // resized image
                    resizeImage($image);

                    // stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    // saved file path to database
                    $question_store->image_url = $file_path;
                }
                $question_store->save();

                // store options
                $options_id = [];
                foreach ($request->eoption as $key => $option) {
                    $question_option_store = new OnlineExamQuestionOption;
                    $question_option_store->question_id = $question_store->id;
                    $question_option_store->option = safe_htmlspecialchars($option);
                    $question_option_store->save();
                    $options_id[$key] = $question_option_store->id;
                }
                foreach ($request->answer as $answer) {
                    foreach ($options_id as $key => $option) {
                        if ($key == $answer) {
                            $question_answer_store = new OnlineExamQuestionAnswer;
                            $question_answer_store->question_id = $question_store->id;
                            $question_answer_store->answer = $options_id[$key];
                            $question_answer_store->save();
                        }
                    }
                }
            } else {
                try {
                    $question_store = new OnlineExamQuestion;
                    $question_store->class_subject_id = $class_subject_id;
                    $question_store->question_type = $request->question_type;
                    $question_store->session_year_id = $online_exam_data->session_year_id;
                    $question_store->question = safe_htmlspecialchars($request->question);
                    $question_store->note = safe_htmlspecialchars($request->note);
                    if ($request->hasFile('image')) {
                        $image = $request->file('image');

                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();

                        // made file path to store in database
                        $file_path = 'online-exam-questions/'.$file_name;

                        // resized image
                        resizeImage($image);

                        // stored image to storage/public/online-exam-questions folder
                        $destinationPath = storage_path('app/public/online-exam-questions');
                        $image->move($destinationPath, $file_name);

                        // saved file path to database
                        $question_store->image_url = $file_path;
                    }
                    $question_store->save();
                } catch (Throwable $e) {
                    dd($e->getMessage());
                }

                // store options
                $options_id = [];
                foreach ($request->option as $key => $option) {
                    $question_option_store = new OnlineExamQuestionOption;
                    $question_option_store->question_id = $question_store->id;
                    $question_option_store->option = safe_htmlspecialchars($option);
                    $question_option_store->save();
                    $options_id[$key] = $question_option_store->id;
                }
                foreach ($request->answer as $answer) {
                    foreach ($options_id as $key => $option) {
                        if ($key == $answer) {
                            $question_answer_store = new OnlineExamQuestionAnswer;
                            $question_answer_store->question_id = $question_store->id;
                            $question_answer_store->answer = $options_id[$key];
                            $question_answer_store->save();
                        }
                    }
                }
            }
            // dd($question_store->toArray());
            if ($request->question_type) {
                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                    'data' => [
                        'exam_id' => $request->online_exam_id,
                        'question_type' => $request->question_type,
                        'question_id' => $question_store->id,
                        'question' => "<textarea id='qc".$question_store->id."'>".safe_htmlspecialchars_decode($request->equestion)."</textarea><script>setTimeout(() => {equation_editor = CKEDITOR.inline('qc".$question_store->id."', { skin:'moono',extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, }); },1000);</script>",
                    ],
                ];
            } else {
                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                    'data' => [
                        'exam_id' => $request->online_exam_id,
                        'question_type' => $request->question_type,
                        'question_id' => $question_store->id,
                        'question' => $request->question,
                    ],
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

    public function getClassSubjectQuestions($online_exam_id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';

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

        $online_exam_data = OnlineExam::where('id', $online_exam_id)->first();
        if ($online_exam_data->model_type == 'App\Models\ClassSection') {
            $class_id = ClassSection::where('id', $online_exam_data->model_id)->pluck('class_id')->first();
        } else {
            $class_id = $online_exam_data->model_id;
        }
        $class_subject_id = ClassSubject::where(['class_id' => $class_id, 'subject_id' => $online_exam_data->subject_id])->pluck('id')->first();
        $exclude_question_id = OnlineExamQuestionChoice::where('online_exam_id', $online_exam_id)->pluck('question_id');
        $sql = OnlineExamQuestion::with('class_subject', 'options', 'answers')->where('class_subject_id', $class_subject_id)->whereNotIn('id', $exclude_question_id);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql = $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('question', 'LIKE', "%$search%")
                ->orWhere('created_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                ->orWhere('updated_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                ->orWhereHas('class_subject', function ($q) use ($search) {
                    $q->whereHas('class', function ($c) use ($search) {
                        $c->where('name', 'LIKE', "%$search%")
                            ->orWhereHas('medium', function ($m) use ($search) {
                                $m->where('name', 'LIKE', "%$search%");
                            });
                    })->orWhereHas('subject', function ($s) use ($search) {
                        $s->where('name', 'LIKE', "%$search%")
                            ->orWhere('type', 'LIKE', "%$search%");
                    });
                })
                ->orWhereHas('options', function ($p) use ($search) {
                    $p->where('option', 'LIKE', "%$search%");
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
            $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('online-exam-question.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';

            $tempRow['question_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->class_subject->class_id;
            $tempRow['class_name'] = $row->class_subject->class->name.' '.$row->class_subject->class->medium->name;
            $tempRow['class_subject_id'] = $row->class_subject_id;
            $tempRow['subject_id'] = $row->class_subject->subject_id;
            $tempRow['subject_name'] = $row->class_subject->subject->name.' - '.$row->class_subject->subject->type;
            $tempRow['question_type'] = $row->question_type;
            $tempRow['question'] = '';
            $tempRow['options'] = [];
            $tempRow['answers'] = [];
            if ($row->question_type) {
                $tempRow['question'] = "<div class='equation-editor-inline' contenteditable=false name='qc".$row->id."'>".safe_htmlspecialchars_decode($row->question).'</div>';
                $tempRow['question_row'] = safe_htmlspecialchars_decode($row->question);
                $option_data = [];
                foreach ($row->options as $key => $options) {
                    $option_data = [
                        'id' => $options->id,
                        'option' => "<div class='equation-editor-inline' contenteditable=false>".safe_htmlspecialchars_decode($options->option).'</div>',
                        'option_row' => safe_htmlspecialchars_decode($options->option),
                    ];
                    $tempRow['options'][] = $option_data;
                }
                $answer_data = [];
                foreach ($row->answers as $answers) {
                    $answer_data = [
                        'id' => $answers->id,
                        'answer' => "<div class='equation-editor-inline' contenteditable=false>".safe_htmlspecialchars_decode($answers->options->option).'</div>',
                    ];
                    $tempRow['answers'][] = $answer_data;
                }
            } else {
                $tempRow['question'] = safe_htmlspecialchars_decode($row->question);
                $tempRow['question_textarea'] = null;
                $option_data = [];
                foreach ($row->options as $key => $options) {
                    $option_data = [
                        'id' => $options->id,
                        'option' => safe_htmlspecialchars_decode($options->option),
                    ];
                    $tempRow['options'][] = $option_data;
                }
                foreach ($row->answers as $key => $answers) {
                    $answer_data = [
                        'id' => $answers->id,
                        'answer' => safe_htmlspecialchars_decode($answers->options->option),
                    ];
                    $tempRow['answers'][] = $answer_data;
                }
            }
            $tempRow['image'] = $row->image_url;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function storeQuestionsChoices(Request $request)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'exam_id' => 'required',
                'assign_questions.*.question_id' => 'required',
                'assign_questions.*.marks' => 'required|numeric',
            ],
            [
                'assign_questions.*.marks.required' => trans('marks_are_required'),
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
            $data_store = [];
            foreach ($request->assign_questions as $question) {
                if (isset($question['edit_id']) && ! empty($question['edit_id'])) {
                    $edit_question_choice = OnlineExamQuestionChoice::find($question['edit_id']);
                    $edit_question_choice->marks = $question['marks'];
                    $edit_question_choice->save();
                } else {
                    $data_store[] = [
                        'online_exam_id' => $request->exam_id,
                        'question_id' => $question['question_id'],
                        'marks' => $question['marks'],
                    ];
                }
            }
            if (isset($data_store) && ! empty($data_store)) {
                OnlineExamQuestionChoice::insert($data_store);
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

    public function removeQuestionsChoices($id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $choice = OnlineExamQuestionChoice::findOrFail($id);

            // Cascade handled at model level
            $choice->delete();

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

    public function onlineExamTermsConditionIndex()
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = Settings::where('type', 'online_exam_terms_condition')->first();
        $type = 'online_exam_terms_condition';

        return response(view('online_exam.terms_conditions', compact('settings', 'type')));
    }

    public function storeOnlineExamTermsCondition(Request $request)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $type = $request->type;
            $message = $request->message;
            $id = Settings::select('id')->where('type', $type)->pluck('id')->first();
            if (isset($id) && ! empty($id)) {
                $setting = Settings::find($id);
                $setting->message = safe_htmlspecialchars($message);
                $setting->save();
                $response = [
                    'error' => false,
                    'message' => trans('data_update_successfully'),
                ];
            } else {
                $setting = new Settings;
                $setting->type = $type;
                $setting->message = safe_htmlspecialchars($message);
                $setting->save();
                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
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

    public function onlineExamResultIndex($id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $online_exam = OnlineExam::where('id', $id)->with('model', 'subject')->first();
        if ($online_exam->model_type == 'App\Models\ClassSection') {
            $class_data = ClassSection::where('id', $online_exam->model_id)->with('class.medium', 'section')->first();
        } else {
            $class_data = ClassSchool::where('id', $online_exam->model_id)->with('medium')->first();
        }

        return response(view('online_exam.online_exam_result', compact('online_exam', 'class_data')));
    }

    public function showOnlineExamResult($id)
    {
        if (! Auth::user()->can('manage-online-exam')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';

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
        $class_section_id = SubjectTeacher::where('teacher_id', $teacher_id)->pluck('class_section_id');

        $student_id = Students::whereIn('class_section_id', $class_section_id)->pluck('id');

        $sql = StudentOnlineExamStatus::with('student_data', 'online_exam.question_choice')->whereIn('student_id', $student_id)->where('status', 2)->where('online_exam_id', $id);

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $student_attempt) {
            // get the total marks and obtained marks
            $total_obtained_marks = 0;
            $total_marks = 0;

            $exam_submitted_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student_attempt->student_id, 'online_exam_id' => $student_attempt->online_exam_id])->pluck('question_id');

            $question_ids = OnlineExamQuestionChoice::whereIn('id', $exam_submitted_question_ids)->pluck('question_id');

            $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student_attempt->student_id, 'online_exam_id' => $student_attempt->online_exam_id])->pluck('option_id');

            // removes the question id of the question if one of the answer of particular question is wrong
            foreach ($question_ids as $question_id) {
                $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id', $question_id)->whereNotIn('answer', $exam_attempted_answers)->count();
                if ($check_questions_answers_exists) {
                    unset($question_ids[array_search($question_id, $question_ids->toArray())]);
                }
            }

            $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id', $question_ids)->whereIn('answer', $exam_attempted_answers)->pluck('question_id');

            // get the data of only attempted data
            $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $student_attempt->online_exam_id)->whereIn('question_id', $exam_correct_answers_question_id)->first();
            $total_obtained_marks = $total_obtained_marks['sum(marks)'];
            $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $student_attempt->online_exam_id)->first();
            $total_marks = $total_marks['sum(marks)'];

            $tempRow['student_id'] = $student_attempt->student_id;
            $tempRow['no'] = $no++;
            $tempRow['student_name'] = $student_attempt->student_data->user->first_name.' '.$student_attempt->student_data->user->last_name;
            if ($total_obtained_marks) {
                $tempRow['marks'] = $total_obtained_marks.' / '.$total_marks;
            } else {
                $total_obtained_marks = 0;
                $tempRow['marks'] = $total_obtained_marks.' / '.$total_marks;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
}
