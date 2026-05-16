<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ClassTeacherController extends Controller
{
    public function teacher()
    {
        if (! Auth::user()->can('class-teacher-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::with('class.medium', 'section', 'class.streams')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        $class_teacher_ids = ClassTeacher::whereNot('class_teacher_id', null)->pluck('class_teacher_id');
        // $assign_teacher_id = ClassSection::select('class_teacher_id')->whereNotNull('class_teacher_id')->get()->pluck('class_teacher_id');
        // $teachers = Teacher::whereNotIn('id', $assign_teacher_id)->with('user')->get();
        $classes = ClassSchool::orderBy('id', 'DESC')->with('medium', 'streams')->get();

        return view('class.teacher', compact('class_section', 'classes', 'session_years'));
    }

    public function assign_teacher(Request $request)
    {
        if (! Auth::user()->can('class-teacher-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $request->validate([
            'class_section_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'session_year_id' => 'required|integer',
            'semester_id' => 'nullable|integer',
        ]);

        $session_year_id = $request->session_year_id;

        try {
            $teacher = Teacher::findOrFail($request->teacher_id);
            $class_teacher = ClassTeacher::updateOrCreate(
                [
                    'class_section_id' => $request->class_section_id,
                    'class_teacher_id' => $request->teacher_id,
                    'session_year_id' => $session_year_id,
                    'semester_id' => $request->semester_id ?: null,
                ]
            );

            if ($class_teacher->wasRecentlyCreated) {
                $permission = [
                    'class-teacher',
                    'student-leave-approve',
                ];
                $teacher->user->givePermissionTo($permission);
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

    public function show()
    {
        if (! Auth::user()->can('class-teacher-list')) {
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
            $offset = (int) $_GET['offset'];
        }

        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $session_year_id = $_GET['session_year_id'];

        $sql = ClassSection::with([
            'class.medium',
            'class.streams',
            'section',
            'classTeachers' => function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id)
                    ->wherePivotNull('deleted_at');
            },
            'classTeachers.user',
        ]);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orWhereHas('class.medium', function ($q) use ($search) {
                    $q->where('classes.name', 'LIKE', "%$search%")->orwhere('mediums.name', 'LIKE', "%$search%");
                })
                ->orWhereHas('section', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })
                ->orWhereHas('classTeachers.user', function ($q) use ($search) {
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%".$search."%'")->orwhere('users.first_name', 'LIKE', "%$search%")->orwhere('users.last_name', 'LIKE', "%$search%");
                });
        }
        if ($_GET['class_id']) {
            $sql = $sql->where('class_id', $_GET['class_id']);
        }

        $res = $sql->orderBy($sort, $order)->get();

        // Fetch semesters for the selected session year
        $semesters = Semester::where('session_year_id', $session_year_id)->orderBy('id', 'ASC')->get();
        $filter_semester_id = $_GET['semester_id'] ?? null;

        $bulkData = [];
        $rows = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

            $includesSemesters = $row->class->getIncludeSemestersForSession($session_year_id);

            if ($includesSemesters && $semesters->isNotEmpty()) {
                // Filter to specific semester if selected, otherwise show all
                $semestersToShow = $filter_semester_id
                    ? $semesters->where('id', $filter_semester_id)
                    : $semesters;

                foreach ($semestersToShow as $semester) {
                    // Filter teachers for this specific semester
                    $semesterTeachers = $row->classTeachers->filter(function ($teacher) use ($semester) {
                        return $teacher->pivot->semester_id == $semester->id;
                    });

                    $class_teacher_ids = [];
                    $class_teacher_name = [];
                    foreach ($semesterTeachers as $class_teacher) {
                        $class_teacher_ids[] = $class_teacher->pivot->class_teacher_id;
                        $class_teacher_name[] = $class_teacher->user->first_name.' '.$class_teacher->user->last_name ?? '-';
                    }

                    $tempRow = [];
                    $tempRow['id'] = $row->id;
                    $tempRow['class_id'] = $row->class_id;
                    $tempRow['section_id'] = $row->section_id;
                    $tempRow['no'] = $no++;
                    $tempRow['class'] = $row->class->name.' - '.$row->class->medium->name;
                    $tempRow['stream_name'] = $row->class->streams->name ?? '-';
                    $tempRow['section'] = $row->section->name;
                    $tempRow['semester_id'] = $semester->id;
                    $tempRow['semester_name'] = $semester->name;
                    $tempRow['teacher_id'] = $class_teacher_ids ?? '-';
                    $tempRow['teachers'] = $class_teacher_name ?? '-';
                    $tempRow['operate'] = $operate;
                    $rows[] = $tempRow;
                }
            } else {
                // No semesters — skip if a specific semester filter is applied
                if ($filter_semester_id) {
                    continue;
                }

                // Teachers without semester (null semester_id)
                $class_teacher_ids = [];
                $class_teacher_name = [];
                foreach ($row->classTeachers as $class_teacher) {
                    $class_teacher_ids[] = $class_teacher->pivot->class_teacher_id;
                    $class_teacher_name[] = $class_teacher->user->first_name.' '.$class_teacher->user->last_name ?? '-';
                }

                $tempRow = [];
                $tempRow['id'] = $row->id;
                $tempRow['class_id'] = $row->class_id;
                $tempRow['section_id'] = $row->section_id;
                $tempRow['no'] = $no++;
                $tempRow['class'] = $row->class->name.' - '.$row->class->medium->name;
                $tempRow['stream_name'] = $row->class->streams->name ?? '-';
                $tempRow['section'] = $row->section->name;
                $tempRow['semester_id'] = null;
                $tempRow['semester_name'] = '-';
                $tempRow['teacher_id'] = $class_teacher_ids ?? '-';
                $tempRow['teachers'] = $class_teacher_name ?? '-';
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
        }

        $total = count($rows);

        // Apply pagination AFTER generating semester rows
        $rows = array_slice($rows, $offset, $limit);

        $bulkData['total'] = $total;
        $bulkData['rows'] = array_values($rows);

        return response()->json($bulkData);
    }

    public function removeClassTeacher($id, $class_teacher_id, Request $request)
    {
        try {
            $semester_id = $request->input('semester_id');

            $query = ClassTeacher::where('class_section_id', $id)
                ->where('class_teacher_id', $class_teacher_id);

            if (! is_null($semester_id)) {
                $query->where('semester_id', $semester_id);
            }

            $class_teacher = $query->first();

            if (! $class_teacher) {
                return response()->json([
                    'error' => true,
                    'message' => 'Class teacher not found',
                ]);
            }

            $permission = [
                'class-teacher',
                'student-leave-approve',
            ];

            $old_teacher = Teacher::with('user')->find($class_teacher_id);

            if ($old_teacher && $old_teacher->user) {
                foreach ($permission as $perm) {
                    if ($old_teacher->user->hasPermissionTo($perm)) {
                        $old_teacher->user->revokePermissionTo($perm);
                    }
                }
            }

            $class_teacher->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    public function getClassTeacherlist($class_section_id, $session_year_id)
    {
        $semester_id = request('semester_id');
        $response = Teacher::with('user')->orWhereHas('classTeachers', function ($q) use ($class_section_id, $session_year_id, $semester_id) {
            $q->where('class_section_id', $class_section_id);
            $q->where('session_year_id', $session_year_id);
            if ($semester_id) {
                $q->where('semester_id', $semester_id);
            } else {
                $q->whereNull('semester_id');
            }
        })->get();

        return response()->json($response);
    }

    public function getNotClassTeacherList($class_section_id, $session_year_id)
    {
        $semester_id = request('semester_id');
        $response = Teacher::with('user')->whereDoesntHave('classTeachers', function ($q) use ($class_section_id, $session_year_id, $semester_id) {
            $q->where('class_section_id', $class_section_id);
            $q->where('session_year_id', $session_year_id);
            if ($semester_id) {
                $q->where('semester_id', $semester_id);
            } else {
                $q->whereNull('semester_id');
            }
        })->get();

        return response()->json($response);
    }
}
