<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassSchool;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\File;
use App\Models\Settings;
use App\Models\Students;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('announcement-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::SubjectTeacher()->with('class.medium', 'section')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        return view('announcement.index', compact('class_section', 'session_years'));
    }

    public function getClassSectionsForAnnouncement(Request $request)
    {
        $session_year_id = $request->session_year_id ?: getSettings('session_year')['session_year'];
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') && $user->teacher;

        $class_sections = ClassSection::SubjectTeacher()->with('class.medium', 'section', 'class.streams')
            ->whereHas('subject_teachers', function ($q) use ($session_year_id, $user, $isTeacher) {
                $q->where('session_year_id', $session_year_id);
                if ($isTeacher) {
                    $q->where('teacher_id', $user->teacher->id);
                }
            })->get();

        $result = [];
        foreach ($class_sections as $section) {
            $class = ClassSchool::withSessionConfigFor($session_year_id)
                ->where('id', $section->class->id)
                ->first();

            $hasSemesters = $class && $class->getIncludeSemestersForSession($session_year_id);

            $baseName = $section->class->name . ' ' . $section->section->name . ' - ' . $section->class->medium->name . ' ' . ($section->class->streams->name ?? '');

            if ($hasSemesters) {
                if ($isTeacher) {
                    $semesters = Semester::where('session_year_id', $session_year_id)
                        ->whereIn('id', function ($q) use ($section, $session_year_id, $user) {
                            $q->select('semester_id')
                                ->from('subject_teachers')
                                ->where('class_section_id', $section->id)
                                ->where('session_year_id', $session_year_id)
                                ->where('teacher_id', $user->teacher->id)
                                ->whereNotNull('semester_id')
                                ->whereNull('deleted_at');
                        })
                        ->orderBy('id', 'ASC')
                        ->get();
                } else {
                    $semesters = Semester::where('session_year_id', $session_year_id)
                        ->orderBy('id', 'ASC')
                        ->get();
                }

                foreach ($semesters as $semester) {
                    $result[] = [
                        'id' => $section->id,
                        'name' => $baseName . ' - ' . $semester->name,
                        'class_id' => $section->class->id,
                        'semester_id' => $semester->id,
                    ];
                }
            } else {
                $result[] = [
                    'id' => $section->id,
                    'name' => $baseName,
                    'class_id' => $section->class->id,
                    'semester_id' => null,
                ];
            }
        }
        return response()->json($result);
    }

    public function getAssignData(Request $request)
    {
        $data = $request->data;
        $class_id = $request->class_id;
        $class_section_id = $request->class_section_id;
        $semester_id = $request->semester_id;
        $session_year_id = $request->session_year_id ?: getSettings('session_year')['session_year'];

        if ($data == 'class_section' && $class_id != '') {
            $user = Auth::user();

            if ($user->hasRole('Teacher') && $user->teacher) {
                // Only return subjects assigned to this teacher
                $query = SubjectTeacher::where('class_section_id', $class_section_id)
                    ->where('teacher_id', $user->teacher->id)
                    ->where('session_year_id', $session_year_id);

                if ($semester_id) {
                    $query->where('semester_id', $semester_id);
                }

                $info = $query->with('subject')->get();
            } else {
                $query = ClassSubject::where('class_id', $class_id)
                    ->where('session_year_id', $session_year_id);

                if ($semester_id) {
                    $query->where('semester_id', $semester_id);
                }

                $info = $query->with('subject')->get();
            }
        } elseif ($data == 'class') {
            $info = ClassSchool::get();
        } else {
            $info = '';
        }
        return response()->json($info);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('announcement-create')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'set_data' => 'required',
            'file' => 'nullable|array',
            'file.*' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:4096'
        ], [
            'set_data.required' => 'The Assign To Field is Required'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $user = array();
            // $data = getSettings('session_year');
            if (!empty($request->get_data)) {
                $getdata = count($request->get_data);
            } else {
                $getdata = 1;
            }
            for ($i = 0; $i < $getdata; $i++) {
                $announcement = new Announcement();
                $announcement->title = $request->title;
                $announcement->description = $request->description;
                $announcement->session_year_id = getSettings('session_year')['session_year'];
                $announcement->semester_id = $request->semester_id ?: null;
                if (!empty($request->set_data)) {
                    if ($request->set_data == 'class_section') {
                        $teacher_id = Auth::user()->teacher->id;
                        $subject_teacher_id = SubjectTeacher::select('id')->where(['class_section_id' => $request->class_section_id, 'teacher_id' => $teacher_id, 'subject_id' => $request->get_data[$i]])->get()->pluck('id');
                        $subject_name = SubjectTeacher::where(['teacher_id' => $teacher_id, 'class_section_id' => $request->class_section_id, 'subject_id' => $request->get_data[$i]])->with('subject')->get();
                        if (count($subject_name)) {
                            if (count($subject_teacher_id) != 0) {
                                for ($j = 0; $j < count($subject_teacher_id); $j++) {
                                    $subject_teacher = SubjectTeacher::find($subject_teacher_id[$j]);
                                    $announcement->table()->associate($subject_teacher);
                                    $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');
                                }
                            }
                            $title = 'New announcement in ' . $subject_name[0]->subject->name;
                            $body = $request->title;
                        } else {
                            $response = array(
                                'error' => true,
                                'message' => trans('no_data_found')
                            );
                            return response()->json($response);
                        }
                    }
                    if ($request->set_data == 'class') {
                        $class = ClassSchool::find($request->get_data[$i]);
                        $announcement->table()->associate($class);
                        $get_class = ClassSection::select('id')->where('class_id', $request->get_data[$i])->get()->pluck('id');
                        $user = Students::select('user_id')->whereIn('class_section_id', $get_class)->get()->pluck('user_id');
                        $title = $request->title;
                        $body = $request->description;
                    }
                    if ($request->set_data == 'noticeboard') {
                        $announcement->table_id = null;
                        $announcement->table_type = "";
                        $user = Students::select('user_id')->get()->pluck('user_id');
                        $title = 'Noticeboard updated';
                        $body = $request->title;
                    }
                }
                $type = $request->set_data;
                $image = null;
                $userinfo = null;

                $announcement->save();
                if ($request->hasFile('file')) {
                    foreach ($request->file as $file_upload) {
                        $file = new File();
                        $file->file_name = $file_upload->getClientOriginalName();
                        $file->type = 1;
                        $file->file_url = $file_upload->store('announcement', 'public');
                        $file->modal()->associate($announcement);
                        $file->save();
                    }
                }
                sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            }
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
                $message = trans('error_occurred');
                $exceptionMessage = strtolower((string) $e->getMessage());
                if (str_contains($exceptionMessage, 'permission') || str_contains($exceptionMessage, 'unauthorized')) {
                    $message = trans('no_permission_message');
                } elseif (str_contains($exceptionMessage, 'subject') && str_contains($exceptionMessage, 'not')) {
                    $message = trans('no_data_found');
                }
                $response = array(
                    'error' => true,
                    'message' => $message
                );
            }
        }
        return response()->json($response);
    }

    public function update(Request $request)
    {
        if (!Auth::user()->can('announcement-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $request->validate([
            'title' => 'required',
            'set_data' => 'required'
        ],  [
            'set_data.required' => 'The Assign To Field is required.'
        ]);
        try {

            $user = array();
            $title = ''; // Initialize $title to avoid undefined variable
            $body = '';  // Initialize $body to avoid undefined variable
            if (Auth::user()->teacher) {
                $teacher_id = Auth::user()->teacher->id;
            }
            $announcement = Announcement::find($request->edit_id);
            $announcement->title = $request->title;
            $announcement->description = $request->description;
            $announcement->semester_id = $request->semester_id ?: null;
            if (!empty($request->set_data)) {
                if ($request->set_data == 'class_section') {
                    $subject_teacher_id = SubjectTeacher::select('id')->where(['class_section_id' => $request->class_section_id, 'subject_id' => $request->get_data, 'teacher_id' => $teacher_id])->get()->pluck('id');
                    $subject_name = SubjectTeacher::where(['class_section_id' => $request->class_section_id, 'subject_id' => $request->get_data, 'teacher_id' => $teacher_id])->with('subject')->get();
                    if (count($subject_teacher_id) != 0) {
                        for ($j = 0; $j < count($subject_teacher_id); $j++) {
                            $subject_teacher = SubjectTeacher::find($subject_teacher_id[$j]);
                            $announcement->table()->associate($subject_teacher);
                            $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');
                        }
                        if (isset($subject_name[0]) && isset($subject_name[0]->subject)) {
                            $title = 'Update announcement in ' . $subject_name[0]->subject->name;
                        } else {
                            $title = 'Update announcement';
                        }
                        $body = $request->title;
                    }
                }
                if ($request->set_data == 'class') {
                    $class = ClassSchool::find($request->get_data);
                    $announcement->table()->associate($class);
                    $get_class = ClassSection::select('id')->where('class_id', $request->get_data)->get()->pluck('id');
                    $user = Students::select('user_id')->whereIn('class_section_id', $get_class)->get()->pluck('user_id');
                    $title = $request->title;
                    $body = $request->description;
                }
                if ($request->set_data == 'noticeboard') {
                    $announcement->table_id = null;
                    $announcement->table_type = "";
                    $user = Students::select('user_id')->get()->pluck('user_id');
                    $title = 'Noticeboard updated';
                    $body = $request->title;
                }
            }
            $type = $request->set_data;
            $image = null;
            $userinfo = null;

            $announcement->save();

            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File();
                    $file->file_name = $file_upload->getClientOriginalName();
                    $file->type = 1;
                    $file->file_url = $file_upload->store('announcement', 'public');
                    $file->modal()->associate($announcement);
                    $file->save();
                }
            }
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
            DB::commit();
        } catch (Throwable $e) {
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

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (!Auth::user()->can('announcement-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
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

        $session_year_id = $_GET['session_year_id'];
        $sql = Announcement::with('table', 'file', 'semester')->where('session_year_id', $session_year_id);

        // Filter announcements for teachers
        if (Auth::user()->hasRole('Teacher')) {
            $teacher_id = Auth::user()->teacher->id;
            $sql->where(function ($query) use ($teacher_id) {
                $query->whereHasMorph('table', ['App\\Models\\SubjectTeacher'], function ($q) use ($teacher_id) {
                    $q->where('teacher_id', $teacher_id);
                });
            });
        }

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('title', 'LIKE', "%$search%")
                    ->orwhere('description', 'LIKE', "%$search%");
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
        $user = Auth::user();
        foreach ($res as $row) {
            $operate = '';
            if ($user->hasRole('Super Admin') && $row->table_type == "") {
                $operate .= '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-announcement-form" data-id="' . $row->id . '"  title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata" data-id="' . $row->id . '" data-url="' . url('announcement', $row->id) . '" title="Delete"><i class="fa fa-trash"></i></a>';
            } elseif ($user->hasRole('Teacher') && $row->table_type == "App\\Models\\SubjectTeacher") {
                $operate .= '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-announcement-form" data-id="' . $row->id . '"  title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata" data-id="' . $row->id . '" data-url="' . url('announcement', $row->id) . '" title="Delete"><i class="fa fa-trash"></i></a>';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['title'] = $row->title;
            $tempRow['description'] = $row->description;
            $tempRow['type'] = $row->table_type;
            if ($tempRow['type'] == "App\\Models\\ClassSection") {
                $assign = 'class_section';
                $class = $row->table->class->name . ' - ' . $row->table->section->name;
                $class1 = $class;
            }
            if ($tempRow['type'] == "App\\Models\\ClassSchool") {
                $assign = 'class';
                $class = $row->table->name;
                $class1 = $class;
            }
            if ($tempRow['type'] == "App\\Models\\SubjectTeacher") {
                $assign = 'Subject';
                $class = $row->table;
                $semesterName = $row->semester ? ' - ' . $row->semester->name : '';
                $class1 = $row->table->class_section->class->name . '-' . $row->table->class_section->section->name . $semesterName . ' - ' . $row->table->subject->name;
            }
            if ($tempRow['type'] == "") {
                $assign = 'noticeboard';
                $class = trans("noticeboard");
                $class1 = $class;
            }
            $tempRow['assign'] = $assign;
            $tempRow['assign_to'] = $class;
            $tempRow['assignto'] = $class1;
            $tempRow['get_data'] = $row->table_id;
            $tempRow['file'] = $row->file;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('announcement-delete')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        try {
            Announcement::find($id)->delete();
            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
}
