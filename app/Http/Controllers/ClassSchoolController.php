<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\EducationalProgram;
use App\Models\ElectiveSubjectGroup;
use App\Models\ExamClass;
use App\Models\ExamResult;
use App\Models\FeesClass;
use App\Models\Lesson;
use App\Models\Mediums;
use App\Models\Section;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\Stream;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ClassSchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! Auth::user()->can('class-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $currentSessionYearId = $this->getCurrentSessionYearId();
        $classes = ClassSchool::withSessionConfigFor($currentSessionYearId)
            ->orderBy('id', 'DESC')
            ->with('medium', 'sections', 'streams')
            ->get();
        $sections = Section::orderBy('id', 'ASC')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $streams = Stream::orderBy('id', 'ASC')->get();
        $shifts = Shift::where('status', 1)->get();
        $educational_programs = EducationalProgram::orderBy('id', 'ASC')->get();

        return response(view('class.index', compact('classes', 'sections', 'mediums', 'streams', 'shifts', 'educational_programs')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('class-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'medium_id' => 'required|numeric',
            'name' => 'required|regex:/^[A-Za-z0-9_]+$/',
            'section_id' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {

            $classIds = [];

            // Single class
            if (! $request->stream_id) {

                $class = new ClassSchool;

                $class->name = $request->name;
                $class->educational_program_id = $request->educational_program;
                $class->medium_id = $request->medium_id;
                $class->shift_id = $request->shift_id;

                $class->save();

                $classIds[] = $class->id;

            } else {

                // Multiple classes with streams
                foreach ($request->stream_id as $stream_id) {

                    $classId = ClassSchool::insertGetId([
                        'name' => $request->name,
                        'medium_id' => $request->medium_id,
                        'stream_id' => $stream_id,
                        'shift_id' => $request->shift_id,
                        'educational_program_id' => $request->educational_program,
                    ]);

                    $classIds[] = $classId;
                }
            }

            // Insert class sections
            $classSections = [];

            foreach ($classIds as $classId) {

                foreach ($request->section_id as $section_id) {

                    $classSections[] = [
                        'class_id' => $classId,
                        'section_id' => $section_id,
                    ];
                }

                // Create session config for each class
                ClassSessionConfig::create([
                    'class_id' => $classId,
                    'session_year_id' => $this->getCurrentSessionYearId(),
                    'include_semesters' => false,
                ]);
            }

            ClassSection::insert($classSections);

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('class-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'medium_id' => 'required|numeric',
            'name' => 'required|regex:/^[A-Za-z0-9_]+$/',
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $currentSessionYearId = $this->getCurrentSessionYearId();
            $class = ClassSchool::find($id);

            $class->name = $request->name;
            $class->educational_program_id = $request->educational_program;
            $class->medium_id = $request->medium_id;
            $class->shift_id = $request->shift_id;

            if ($request->stream_id != null) {
                $existingrow = ClassSchool::where('name', $request->name)->where('medium_id', $request->medium_id)->where('shift_id', $request->shift_id)->where('stream_id', $request->stream_id)->first();
                if ($existingrow) {
                    $existingrow->stream_id = $request->stream_id;
                } else {
                    $class->stream_id = $request->stream_id;
                }
            }
            $class->save();
            $all_section_ids = ClassSection::whereIn('section_id', $request->section_id)->where('class_id', $id)->pluck('section_id')->toArray();
            $delete_class_section = $class->sections->pluck('id')->toArray();
            $class_section = [];
            foreach ($request->section_id as $key => $section_id) {
                if (! in_array($section_id, $all_section_ids)) {
                    $class_section[] = [
                        'class_id' => $class->id,
                        'section_id' => $section_id,
                    ];
                } else {
                    unset($delete_class_section[array_search($section_id, $delete_class_section)]);
                }
            }
            ClassSection::insert($class_section);

            ClassSection::whereIn('section_id', $delete_class_section)
                ->where('class_id', $id)
                ->get()
                ->each
                ->delete();

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
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

    // delete all related data:
    public function destroy($id)
    {
        if (! Auth::user()->can('class-delete')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $class = ClassSchool::findOrFail($id);

            // Cascade handled at model level
            $class->delete();

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

    public function show()
    {
        if (! Auth::user()->can('class-list')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
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
        DB::enableQueryLog();
        $currentSessionYearId = $this->getCurrentSessionYearId();
        $sql = ClassSchool::withSessionConfigFor($currentSessionYearId)
            ->with('sections', 'medium', 'streams', 'shifts', 'educational_program');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")
                ->orWhereHas('sections', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('medium', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('streams', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('shifts', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                })->orWhereHas('educational_program', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                });
        }
        if ($_GET['medium_id']) {
            $sql = $sql->where('medium_id', $_GET['medium_id']);
        }
        if ($_GET['shift_id']) {
            $sql = $sql->where('shift_id', $_GET['shift_id']);
        }

        if ($_GET['educational_program_id']) {
            $sql = $sql->where('educational_program_id', $_GET['educational_program_id']);
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
        $classIds = $res->pluck('id')->toArray();
        $classSectionMap = ClassSection::whereIn('class_id', $classIds)
            ->get()
            ->groupBy('class_id')
            ->map(fn ($sections) => $sections->pluck('id'));

        $allClassSectionIds = $classSectionMap->flatten()->toArray();

        $relatedByClassSection = [];
        if (! empty($allClassSectionIds)) {
            $relatedByClassSection = [
                'students' => Students::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'assignments' => Assignment::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'attendance' => Attendance::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'exam results' => ExamResult::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'lessons' => Lesson::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'student sessions' => StudentSessions::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'subject teachers' => SubjectTeacher::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'timetables' => Timetable::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
            ];
        }

        $classesWithSubjects = ClassSubject::whereIn('class_id', $classIds)->pluck('class_id')->unique()->toArray();
        $classesWithExams = ExamClass::whereIn('class_id', $classIds)->pluck('class_id')->unique()->toArray();
        $classesWithFees = FeesClass::whereIn('class_id', $classIds)->pluck('class_id')->unique()->toArray();

        foreach ($res as $row) {
            // Build related data array for this class
            $sectionIds = $classSectionMap->get($row->id, collect())->toArray();
            $relatedData = [];
            foreach ($relatedByClassSection as $label => $csIds) {
                if (! empty(array_intersect($sectionIds, $csIds))) {
                    $relatedData[] = $label;
                }
            }
            if (in_array($row->id, $classesWithSubjects)) {
                $relatedData[] = 'class subjects';
            }
            if (in_array($row->id, $classesWithExams)) {
                $relatedData[] = 'exams';
            }
            if (in_array($row->id, $classesWithFees)) {
                $relatedData[] = 'fees';
            }

            $operate = '<a href='.route('class.edit', $row->id).' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="'.route('class.destroy', $row->id).'"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    data-id="'.$row->id.'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['educational_program_id'] = $row->educational_program->id ?? '';
            $tempRow['educational_program_name'] = $row->educational_program->title ?? '-';
            $tempRow['medium_id'] = $row->medium->id;
            $tempRow['medium_name'] = $row->medium->name;
            $tempRow['shift_id'] = $row->shifts->id ?? '';
            $tempRow['shift_name'] = $row->shifts->title ?? '-';
            $sections = $row->sections;
            $tempRow['section_id'] = $sections->pluck('id');
            $tempRow['section_name'] = $sections->pluck('name');
            $tempRow['stream_id'] = $row->streams->id ?? '';
            $tempRow['stream_name'] = $row->streams->name ?? '-';
            $tempRow['include_semesters'] = $row->getIncludeSemestersForSession($currentSessionYearId);
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function subject()
    {
        if (! Auth::user()->can('class-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $currentSessionYearId = $this->getCurrentSessionYearId();
        $classes = ClassSchool::withSessionConfigFor($currentSessionYearId)
            ->orderBy('id', 'DESC')
            ->with('medium', 'sections', 'streams')
            ->get();
        $subjects = Subject::orderBy('id', 'ASC')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $streams = Stream::orderBy('id', 'ASC')->get();
        $semesters = Semester::orderBy('id', 'ASC')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return response(view('class.subject', compact('classes', 'subjects', 'mediums', 'streams', 'semesters', 'session_years')));
    }

    public function update_subjects(Request $request)
    {
        $validation_rules = [
            'class_id' => 'required|numeric',
            'edit_core_subject' => 'nullable|array',
            'edit_core_subject.*' => 'nullable|array|required_array_keys:class_subject_id,subject_id',
            'core_subjects' => 'nullable|array',
            'elective_subject_id' => 'array',
            'elective_subjects' => 'nullable|array',
            'elective_subjects.*.subject_id' => 'required|array',
            'elective_subjects.*.total_selectable_subjects' => 'required|numeric',
            'session_year_id' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        $session_year_id = $request->session_year_id;

        try {

            /*
        |--------------------------------------------------------------------------
        | UPDATE CORE SUBJECTS
        |--------------------------------------------------------------------------
        */

            if ($request->edit_core_subject) {
                foreach ($request->edit_core_subject as $row) {
                    $edit_core_subject = ClassSubject::where('id', $row['class_subject_id'])
                        ->where('session_year_id', $session_year_id)
                        ->first();
                    if (! $edit_core_subject) {
                        continue;
                    }
                    $edit_core_subject->subject_id = $row['subject_id'];
                    $edit_core_subject->semester_id = $row['semester_id'] ?? null;
                    $edit_core_subject->save();
                }
            }

            /*
        |--------------------------------------------------------------------------
        | ADD NEW CORE SUBJECTS
        |--------------------------------------------------------------------------
        */

            if ($request->core_subjects) {
                $core_subjects = [];
                foreach ($request->core_subjects as $row) {
                    $core_subjects[] = [
                        'class_id' => $request->class_id,
                        'type' => 'Compulsory',
                        'subject_id' => $row['subject_id'],
                        'semester_id' => $row['semester_id'] ?? null,
                        'session_year_id' => $session_year_id,
                    ];
                }
                ClassSubject::insert($core_subjects);
            }

            /*
        |--------------------------------------------------------------------------
        | EDIT ELECTIVE SUBJECT GROUPS + CLEANUP STUDENT SUBJECTS
        |--------------------------------------------------------------------------
        */

            if ($request->edit_elective_subjects) {
                foreach ($request->edit_elective_subjects as $subject_group) {

                    // --- FETCH OLD SUBJECT IDS BEFORE UPDATE ---
                    $old_subject_ids = ClassSubject::where([
                        'elective_subject_group_id' => $subject_group['subject_group_id'],
                        'session_year_id' => $session_year_id,
                    ])->pluck('subject_id');

                    // --- UPDATE ELECTIVE GROUP ---
                    $elective_subject_group = ElectiveSubjectGroup::where([
                        'id' => $subject_group['subject_group_id'],
                        'session_year_id' => $session_year_id,
                    ])->first();

                    if (! $elective_subject_group) {
                        continue;
                    }

                    $elective_subject_group->total_subjects = count($subject_group['subject_id']);
                    $elective_subject_group->total_selectable_subjects = $subject_group['total_selectable_subjects'];
                    $elective_subject_group->class_id = $request->class_id;
                    $elective_subject_group->semester_id = $subject_group['semester_id'] ?? null;
                    $elective_subject_group->save();

                    // --- UPDATE SUBJECTS INSIDE THE GROUP ---
                    $new_subject_ids = collect($subject_group['subject_id']);

                    foreach ($subject_group['subject_id'] as $key => $subject_id) {

                        if (! empty($subject_group['class_subject_id'][$key])) {
                            // Existing subject
                            $elective_subject = ClassSubject::where([
                                'id' => $subject_group['class_subject_id'][$key],
                                'session_year_id' => $session_year_id,
                            ])->first();

                            if (! $elective_subject) {
                                $elective_subject = new ClassSubject;
                            }
                        } else {
                            // New subject
                            $elective_subject = new ClassSubject;
                        }

                        $elective_subject->class_id = $request->class_id;
                        $elective_subject->type = 'Elective';
                        $elective_subject->subject_id = $subject_id;
                        $elective_subject->elective_subject_group_id = $elective_subject_group->id;
                        $elective_subject->semester_id = $subject_group['semester_id'] ?? null;
                        $elective_subject->session_year_id = $session_year_id;
                        $elective_subject->save();
                    }

                    // --- CLEANUP: REMOVE STUDENT SUBJECTS FOR REMOVED ELECTIVE SUBJECTS ---
                    $removed_subject_ids = $old_subject_ids->diff($new_subject_ids);

                    if ($removed_subject_ids->count()) {

                        // find class sections that actually have students in the target session
                        $class_section_ids = StudentSessions::query()
                            ->where('session_year_id', $session_year_id)
                            ->whereHas('class_section', function ($q) use ($request) {
                                $q->where('class_id', $request->class_id);
                            })
                            ->pluck('class_section_id')
                            ->unique()
                            ->values();

                        // delete from student_subjects
                        StudentSubject::whereIn('subject_id', $removed_subject_ids)
                            ->whereIn('class_section_id', $class_section_ids)
                            ->where('session_year_id', $session_year_id)
                            ->when($subject_group['semester_id'] ?? null, function ($query, $semester) {
                                return $query->where('semester_id', $semester);
                            })
                            ->delete();
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | CREATE NEW ELECTIVE SUBJECT GROUPS
        |--------------------------------------------------------------------------
        */

            if ($request->elective_subjects) {
                foreach ($request->elective_subjects as $subject_group) {

                    // Create group
                    $elective_subject_group = new ElectiveSubjectGroup;
                    $elective_subject_group->total_subjects = count($subject_group['subject_id']);
                    $elective_subject_group->total_selectable_subjects = $subject_group['total_selectable_subjects'];
                    $elective_subject_group->class_id = $request->class_id;
                    $elective_subject_group->session_year_id = $session_year_id;
                    $elective_subject_group->semester_id = $subject_group['semester_id'] ?? null;
                    $elective_subject_group->save();

                    foreach ($subject_group['subject_id'] as $subject_id) {
                        $elective_subject = [
                            'class_id' => $request->class_id,
                            'type' => 'Elective',
                            'subject_id' => $subject_id,
                            'session_year_id' => $session_year_id,
                            'semester_id' => $subject_group['semester_id'] ?? null,
                            'elective_subject_group_id' => $elective_subject_group->id,
                        ];
                        ClassSubject::insert($elective_subject);
                    }
                }
            }

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    public function subject_list()
    {
        if (! Auth::user()->can('class-list')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = $_GET['offset'] ?? 0;
        $limit = $_GET['limit'] ?? 10;
        $sort = $_GET['sort'] ?? 'id';
        $order = $_GET['order'] ?? 'DESC';
        $session_year_id = $_GET['session_year_id'] ?? null;

        $sql = ClassSchool::withSessionConfigFor($session_year_id)->with([
            'sections',
            'medium',
            'streams',
            'coreSubject' => function ($q) use ($session_year_id) {
                if ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }
                $q->with('semester');
            },
            'electiveSubjectGroup' => function ($q) use ($session_year_id) {
                if ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }

                $q->with(['electiveSubjects' => function ($subQ) use ($session_year_id) {
                    if ($session_year_id) {
                        $subQ->where('session_year_id', $session_year_id);
                    }
                }, 'electiveSubjects.subject']);
            },
        ]);

        if (! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('name', 'LIKE', "%$search%");
        }

        if (! empty($_GET['medium_id'])) {
            $sql->where('medium_id', $_GET['medium_id']);
        }

        $total = $sql->count();

        $currentSemester = Semester::get()->first(function ($semester) {
            return $semester->current;
        });

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $includesSemesters = $row->getIncludeSemestersForSession($session_year_id);

            if ($includesSemesters !== null) {
                $operate = '<a href='.route('class-subject-edit.index', $row->id).' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            } else {
                $operate = '<a href="javascript:void(0)" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon disabled" title="'.trans('semester_not_configured').'" disabled><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            }

            $tempRow = [];
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['medium_id'] = $row->medium->id;
            $tempRow['medium_name'] = $row->medium->name;
            $tempRow['stream_id'] = $row->streams->id ?? ' ';
            $tempRow['stream_name'] = $row->streams->name ?? '-';
            $tempRow['section_names'] = $row->sections->pluck('name');
            $tempRow['include_semesters'] = $includesSemesters;

            if ($includesSemesters && ! empty($currentSemester)) {
                $tempRow['core_subjects'] = $row->coreSubject->filter(function ($data) use ($currentSemester) {
                    return $data->semester_id == $currentSemester->id;
                })->values(); // Filter subjects based on current semester

                $tempRow['elective_subject_groups'] = $row->electiveSubjectGroup->filter(function ($data) use ($currentSemester) {
                    return $data->semester_id == $currentSemester->id;
                })->values();
            } else {
                $tempRow['core_subjects'] = $row->coreSubject;
                $tempRow['elective_subject_groups'] = $row->electiveSubjectGroup;
            }

            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function subject_destroy($id)
    {
        try {
            $classSubject = ClassSubject::findOrFail($id);

            // Cascade + bookkeeping handled in model
            $classSubject->delete();

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

    public function subject_group_destroy($id)
    {
        try {
            $group = ElectiveSubjectGroup::findOrFail($id);

            // Cascade handled at model level
            $group->delete();

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

    public function getSubjectsByMediumId($medium_id)
    {
        try {
            $subjects = Subject::where('medium_id', $medium_id)->get();
            $response = [
                'error' => false,
                'data' => $subjects,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function classSubjectsEdit(Request $request, $id)
    {
        if (! Auth::user()->can('class-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $session_year_id = $request->query('session_year_id');

        $sessionYearId = $session_year_id ?: $this->getCurrentSessionYearId();

        $class = ClassSchool::withSessionConfigFor($sessionYearId)->with([
            'medium',
            'sections',
            'streams',
            'coreSubject' => function ($q) use ($sessionYearId) {
                if ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                }
                $q->with('semester');
            },
            'electiveSubjectGroup' => function ($q) use ($sessionYearId) {
                if ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                }

                $q->with(['electiveSubjects' => function ($subQ) use ($sessionYearId) {
                    if ($sessionYearId) {
                        $subQ->where('session_year_id', $sessionYearId);
                    }
                }, 'electiveSubjects.subject']);
            },
        ])->findOrFail($id);

        $semesters = Semester::where('session_year_id', $sessionYearId)->orderBy('id', 'ASC')->get();
        $subjects = Subject::orderBy('id', 'ASC')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $streams = Stream::orderBy('id', 'ASC')->get();

        return response(view('class.edit_subject', compact('class', 'semesters', 'subjects', 'mediums', 'streams', 'sessionYearId')));
    }

    private function getCurrentSessionYearId(): ?int
    {
        $sessionYearId = getSettings('session_year')['session_year'];

        return $sessionYearId ? (int) $sessionYearId : null;
    }

    public function assignElectiveSubject(Request $request)
    {
        try {
            if (! Auth::user()->can('assign-elective-subjects')) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ]);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'student_id' => 'required_if:is_bulk,0|exists:students,id',
                'student_ids' => 'required_if:is_bulk,1|array',
                'student_ids.*' => 'exists:students,id',
                'elective_group' => 'required|exists:elective_subject_groups,id',
                'selected_subjects' => 'required|array',
                'selected_subjects.*' => 'required|array|min:1',
                'is_bulk' => 'nullable|in:0,1',
                'session_year_id' => 'required|numeric',
                'semester_id' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $isBulk = (int) $request->input('is_bulk', 0);
            $electiveGroupId = $request->input('elective_group');
            $selectedSubjects = $request->input('selected_subjects');
            $semester_id = $request->input('semester_id') ?: null;

            // Flatten all selected subject IDs into one array
            $selectedSubjectIds = collect($selectedSubjects)->flatten()->toArray();

            $electiveGroup = ElectiveSubjectGroup::with('electiveSubjects')
                ->when($semester_id, function ($query) use ($semester_id) {
                    $query->where('semester_id', $semester_id);
                })
                ->findOrFail($electiveGroupId);

            $selectedCount = count($selectedSubjectIds);
            $requiredCount = $electiveGroup->total_selectable_subjects;

            if ($selectedCount != $requiredCount) {
                return response()->json([
                    'error' => true,
                    'message' => "You must select exactly {$requiredCount} subject(s).",
                ], 422);
            }

            $validSubjectIds = $electiveGroup->electiveSubjects->pluck('subject_id')->toArray();
            $invalidSubjects = array_diff($selectedSubjectIds, $validSubjectIds);

            if (! empty($invalidSubjects)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Some selected subjects do not belong to this elective group.',
                ], 422);
            }

            // Get session year and current semester

            $sessionYearId = $request->session_year_id;

            // Build list of students (single or bulk)
            $studentIds = $isBulk ? $request->input('student_ids') : [$request->input('student_id')];

            DB::beginTransaction();

            foreach ($studentIds as $studentId) {

                $studentSession = StudentSessions::where('student_id', $studentId)
                    ->where('session_year_id', $sessionYearId)
                    ->first();

                if (! $studentSession) {
                    throw new \Exception("Student session not found for student ID {$studentId}");
                }

                $classSectionId = $studentSession->class_section_id;

                StudentSubject::where('student_id', $studentId)
                    ->where('class_section_id', $classSectionId)
                    ->where('session_year_id', $sessionYearId)
                    ->when(
                        is_null($semester_id),
                        fn ($q) => $q->whereNull('semester_id'),
                        fn ($q) => $q->where('semester_id', $semester_id)
                    )
                    ->whereIn('subject_id', $validSubjectIds)
                    ->delete();

                foreach ($selectedSubjectIds as $subjectId) {

                    StudentSubject::create([
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'class_section_id' => $classSectionId,
                        'session_year_id' => $sessionYearId,
                        'semester_id' => $semester_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => $isBulk
                    ? 'Elective subjects assigned successfully to all selected students!'
                    : 'Elective subjects assigned successfully!',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStudentAssignedSubjects(Request $request)
    {
        try {
            if (! Auth::user()->can('assign-elective-subjects')) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ]);
            }
            $session_year_id = $request->session_year_id;
            $semester_id = $request->semester_id;
            $studentId = $request->input('student_id');

            if (! $studentId) {
                return response()->json(['error' => 'Student ID is required'], 400);
            }

            $student = Students::findOrFail($studentId);

            $student_class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)
                ->first()->class_section_id;

            $class_id = ClassSection::find($student_class_section_id)->class_id;

            $hasSemesters = ClassSessionConfig::where('class_id', $class_id)
                ->where('session_year_id', $session_year_id)
                ->where('include_semesters', true)
                ->exists();

            // Get assigned elective subjects for this student
            $assignedSubjects = StudentSubject::where('student_id', $studentId)
                ->where('class_section_id', $student_class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when($hasSemesters, function ($query) use ($semester_id) {
                    $query->where('semester_id', $semester_id);
                }, function ($query) {
                    $query->whereNull('semester_id');
                })
                ->pluck('subject_id')
                ->toArray();

            // return response()->json([
            //     'studentId' => $studentId,
            //     'student_class_section_id' => $student_class_section_id,
            //     'session_year_id' => $session_year_id,
            //     'semester_id' => $semester_id,
            // ]);

            return response()->json([
                'error' => false,
                'assigned_subjects' => $assignedSubjects,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function selectElectiveSubjects()
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $classes = [];
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        if (Auth::user()->hasRole('Super Admin')) {
            // Get unique classes, with required relations
            $classes = ClassSchool::with([
                'medium',
                'streams',
            ])->get();
        }

        // Students will depend on selected class_section, so keep it empty for now
        $elective_subjects = [];

        return view(
            'select_elective_subjects.index',
            compact('classes', 'elective_subjects', 'session_years')
        );
    }

    public function getElectiveGroups(Request $request)
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        $session_year_id = $request->session_year_id;
        $class_id = $request->class_id;
        $semester_id = $request->semester_id;

        $hasSemesters = ClassSessionConfig::where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->where('include_semesters', true)
            ->exists();

        $groups = ElectiveSubjectGroup::with([
            'electiveSubjects' => function ($query) {
                $query->without('semester')->with('subject');
            },
        ])
            ->where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->when($hasSemesters, function ($query) use ($semester_id) {
                $query->where('semester_id', $semester_id);
            }, function ($query) {
                $query->whereNull('semester_id');
            })
            ->get()
            ->values();

        // Return as JSON for AJAX
        return response()->json($groups);
    }

    public function getElectiveSubjects(Request $request)
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $request->validate([
            'class_id' => 'required|integer',
            'session_year_id' => 'required|integer',
            'semester_id' => 'nullable|integer',
        ]);

        $class_id = $request->class_id;
        $session_year_id = $request->session_year_id;
        $semester_id = $request->semester_id;

        // Fetch elective subjects corresponding to class
        $hasSemesters = ClassSessionConfig::where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->where('include_semesters', true)
            ->exists();

        $subjects = ElectiveSubjectGroup::with([
            'electiveSubjects' => function ($query) {
                $query->without('semester')->with('subject');
            },
        ])
            ->where('class_id', $class_id)->where('session_year_id', $session_year_id)
            ->when($hasSemesters, function ($query) use ($semester_id) {
                $query->where('semester_id', $semester_id);
            }, function ($query) {
                $query->whereNull('semester_id');
            })
            ->get()
            ->pluck('electiveSubjects')
            ->flatten()
            ->pluck('subject')
            ->filter()
            ->values();

        // Return as JSON for AJAX
        return response()->json($subjects);
    }

    public function fetchStudentSubjects(Request $request)
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search');
        $status = $request->input('filter_status');
        $semester_id = $request->input('semester_id');

        // Get session year
        $sessionYearId = (int) $request->session_year_id;

        // ---------- NEW: single class_id from request ----------
        $class_id = (int) $request->input('class_id');
        if (! $class_id) {
            return response()->json([
                'total' => 0,
                'rows' => [],
            ]);
        }
        // -------------------------------------------------------

        // Single elective subject filter (e.g., filter_elective_subject=6)
        $filterSubjectId = $request->filled('filter_elective_subject')
            ? (int) $request->input('filter_elective_subject')
            : null;

        /* -------------------------------------------------------
        1. Get all students that belong to the given class (session-wise)
        ------------------------------------------------------- */

        $query = StudentSessions::with([
            'student.user',
            'class_section.class',
            'class_section.section',
        ])
            ->where('session_year_id', $sessionYearId)
            ->whereHas('class_section', function ($q) use ($class_id) {
                $q->where('class_id', $class_id);
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', function ($q) use ($search) {
                    $q->where('admission_no', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw(
                                "CONCAT(first_name,' ',last_name) LIKE ?",
                                ["%$search%"]
                            );
                        });
                });
            })
            ->orderBy($sort, $order);

        $students = $query->get();

        /* -------------------------------------------------------
       2. Rules, elective IDs and current selections for THIS class only
       ------------------------------------------------------- */
        $rule = ElectiveSubjectGroup::where('class_id', $class_id)
            ->where('session_year_id', $sessionYearId)
            ->when($semester_id, function ($q) use ($semester_id) {
                $q->where('semester_id', $semester_id);
            })
            ->first();

        $electiveIds = ClassSubject::where('class_id', $class_id)
            ->where('type', 'Elective')
            ->when($semester_id, function ($q) use ($semester_id) {
                $q->where('semester_id', $semester_id);
            })
            ->where('session_year_id', $sessionYearId)
            ->pluck('subject_id')
            ->toArray();

        $selections = StudentSubject::whereIn('student_id', $students->pluck('student_id'))
            ->whereIn('subject_id', $electiveIds)
            ->whereIn('class_section_id', $students->pluck('class_section_id'))
            ->where('session_year_id', $sessionYearId)
            ->when($semester_id, function ($q) use ($semester_id) {
                $q->where('semester_id', $semester_id);
            })
            ->select('student_id', 'subject_id')
            ->get()
            ->groupBy('student_id');

        $filtered = [];

        foreach ($students as $s) {
            $st = $this->calcStatus(
                $s,
                collect([$class_id => $rule]),
                collect([$class_id => $electiveIds]),
                $selections
            );

            // ---- status filter -------------------------------------------------
            if ($status && $st['label'] !== $this->map($status)) {
                continue;
            }

            // ---- elective-subject filter ----------------------------------------
            if ($filterSubjectId) {
                $studentElectiveIds = $selections->get($s->id, collect())
                    ->pluck('subject_id')
                    ->toArray();

                if (! in_array($filterSubjectId, $studentElectiveIds)) {
                    continue;
                }
            }

            // ---- selected subject names -----------------------------------------
            $names = $selections->get($s->student_id, collect())
                ->map(fn ($x) => Subject::find($x->subject_id)?->name ?? '')
                ->filter()
                ->implode(', ');

            $filtered[] = [
                'id' => $s->student_id,
                'full_name' => $s->student->user->full_name,
                'photo' => $s->student->user->image,
                'status' => $st['label'],
                'status_badge' => $st['badge'],
                'selected_subjects' => $names ?: '—',
                'class_id' => $class_id,
                'operate' => '<a href="javascript:void(0)" class="btn btn-xs border border-dark rounded btn-secondary bg-transparent text-dark assign-elective" 
                    data-class-id="'.$class_id.'" data-student-id="'.$s->student_id.'"
                    data-toggle="modal" data-target="#assignModal">Assign</a>',
            ];
        }

        $total = count($filtered);
        $rows = array_slice($filtered, $offset, $limit);

        // ---- KEEP ORIGINAL RESPONSE STRUCTURE ----
        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    private function map($key)
    {
        return ['not_assigned' => 'Not Assigned', 'incomplete' => 'Incomplete', 'complete' => 'Completed'][$key] ?? '';
    }

    private function calcStatus(
        $studentSession,
        $rules,
        $electiveIds,
        $selections
    ): array {
        // Defensive normalization: accept arrays or Collections
        $rules = collect($rules); // keyed by class_id => rule-object/array
        $electiveIds = collect($electiveIds)
            ->map(function ($ids) {
                // ensure every value is an array of IDs
                return is_array($ids) ? array_values($ids) : [(int) $ids];
            });
        $selections = collect($selections)
            ->map(function ($items) {
                // ensure each student's selections is a Collection
                return collect($items);
            });

        // Get class id from student (null-safe)
        $classId = $studentSession->class_section?->class_id ?? null;

        if (! $classId) {
            return [
                'label' => 'No Class',
                'badge' => '<span class="badge badge-secondary">N/A</span>',
                'selected' => 0,
                'required' => 0,
            ];
        }

        // Lookup rule for this class
        $rule = $rules->get($classId);
        $requiredCount = $rule->total_selectable_subjects ?? 0;

        // Get student's selections (collection) and elective subject ids for the class
        $studentSelections = $selections->get($studentSession->student_id, collect());
        $validElectiveIds = $electiveIds->get($classId, []);

        // Ensure IDs are integers (defensive) and always array
        $validElectiveIds = array_map('intval', (array) $validElectiveIds);

        // Count how many selected subjects are actually elective for this class
        $selectedCount = $studentSelections->whereIn('subject_id', $validElectiveIds)->count();

        // Clear, separate conditions for easier reasoning & debugging:
        if ($requiredCount === 0) {
            // No rule exists (class doesn't require/select electives)
            return [
                'label' => 'No Rule',
                'badge' => '<span class="badge badge-info">No Rule</span>',
                'selected' => $selectedCount,
                'required' => $requiredCount,
            ];
        }
        if ($selectedCount === 0) {
            // Rule exists but student hasn't selected any valid elective
            return [
                'label' => 'Not Assigned',
                'badge' => '<span class="badge badge-secondary border rounded border-dark text-dark bg-transparent">Not Assigned</span>',
                'selected' => 0,
                'required' => $requiredCount,
            ];
        }

        if ($selectedCount >= $requiredCount) {
            return [
                'label' => 'Completed',
                'badge' => '<span class="badge badge-success border rounded border-success text-success bg-transparent">Completed</span>',
                'selected' => $selectedCount,
                'required' => $requiredCount,
            ];
        }

        return [
            'label' => 'Incomplete',
            'badge' => '<span class="badge badge-warning border rounded border-warning text-warning bg-transparent">Incomplete</span>',
            'selected' => $selectedCount,
            'required' => $requiredCount,
        ];
    }

    public function getClassSections($class_id)
    {
        $sections = ClassSection::with('class', 'section', 'streams')
            ->where('class_id', $class_id)
            ->get();

        return response()->json($sections);
    }
}
