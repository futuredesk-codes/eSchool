<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Semester;
use App\Models\Timetable;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use App\Models\SubjectTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (!Auth::user()->can('timetable-list') || !Auth::user()->can('class-timetable')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        return view('timetable.index', compact('session_years'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('timetable-create') || !Auth::user()->can('timetable-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }

        $request->validate([
            'day' => 'required',
            'class_section_id' => 'required',
            'session_year_id' => 'required|integer',
            'semester_id' => 'nullable|integer',
        ]);

        try {
            $day_name = $request->day;
            $class_section_id = $request->class_section_id;

            // $class = ClassSection::where('id', $class_section_id)->with('class')->get();

            if ($day_name == 'monday') {
                $day = 1;
            } elseif ($day_name == 'tuesday') {
                $day = 2;
            } elseif ($day_name == 'wednesday') {
                $day = 3;
            } elseif ($day_name == 'thursday') {
                $day = 4;
            } elseif ($day_name == 'friday') {
                $day = 5;
            } elseif ($day_name == 'saturday') {
                $day = 6;
            } elseif ($day_name == 'sunday') {
                $day = 7;
            }
            $a = $day_name . "_group";
            foreach ($request->$a as $data) {
                if (isset($data['id']) && $data['id'] != '') {
                    $timetable = Timetable::find($data['id']);
                } else {
                    $timetable = new Timetable();
                }

                $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');
                $session_year_id = getSettings('session_year')['session_year'];

                $currentSemester = Semester::where('session_year_id', $session_year_id)
                    ->get()
                    ->first(fn($s) => $s->current);

                $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

                $subject_teacher_id = SubjectTeacher::select('id')->where('subject_id', $data['subject_id'])
                    ->where('teacher_id', $data['teacher_id'])
                    ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                        if ($currentSemester) {
                            $q->where('semester_id', $currentSemester->id);
                        }
                    })
                    ->pluck('id')->first();

                $timetable->subject_teacher_id = ($subject_teacher_id) ? ($subject_teacher_id) : 0;
                $timetable->class_section_id = $class_section_id;
                $timetable->session_year_id = $request->session_year_id;
                $timetable->semester_id = $request->semester_id ?? null;
                $timetable->start_time = $data['start_time'];
                $timetable->end_time = $data['end_time'];
                $timetable->day = $day;
                $timetable->day_name = $day_name;
                $timetable->live_class_url = $data['live_class_url'];
                $timetable->link_name = $data['link_name'];
                $timetable->save();
            }

            return redirect()->back()->with('success', trans('data_store_successfully'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', trans('error_occurred'));
        }
    }

    public function getSubjectByClassSection(Request $request)
    {
        $class = ClassSchool::withSessionConfigFor($request->session_year_id)
            ->where('id', $request->class_id)
            ->first();

        if ($class && $class->getIncludeSemestersForSession($request->session_year_id) && $request->semester_id) {
            $subjects = ClassSubject::SubjectTeacher()
                ->where('class_id', $request->class_id)
                ->where('session_year_id', $request->session_year_id)
                ->where('semester_id', $request->semester_id)
                ->with('subject')
                ->get();
        } else {
            $subjects = ClassSubject::SubjectTeacher()
                ->where('session_year_id', $request->session_year_id)
                ->where('class_id', $request->class_id)
                ->with('subject')
                ->get();
        }
        return response($subjects);
    }

    public function getClassSemesters(Request $request)
    {
        $class = ClassSchool::withSessionConfigFor($request->session_year_id)
            ->where('id', $request->class_id)
            ->first();

        $hasSemesters = $class && $class->getIncludeSemestersForSession($request->session_year_id);

        if ($hasSemesters) {
            $semesters = Semester::where('session_year_id', $request->session_year_id)
                ->orderBy('id', 'ASC')
                ->get();
        } else {
            $semesters = collect();
        }

        return response()->json([
            'has_semesters' => $hasSemesters,
            'semesters' => $semesters
        ]);
    }

    public function getClassSectionsWithSemesters(Request $request)
    {
        $sessionYearId = $request->session_year_id;
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') && $user->teacher;
        $isClassTeacherScope = $request->scope === 'teacher' && $isTeacher;

        $query = ClassSection::with('class.medium', 'section', 'class.streams');

        // If scope=teacher, limit to class teacher's assigned class sections
        if ($isClassTeacherScope) {
            $classSectionIds = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                ->where('session_year_id', $sessionYearId)
                ->pluck('class_section_id');
            $query->whereIn('id', $classSectionIds);
        } elseif ($isTeacher) {
            // For subject teachers (non class-teacher scope), filter by subject_teachers assignments
            $query->whereHas('subject_teachers', function ($q) use ($sessionYearId, $user) {
                $q->where('session_year_id', $sessionYearId)
                    ->where('teacher_id', $user->teacher->id);
            });
        }

        $classSections = $query->get();
        $result = [];

        foreach ($classSections as $section) {
            $class = ClassSchool::withSessionConfigFor($sessionYearId)
                ->where('id', $section->class->id)
                ->first();

            $hasSemesters = $class && $class->getIncludeSemestersForSession($sessionYearId);

            if ($hasSemesters) {
                // For class-teacher scope, show semesters from class_teachers assignments
                if ($isClassTeacherScope) {
                    $semesterIds = ClassTeacher::where('class_section_id', $section->id)
                        ->where('class_teacher_id', $user->teacher->id)
                        ->where('session_year_id', $sessionYearId)
                        ->whereNotNull('semester_id')
                        ->pluck('semester_id')
                        ->unique()
                        ->values();

                    $semesters = Semester::where('session_year_id', $sessionYearId)
                        ->whereIn('id', $semesterIds)
                        ->orderBy('id', 'ASC')
                        ->get();
                } elseif ($isTeacher) {
                    // For subject-teacher scope, only show semesters they are assigned to
                    $semesters = Semester::where('session_year_id', $sessionYearId)
                        ->whereIn('id', function ($q) use ($section, $sessionYearId, $user) {
                            $q->select('semester_id')
                                ->from('subject_teachers')
                                ->where('class_section_id', $section->id)
                                ->where('session_year_id', $sessionYearId)
                                ->where('teacher_id', $user->teacher->id)
                                ->whereNotNull('semester_id')
                                ->whereNull('deleted_at');
                        })
                        ->orderBy('id', 'ASC')
                        ->get();
                } else {
                    $semesters = Semester::where('session_year_id', $sessionYearId)
                        ->orderBy('id', 'ASC')
                        ->get();
                }

                foreach ($semesters as $semester) {
                    $semesterStartDate = is_string($semester->start_date)
                        ? date('d-m-Y', strtotime($semester->start_date))
                        : $semester->start_date->format('d-m-Y');

                    $semesterEndDate = is_string($semester->end_date)
                        ? date('d-m-Y', strtotime($semester->end_date))
                        : $semester->end_date->format('d-m-Y');

                    $label = $section->class->name . ' ' . $section->section->name
                        . ' - ' . $section->class->medium->name
                        . ($section->class->streams->name ?? '')
                        . ' - ' . $semester->name;

                    $result[] = [
                        'class_section_id' => $section->id,
                        'class_id' => $section->class->id,
                        'semester_id' => $semester->id,
                        'semester_start_date' => $semesterStartDate,
                        'semester_end_date' => $semesterEndDate,
                        'label' => $label,
                    ];
                }
            } else {
                $label = $section->class->name . ' ' . $section->section->name
                    . ' - ' . $section->class->medium->name
                    . ($section->class->streams->name ?? '');

                $result[] = [
                    'class_section_id' => $section->id,
                    'class_id' => $section->class->id,
                    'semester_id' => null,
                    'semester_start_date' => null,
                    'semester_end_date' => null,
                    'label' => $label,
                ];
            }
        }

        return response()->json($result);
    }

    public function getteacherbysubject(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'session_year_id' => 'required|integer',
        ]);

        $classId = ClassSection::where('id', $request->class_section_id)->pluck('class_id');
        $isClassHasSemester = ClassSessionConfig::classHasSemester($classId,  $request->session_year_id);
        $currentSemester = Semester::where('session_year_id', $request->session_year_id)
            ->get()
            ->first(fn($s) => $s->current);

        $teacher = SubjectTeacher::where([
            'class_section_id' => $request->class_section_id,
            'subject_id' => $request->subject_id,
            'session_year_id' => $request->session_year_id
        ])
            ->with('teacher')
            ->when($isClassHasSemester && $currentSemester, function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester->id);
            })
            ->get();
        return response(
            $teacher
        );
    }

    public function checkTimetable(Request $request)
    {
        $query = Timetable::with('subject_teacher')->where([
            'class_section_id' => $request->class_section_id,
            'session_year_id' => $request->session_year_id,
            'day' => $request->day
        ]);

        if ($request->semester_id) {
            $query->where('semester_id', $request->semester_id);
        }

        return response($query->get());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('timetable-delete')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        try {
            Timetable::find($id)->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully')
            ];
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }


    public function class_timetable()
    {
        // check the user if teacher exists
        $user = Auth::user()->teacher;
        if ($user) {
            $session_years = SessionYear::orderBy('id', 'ASC')->get();
            return view('timetable.class_timetable', compact('session_years'));
        } else {
            // if teacher doesn't exists then send the class section data for select option directly to view
            if (!Auth::user()->can('timetable-list') || !Auth::user()->can('class-timetable')) {
                $response = array(
                    'error' => true,
                    'message' => trans('no_permission_message')
                );
                return response()->json($response);
            }
            $session_years = SessionYear::orderBy('id', 'ASC')->get();
            return view('timetable.class_timetable', compact('session_years'));
        }
    }

    public function gettimetablebyclass(Request $request)
    {
        Session::put('class_timetable', $request->class_section_id);

        $query = Timetable::where('class_section_id', $request->class_section_id)
            ->where('session_year_id', $request->session_year_id);

        if ($request->semester_id) {
            $query->where('semester_id', $request->semester_id);
        }

        $timetable = (clone $query)->with('subject_teacher')
            ->orderBy('day', 'asc')
            ->get();

        $day = (clone $query)->select('day', 'day_name')
            ->groupBy('day', 'day_name')
            ->get();

        return [
            'timetable' => $timetable,
            'days' => $day
        ];
    }

    public function teacher_timetable()
    {
        // check the user if teacher exists
        $user = Auth::user()->teacher;
        if ($user) {
            // if teacher exists then send the timetable data directly to view by its credentials
            $session_years = SessionYear::orderBy('id', 'ASC')->get();

            return view('timetable.teacher_timetable', compact('session_years'));
        } else {
            // if teacher doesn't exists then send the class section data for select option directly to view
            if (!Auth::user()->can('timetable-list') || !Auth::user()->can('teacher-timetable')) {
                $response = array(
                    'error' => true,
                    'message' => trans('no_permission_message')
                );
                return response()->json($response);
            }

            $teacher = Teacher::with('user')->teachers()->get();
            $session_years = SessionYear::orderBy('id', 'ASC')->get();

            return view('timetable.teacher_timetable', compact('teacher', 'session_years'));
        }
    }

    public function gettimetablebyteacher(Request $request)
    {
        $subject_teacher = SubjectTeacher::select('id')->where('teacher_id', $request->teacher_id)->pluck('id');
        $timetable = array();
        $day = array();
        for ($i = 0; $i < count($subject_teacher); $i++) {
            $timetable[] = Timetable::with('subject_teacher', 'class_section', 'semester')
                ->where('session_year_id', $request->session_year_id)
                ->where('subject_teacher_id', $subject_teacher[$i])
                ->get();
        }
        $day[] = Timetable::select('day', 'day_name')
            ->whereIn('subject_teacher_id', $subject_teacher)
            ->where('session_year_id', $request->session_year_id)
            ->groupBy('day', 'day_name')
            ->get();

        return $data = [
            'timetable' => $timetable,
            'days' => $day
        ];
    }

    public function getTimetableBySubjectTeacherClass(Request $request)
    {
        $teacher = Auth::user()->teacher;
        $subject_teacher_id = SubjectTeacher::where('teacher_id', $teacher->id)->pluck('id')->toArray();

        if ($request->class_section_id) {

            $timetableQuery = Timetable::whereIn('subject_teacher_id', $subject_teacher_id)
                ->where('session_year_id', $request->session_year_id)
                ->where('class_section_id', $request->class_section_id);

            if ($request->semester_id) {
                $timetableQuery->where('semester_id', $request->semester_id);
            }

            $timetable = (clone $timetableQuery)->with('subject_teacher', 'class_section', 'semester')
                ->orderBy('day', 'asc')->get();

            $day = (clone $timetableQuery)->select('day', 'day_name')
                ->groupBy('day', 'day_name')->get();
        } else {
            $timetableQuery = Timetable::whereIn('subject_teacher_id', $subject_teacher_id)
                ->where('session_year_id', $request->session_year_id);

            $timetable = (clone $timetableQuery)->with('subject_teacher', 'class_section', 'semester')
                ->orderBy('day', 'asc')->get();

            $day = (clone $timetableQuery)->select('day', 'day_name')
                ->groupBy('day', 'day_name')->get();
        }
        return $data = [
            'timetable' => $timetable,
            'days' => $day
        ];
    }

    public function linkUpdate(Request $request)
    {
        try {
            $timetable = Timetable::where('id', $request->edit_id)->first();
            $timetable->live_class_url = $request->live_class_url;
            $timetable->link_name = $request->link_name;
            $timetable->save();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully')
            ];
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function getTimeatableDetails($id)
    {
        $timetable = Timetable::where('id', $id)->first();
        return response($timetable);
    }
}
