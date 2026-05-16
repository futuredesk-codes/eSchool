<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\ChatFile;
use App\Models\ChatMessage;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamTimetable;
use App\Models\File;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\MultipleEvent;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\ReadMessage;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\UserNotification;
use App\Rules\uniqueLessonInClass;
use App\Rules\uniqueTopicInLesson;
use App\Services\AcademicCalendarService;
use App\Services\GalleryService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TeacherApiController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $auth = Auth::user();

            if (! $auth->hasRole('Teacher')) {
                ResponseService::errorResponse('Invalid Login Credentials', null, 101);
            }
            $token = $auth->createToken($auth->first_name)->plainTextToken;
            $user = $auth->load(['teacher']);

            $dynamicFields = null;
            $dynamicField = $user->teacher->dynamic_fields;
            $user = flattenMyModel($user);
            if (! empty($dynamicField)) {
                $data = json_decode($dynamicField, true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if ($item != null) {
                            foreach ($item as $key => $value) {
                                $dynamicFields[$key] = $value;
                            }
                        }
                    }
                } else {
                    $dynamicFields = $data;
                }
            } else {
                $dynamicFields = null;
            }

            $user = array_merge($user, ['dynamic_fields' => $dynamicFields]);

            if ($request->fcm_id) {
                $auth->fcm_id = $request->fcm_id;
                $auth->save();
            }
            if ($request->device_type) {
                $auth->device_type = $request->device_type;
                $auth->save();
            }
            ResponseService::successResponse('User logged-in!', $user, ['token' => $token], 100);
        } else {
            ResponseService::errorResponse('Invalid Login Credentials', null, 101);
        }
    }

    /**
     * Get academic calendar PDF combining holidays, events, and exams.
     */
    public function getAcademicCalendarPdf(AcademicCalendarService $service)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher) {
                throw new \Exception('Teacher record not found.');
            }

            $classIds = ClassSection::whereIn(
                'id',
                ClassTeacher::where('class_teacher_id', $teacher->id)->pluck('class_section_id')
            )
                ->pluck('class_id')
                ->toArray();

            if (empty($classIds)) {
                throw new \Exception('No classes found for this teacher.');
            }

            $pdf = $service->generate($classIds);

            return ResponseService::successResponse(
                'Academic Calendar PDF fetched successfully',
                null,
                ['pdf' => $pdf]
            );
        } catch (Throwable $e) {
            return ResponseService::errorResponse(
                'Error occurred while generating academic calendar PDF',
                null,
                103,
                $e
            );
        }
    }

    /**
     * Returns [$classTeacherSections, $otherClassSections] for the given teacher
     * and session year, filtered to the current semester where applicable.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Collection, 1: \Illuminate\Database\Eloquent\Collection}
     */
    private function getTeacherClassSections($teacher, $sessionYearId): array
    {
        $currentSemester = Semester::where('session_year_id', $sessionYearId)
            ->get()
            ->first(fn ($s) => $s->current);

        $classSectionsWith = ['class.medium', 'section', 'class.streams', 'class.shifts'];

        // Class Teacher Sections
        $classTeacherRecords = ClassTeacher::when(
            $currentSemester,
            fn ($q) => $q->where(fn ($q) => $q->where('semester_id', $currentSemester->id)
                ->orWhereNull('semester_id'))
        )
            ->where('session_year_id', $sessionYearId)
            ->where('class_teacher_id', $teacher->id)
            ->whereIn(
                'class_section_id',
                $teacher->class_sections()
                    ->where('session_year_id', $sessionYearId)
                    ->pluck('class_section_id')
            )
            ->get();

        $classTeacherSections = ClassSection::whereIn('id', $classTeacherRecords->pluck('class_section_id'))
            ->with($classSectionsWith)
            ->get();

        // Subject Teacher Sections (excluding class teacher ones)
        $subjectSectionIds = SubjectTeacher::when(
            $currentSemester,
            fn ($q) => $q->where(fn ($q) => $q->where('semester_id', $currentSemester->id)
                ->orWhereNull('semester_id'))
        )
            ->where('session_year_id', $sessionYearId)
            ->where('teacher_id', $teacher->id)
            ->pluck('class_section_id');

        $otherClassSections = ClassSection::whereIn('id', $subjectSectionIds)
            ->with($classSectionsWith)
            ->get()
            ->diff($classTeacherSections);

        return [$classTeacherSections, $otherClassSections];
    }

    public function classes(Request $request)
    {
        try {
            $settings = getSettings('session_year');
            $sessionYearId = $settings['session_year'];
            $teacher = $request->user()->teacher;

            [$classTeacherSections, $otherClassSections] = $this->getTeacherClassSections(
                $teacher,
                $sessionYearId
            );

            ResponseService::successResponse('Teacher Classes Fetched Successfully.', [
                'class_teacher' => $classTeacherSections->isNotEmpty() ? $classTeacherSections : (object) null,
                'other' => $otherClassSections,
            ]);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function subjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter_by_semester' => 'nullable|boolean',
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $filter_by_semester = $request->filter_by_semester ? $request->filter_by_semester : null;

            $user = $request->user();
            $teacher = $user->teacher;
            $subjects = $teacher->subjects()
                ->where('session_year_id', $session_year_id)
                ->with([
                    'subject',
                    'semester:id,name,session_year_id,start_date,end_date',
                ]);

            if ($request->class_section_id) {
                // if ($filter_by_semester) {
                //     $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id');
                //     $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

                //     $currentSemester = Semester::where('session_year_id', $session_year_id)
                //         ->get()
                //         ->first(fn($s) => $s->current);

                //     $subjects = $subjects->where('class_section_id', $request->class_section_id)
                //         ->when($isClassHaveSemester && $currentSemester, function ($query) use ($currentSemester) {
                //             $query->where('semester_id', $currentSemester->id);
                //         });
                // }
                $subjects = $subjects->where('class_section_id', $request->class_section_id);
            }

            if ($request->subject_id) {
                $subjects = $subjects->where('subject_id', $request->subject_id);
            }

            $subjects = $subjects->with('subject')->get()->map(function ($item) {
                $subject = clone $item->subject;
                $subject->setRelation('semester', $item->semester);
                $item->setRelation('subject', $subject);
                $item->unsetRelation('semester');

                return $item;
            });

            ResponseService::successResponse('Teacher Subject Fetched Successfully.', $subjects);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAssignment(Request $request): void
    {
        ResponseService::noPermissionThenSendJson('assignment-list');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'semester_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];

            $query = Assignment::assignmentteachers()
                ->where('session_year_id', $sessionYearId)
                ->with('class_section', 'file', 'subject', 'semester');

            if ($request->filled('class_section_id')) {
                $query->where('class_section_id', $request->class_section_id);
                if ($request->semester_id) {
                    $this->applySemesterFilter($query, $request->class_section_id, $sessionYearId, $request->semester_id);
                }
            }

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $data = $query->orderByDesc('id')->paginate();

            $data->getCollection()->transform(function ($item) {
                $item->subject->semester = $item->semester;
                unset($item->semester);

                return $item;
            });

            ResponseService::successResponse('Assignment Fetched Successfully.', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    private function applySemesterFilter($query, $classSectionId, $sessionYearId, $semesterId = null): void
    {
        $classId = ClassSection::where('id', $classSectionId)->value('class_id');
        $isClassHaveSemester = ClassSessionConfig::classHasSemester($classId, $sessionYearId);

        if (! $isClassHaveSemester) {
            return;
        }
        if (! $semesterId) {
            $semesterId = Semester::all()->first(fn ($s) => $s->current);
        }

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
    }

    public function createAssignment(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-create');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric|exists:class_sections,id',
            'subject_id' => 'required|numeric|exists:subjects,id',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
            'name' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'due_date' => 'required|date_format:d-m-Y H:i',
            'points' => 'nullable|numeric|min:0',
            'resubmission' => 'nullable|boolean',
            'extra_days_for_resubmission' => 'nullable|numeric|min:0',
        ], [
            'class_section_id.exists' => 'Selected class section does not exist.',
            'subject_id.exists' => 'Selected subject does not exist.',
            'due_date.date_format' => 'Due date must be in format DD-MM-YYYY HH:MM (e.g., 07-08-2025 15:45)',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $session_year_id = getSettings('session_year')['session_year'];

            // Semester
            $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($classId, $session_year_id);
            $semesterId = $isClassHaveSemester ? $request->semester_id : null;

            if ($isClassHaveSemester && ! $semesterId) {
                ResponseService::errorResponse('Semester id required.', null, 400);
            }

            $due_date = Carbon::createFromFormat('d-m-Y H:i', $request->due_date);

            $assignment = new Assignment;
            $assignment->class_section_id = $request->class_section_id;
            $assignment->subject_id = $request->subject_id;
            $assignment->semester_id = $semesterId;
            $assignment->name = trim($request->name);
            $assignment->instructions = trim($request->instructions);
            $assignment->due_date = $due_date->format('Y-m-d H:i:s');
            $assignment->points = $request->points;
            $assignment->resubmission = $request->resubmission ? 1 : 0;
            $assignment->extra_days_for_resubmission = $request->resubmission ? $request->extra_days_for_resubmission : null;
            $assignment->session_year_id = $session_year_id;

            // Get class subject information
            $class_subject = ClassSubject::where('subject_id', $request->subject_id)->first();

            if (! $class_subject) {
                ResponseService::errorResponse('Subject is not assigned to any class.', null, 400);
            }

            // Get students based on subject type
            $user = [];
            if ($class_subject->type == 'Elective') {
                $student_ids = Students::where('class_section_id', $request->class_section_id)->pluck('id');

                foreach ($student_ids as $student_id) {
                    $student_subject = StudentSubject::where('student_id', $student_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    if ($student_subject) {
                        $user_id = Students::where('id', $student_subject->student_id)
                            ->where('class_section_id', $request->class_section_id)
                            ->pluck('user_id')
                            ->first();
                        if ($user_id) {
                            $user[] = $user_id;
                        }
                    }
                }
            } else {
                $user = Students::where('class_section_id', $request->class_section_id)
                    ->pluck('user_id')
                    ->toArray();
            }

            // Notification
            $subject_name = Subject::where('id', $request->subject_id)->value('name');
            $title = 'New assignment added in '.$subject_name;
            $body = $request->name;
            $type = 'assignment';

            $notification = new Notification;
            $notification->send_to = 3;
            $notification->session_year_id = $session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            foreach ($user as $user_id) {
                if ($user_id) {
                    $user_notification = new UserNotification;
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $user_id;
                    $user_notification->save();
                }
            }

            $assignment->save();

            if (! empty($user)) {
                sendSimpleNotification($user, $title, $body, $type, null, null);
            }

            // File uploads
            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = $file_upload->getClientOriginalName();
                    $file->type = 1;
                    $file->file_url = $file_upload->store('assignment', 'public');
                    $file->modal()->associate($assignment);
                    $file->save();
                }
            }

            ResponseService::successResponse('Assignment Created Successfully.');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateAssignment(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-edit');

        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|numeric',
            'class_section_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'name' => 'required',
            'instructions' => 'nullable',
            'due_date' => 'required|date',
            'points' => 'nullable',
            'resubmission' => 'nullable|boolean',
            'extra_days_for_resubmission' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $assignment = Assignment::find($request->assignment_id);
            $assignment->class_section_id = $request->class_section_id;
            $assignment->subject_id = $request->subject_id;
            $assignment->name = $request->name;
            $assignment->instructions = $request->instructions;
            $assignment->due_date = Carbon::parse($request->due_date)->format('Y-m-d H:i:s');
            $assignment->points = $request->points;
            if ($request->resubmission) {
                $assignment->resubmission = 1;
                $assignment->extra_days_for_resubmission = $request->extra_days_for_resubmission;
            } else {
                $assignment->resubmission = 0;
                $assignment->extra_days_for_resubmission = null;
            }

            $assignment->session_year_id = $session_year_id;
            $subject_name = Subject::select('name')->where('id', $request->subject_id)->pluck('name')->first();
            $title = 'Update assignment in '.$subject_name;
            $body = $request->name;
            $type = 'assignment';
            $image = null;
            $userinfo = null;

            $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');

            $notification = new Notification;
            $notification->send_to = 3;
            $notification->session_year_id = $session_year_id;
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

            $assignment->save();
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = $file_upload->getClientOriginalName();
                    $file->type = 1;
                    $file->file_url = $file_upload->store('assignment', 'public');
                    $file->modal()->associate($assignment);
                    $file->save();
                }
            }
            ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteAssignment(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-delete');

        try {
            $assignment = Assignment::find($request->assignment_id);
            $assignment->delete();
            ResponseService::successResponse('data_delete_successfully');
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAssignmentSubmission(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-submission');
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sql = AssignmentSubmission::assignmentsubmissionteachers()->with(
                'assignment.subject:id,name',
                'student:id,user_id',
                'student.user:first_name,last_name,id,image',
                'file'
            );
            $data = $sql->where('assignment_id', $request->assignment_id)->get();
            ResponseService::successResponse('Assignment Fetched Successfully.', $data);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateAssignmentSubmission(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-submission');

        $validator = Validator::make($request->all(), [
            'assignment_submission_id' => 'required|numeric',
            'status' => 'required|numeric|in:1,2',
            'points' => 'nullable|numeric',
            'feedback' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $assignment_submission = AssignmentSubmission::findOrFail($request->assignment_submission_id);
            $assignment_submission->feedback = $request->feedback;
            if ($request->status == 1) {
                $assignment_submission->points = $request->points;
            } else {
                $assignment_submission->points = null;
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $assignment_submission->status = $request->status;
            $assignment_submission->save();

            $assignment_data = Assignment::where('id', $assignment_submission->assignment_id)->with('subject')->first();
            $user = Students::select('user_id')->where('id', $assignment_submission->student_id)->get()->pluck('user_id');
            $title = '';
            $body = '';
            if ($request->status == 2) {
                $title = 'Assignment rejected';
                $body = $assignment_data->name.' rejected in '.$assignment_data->subject->name.' subject';
            }
            if ($request->status == 1) {
                $title = 'Assignment accepted';
                $body = $assignment_data->name.' accepted in '.$assignment_data->subject->name.' subject';
            }
            $type = 'assignment';
            $image = null;
            $userinfo = null;

            $notification = new Notification;
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 3;
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
            ResponseService::successResponse('data_update_successfully');
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getLesson(Request $request): void
    {
        ResponseService::noPermissionThenSendJson('lesson-list');

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'nullable|numeric',
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'semester_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];

            $query = Lesson::lessonteachers()
                ->with('file')
                ->withCount('topic');

            if ($request->filled('lesson_id')) {
                $query->where('id', $request->lesson_id);
            }

            if ($request->filled('class_section_id')) {
                $query->where('class_section_id', $request->class_section_id);
                $this->applySemesterFilter($query, $request->class_section_id, $sessionYearId, $request->semester_id);
            }

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $data = $query->orderByDesc('id')->get();

            ResponseService::successResponse('Lesson Fetched Successfully.', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function createLesson(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-create');

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'description' => 'required',
                'class_section_id' => 'required|numeric|exists:class_sections,id',
                'subject_id' => 'required|numeric|exists:subjects,id',
                'semester_id' => 'nullable|numeric|exists:semesters,id',

                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',
            ]
        );

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueLessonInClass($request->class_section_id, $request->subject_id)],
            ]
        );

        if ($validator2->fails()) {
            ResponseService::validationError($validator2->errors()->first());
        }

        try {
            $session_year_id = getSettings('session_year')['session_year'];

            // Semester (conditional)
            $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($classId, $session_year_id);
            $semesterId = $isClassHaveSemester ? $request->semester_id : null;

            $lesson = new Lesson;
            $lesson->name = $request->name;
            $lesson->description = $request->description;
            $lesson->class_section_id = $request->class_section_id;
            $lesson->subject_id = $request->subject_id;
            $lesson->semester_id = $semesterId;
            $lesson->save();

            if ($request->file) {
                foreach ($request->file as $file) {
                    if ($file['type']) {
                        $lesson_file = new File;
                        $lesson_file->file_name = $file['name'];
                        $lesson_file->modal()->associate($lesson);

                        if ($file['type'] == '1') {
                            $lesson_file->type = 1;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == '2') {
                            $lesson_file->type = 2;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        } elseif ($file['type'] == '3') {
                            $lesson_file->type = 3;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == '4') {
                            $lesson_file->type = 4;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        }
                        $lesson_file->save();
                    }
                }
            }

            ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateLesson(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-edit');

        $validator = Validator::make(
            $request->all(),
            [
                'lesson_id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'class_section_id' => 'required|numeric',
                'subject_id' => 'required|numeric',

                'edit_file' => 'nullable|array',
                'edit_file.*.id' => 'required|numeric',
                'edit_file.*.type' => 'nullable|in:1,2,3,4',
                'edit_file.*.name' => 'required_with:edit_file.*.type',
                'edit_file.*.link' => 'required_if:edit_file.*.type,2,4',

                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',

                //            'edit_file' => 'nullable|array',
                //            'edit_file.*.id' => 'required|numeric',
                //            'edit_file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
                //            'edit_file.*.name' => 'required_with:edit_file.*.type',
                //            'edit_file.*.link' => 'required_if:edit_file.*.type,youtube_link,other_link',
                //
                //            'file' => 'nullable|array',
                //            'file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
                //            'file.*.name' => 'required_with:file.*.type',
                //            'file.*.thumbnail' => 'required_if:file.*.type,youtube_link,video_upload,other_link',
                //            'file.*.file' => 'required_if:file.*.type,file_upload,video_upload',
                //            'file.*.link' => 'required_if:file.*.type,youtube_link,other_link',

                // Regex for Youtube Link
                // 'file.*.link'=>['required_if:file.*.type,youtube_link','regex:/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((?:\w|-){11})(?:&list=(\S+))?$/'],
                // Regex for Other Link
                // 'file.*.link'=>'required_if:file.*.type,other_link|url'
            ]
        );
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueLessonInClass($request->class_section_id, $request->lesson_id)],
            ]
        );
        if ($validator2->fails()) {
            ResponseService::validationError($validator2->errors()->first());
        }
        try {
            $lesson = Lesson::find($request->lesson_id);
            $lesson->name = $request->name;
            $lesson->description = $request->description;
            $lesson->class_section_id = $request->class_section_id;
            $lesson->subject_id = $request->subject_id;
            $lesson->save();

            // Update the Old Files
            if ($request->edit_file) {
                foreach ($request->edit_file as $file) {
                    if ($file['type']) {
                        $lesson_file = File::find($file['id']);
                        if ($lesson_file) {
                            $lesson_file->file_name = $file['name'];

                            if ($file['type'] == '1') {
                                $lesson_file->type = 1;
                                if (! empty($file['file'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                                    }
                                    $lesson_file->file_url = $file['file']->store('lessons', 'public');
                                }
                            } elseif ($file['type'] == '2') {
                                $lesson_file->type = 2;
                                if (! empty($file['thumbnail'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                                    }
                                    $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                                }

                                $lesson_file->file_url = $file['link'];
                            } elseif ($file['type'] == '3') {
                                $lesson_file->type = 3;
                                if (! empty($file['file'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                                    }
                                    $lesson_file->file_url = $file['file']->store('lessons', 'public');
                                }

                                if (! empty($file['thumbnail'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                                    }
                                    $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                                }
                            } elseif ($file['type'] == '4') {
                                $lesson_file->type = 4;
                                if (! empty($file['thumbnail'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                                    }
                                    $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                                }
                                $lesson_file->file_url = $file['link'];
                            }

                            $lesson_file->save();
                        }
                    }
                }
            }

            // Add the new Files
            if ($request->file) {
                foreach ($request->file as $file) {
                    if ($file['type']) {
                        $lesson_file = new File;
                        $lesson_file->file_name = $file['name'];
                        $lesson_file->modal()->associate($lesson);

                        if ($file['type'] == '1') {
                            $lesson_file->type = 1;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == '2') {
                            $lesson_file->type = 2;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        } elseif ($file['type'] == '3') {
                            $lesson_file->type = 3;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                        } elseif ($file['type'] == '4') {
                            $lesson_file->type = 4;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        }
                        $lesson_file->save();
                    }
                }
            }
            ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteLesson(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-delete');

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $lesson = Lesson::lessonteachers()->where('id', $request->lesson_id)->firstOrFail();
            $lesson->delete();
            ResponseService::successResponse('data_delete_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getTopic(Request $request): void
    {
        ResponseService::noPermissionThenSendJson('topic-list');

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $data = LessonTopic::lessontopicteachers()
                ->where('lesson_id', $request->lesson_id)
                ->orderByDesc('id')
                ->get();

            ResponseService::successResponse('Topic Fetched Successfully.', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function createTopic(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-create');

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'description' => 'required',
                'lesson_id' => 'required|numeric',
                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',
            ]
        );

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueTopicInLesson($request->lesson_id)],
            ]
        );
        if ($validator2->fails()) {
            ResponseService::validationError($validator2->errors()->first());
        }

        try {
            $topic = new LessonTopic;
            $topic->name = $request->name;
            $topic->description = $request->description;
            $topic->lesson_id = $request->lesson_id;
            $topic->save();

            if ($request->file) {
                foreach ($request->file as $data) {
                    if ($data['type']) {
                        $file = new File;
                        $file->file_name = $data['name'];
                        $file->modal()->associate($topic);

                        if ($data['type'] == '1') {
                            $file->type = 1;
                            $file->file_url = $data['file']->store('lessons', 'public');
                        } elseif ($data['type'] == '2') {
                            $file->type = 2;
                            $file->file_thumbnail = $data['thumbnail']->store('lessons', 'public');
                            $file->file_url = $data['link'];
                        } elseif ($data['type'] == '3') {
                            $file->type = 3;
                            $file->file_thumbnail = $data['thumbnail']->store('lessons', 'public');
                            $file->file_url = $data['file']->store('lessons', 'public');
                        } elseif ($data['type'] == 'other_link') {
                            $file->type = 4;
                            $file->file_thumbnail = $data['thumbnail']->store('lessons', 'public');
                            $file->file_url = $data['link'];
                        }

                        $file->save();
                    }
                }
            }
            ResponseService::successResponse('data_store_successfully');
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateTopic(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-edit');
        $validator = Validator::make(
            $request->all(),
            [
                'topic_id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'edit_file' => 'nullable|array',
                'edit_file.*.type' => 'nullable|in:1,2,3,4',
                'edit_file.*.name' => 'required_with:edit_file.*.type',
                'edit_file.*.link' => 'required_if:edit_file.*.type,2,',

                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',
            ]
        );
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueTopicInLesson($request->lesson_id, $request->topic_id)],
            ]
        );
        if ($validator2->fails()) {
            ResponseService::validationError($validator2->errors()->first());
        }
        try {
            $topic = LessonTopic::find($request->topic_id);

            $topic->name = $request->name;
            $topic->description = $request->description;
            $topic->save();

            // Update the Old Files
            if ($request->edit_file) {
                foreach ($request->edit_file as $key => $file) {
                    if ($file['type']) {
                        $topic_file = File::find($file['id']);
                        $topic_file->file_name = $file['name'];

                        if ($file['type'] == '1') {
                            // Type File :- File Upload
                            $topic_file->type = 1;
                            if (! empty($file['file'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                                }
                                $topic_file->file_url = $file['file']->store('lessons', 'public');
                            }
                        } elseif ($file['type'] == '2') {
                            // Type File :- Youtube Link Upload
                            $topic_file->type = 2;
                            if (! empty($file['thumbnail'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                                }
                                $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            }

                            $topic_file->file_url = $file['link'];
                        } elseif ($file['type'] == '3') {
                            // Type File :- Vedio Upload
                            $topic_file->type = 3;
                            if (! empty($file['file'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                                }
                                $topic_file->file_url = $file['file']->store('lessons', 'public');
                            }

                            if (! empty($file['thumbnail'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                                }
                                $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            }
                        } elseif ($file['type'] == '4') {
                            $topic_file->type = 4;
                            if (! empty($file['thumbnail'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                                }
                                $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            }
                            $topic_file->file_url = $file['link'];
                        }

                        $topic_file->save();
                    }
                }
            }

            // Add the new Files
            if ($request->file) {
                foreach ($request->file as $file) {
                    $topic_file = new File;
                    $topic_file->file_name = $file['name'];
                    $topic_file->modal()->associate($topic);

                    if ($file['type'] == '1') {
                        $topic_file->type = 1;
                        $topic_file->file_url = $file['file']->store('lessons', 'public');
                    } elseif ($file['type'] == '2') {
                        $topic_file->type = 2;
                        $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                        $topic_file->file_url = $file['link'];
                    } elseif ($file['type'] == '3') {
                        $topic_file->type = 3;
                        $topic_file->file_url = $file['file']->store('lessons', 'public');
                        $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                    } elseif ($file['type'] == '4') {
                        $topic_file->type = 4;
                        $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                        $topic_file->file_url = $file['link'];
                    }
                    $topic_file->save();
                }
            }
            ResponseService::successResponse('data_store_successfully');
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteTopic(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-delete');

        try {
            $topic = LessonTopic::LessonTopicTeachers()->findOrFail($request->topic_id);
            $topic->delete();
            ResponseService::successResponse('data_delete_successfully');
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $file = File::find($request->file_id);
            $file->file_name = $request->name;

            if ($file->type == '1') {
                // Type File :- File Upload

                if (! empty($request->file)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }

                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_url = $request->file->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_url = $request->file->store('topics', 'public');
                    } else {

                        $file->file_url = $request->file->store('other', 'public');
                    }
                }
            } elseif ($file->type == '2') {
                // Type File :- Youtube Link Upload

                if (! empty($request->thumbnail)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }

                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_thumbnail = $request->thumbnail->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_thumbnail = $request->thumbnail->store('topics', 'public');
                    } else {

                        $file->file_thumbnail = $request->thumbnail->store('other', 'public');
                    }
                }
                $file->file_url = $request->link;
            } elseif ($file->type == '3') {
                // Type File :- Vedio Upload

                if (! empty($request->file)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }

                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_url = $request->file->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_url = $request->file->store('topics', 'public');
                    } else {

                        $file->file_url = $request->file->store('other', 'public');
                    }
                }

                if (! empty($request->thumbnail)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_thumbnail = $request->thumbnail->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_thumbnail = $request->thumbnail->store('topics', 'public');
                    } else {

                        $file->file_thumbnail = $request->thumbnail->store('other', 'public');
                    }
                }
            }
            $file->save();
            ResponseService::successResponse('data_store_successfully', $file);
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $file = File::findOrFail($request->file_id);
            $file->delete();
            ResponseService::successResponse('data_delete_successfully');
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAnnouncement(Request $request): void
    {
        ResponseService::noPermissionThenSendJson('announcement-list');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'semester_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];
            $teacher = Auth::user()->teacher;

            $subjectTeacherQuery = SubjectTeacher::where('teacher_id', $teacher->id);

            if ($request->filled('class_section_id')) {
                $subjectTeacherQuery->where('class_section_id', $request->class_section_id);
                $this->applySemesterFilter($subjectTeacherQuery, $request->class_section_id, $sessionYearId, $request->semester_id);
            }

            if ($request->filled('subject_id')) {
                $subjectTeacherQuery->where('subject_id', $request->subject_id);
            }

            $subjectTeacherIds = $subjectTeacherQuery->pluck('id');

            $data = Announcement::with('table.subject', 'file')
                ->where('table_type', 'App\Models\SubjectTeacher')
                ->whereIn('table_id', $subjectTeacherIds)
                ->orderByDesc('id')
                ->paginate();

            ResponseService::successResponse('Announcement Fetched Successfully.', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function sendAnnouncement(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-create');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric|exists:class_sections,id',
            'subject_id' => 'required|numeric|exists:subjects,id',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ], [
            'class_section_id.exists' => 'Selected class section does not exist.',
            'subject_id.exists' => 'Selected subject does not exist.',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $session_year_id = getSettings('session_year')['session_year'];
            $teacher_id = Auth::user()->teacher->id;

            // Semester (conditional)
            $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($classId, $session_year_id);
            $semesterId = $isClassHaveSemester ? $request->semester_id : null;

            // Verify teacher is assigned (semester-aware)
            $subject_teacher = SubjectTeacher::where('teacher_id', $teacher_id)
                ->where('class_section_id', $request->class_section_id)
                ->where('subject_id', $request->subject_id)
                ->when($isClassHaveSemester && $semesterId, fn ($q) => $q->where(
                    fn ($q) => $q->where('semester_id', $semesterId)->orWhereNull('semester_id')
                ))
                ->with('subject')
                ->first();

            if (! $subject_teacher) {
                ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }

            $announcement = new Announcement;
            $announcement->title = trim($request->title);
            $announcement->description = trim($request->description);
            $announcement->session_year_id = $session_year_id;
            $announcement->semester_id = $semesterId;
            $announcement->table()->associate($subject_teacher);

            // Get students in the class section
            $user = Students::select('user_id')
                ->where('class_section_id', $request->class_section_id)
                ->pluck('user_id')
                ->filter()
                ->toArray();

            $announcement->save();

            if (! empty($user)) {
                $title = 'New announcement in '.$subject_teacher->subject->name;
                $body = $request->title;
                sendSimpleNotification($user, $title, $body, 'class_section', null, null);
            }

            // File uploads
            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = $file_upload->getClientOriginalName();
                    $file->type = 1;
                    $file->file_url = $file_upload->store('announcement', 'public');
                    $file->modal()->associate($announcement);
                    $file->save();
                }
            }

            ResponseService::successResponse('data_store_successfully');
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateAnnouncement(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-edit');

        $validator = Validator::make($request->all(), [
            'announcement_id' => 'required|numeric',
            'class_section_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'title' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $teacher_id = Auth::user()->teacher->id;
            $announcement = Announcement::findOrFail($request->announcement_id);
            $announcement->title = $request->title;
            $announcement->description = $request->description;

            $subject_teacher = SubjectTeacher::where(['teacher_id' => $teacher_id, 'class_section_id' => $request->class_section_id, 'subject_id' => $request->subject_id])->with('subject')->firstOrFail();
            $announcement->table()->associate($subject_teacher);
            $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');

            $title = 'Update announcement in '.$subject_teacher->subject->name;
            $body = $request->title;
            $image = null;
            $userinfo = null;

            $announcement->save();
            sendSimpleNotification($user, $title, $body, 'class_section', $image, $userinfo);
            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = $file_upload->getClientOriginalName();
                    $file->type = 1;
                    $file->file_url = $file_upload->store('announcement', 'public');
                    $file->modal()->associate($announcement);
                    $file->save();
                }
            }
            ResponseService::successResponse('data_update_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteAnnouncement(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-delete');

        $validator = Validator::make($request->all(), [
            'announcement_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $announcement = Announcement::findorFail($request->announcement_id);
            $announcement->delete();
            ResponseService::successResponse('data_delete_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAttendance(Request $request)
    {
        ResponseService::noPermissionThenSendJson('attendance-list');

        $class_section_id = $request->class_section_id;
        $attendance_type = $request->type;
        $date = date('Y-m-d', strtotime($request->date));

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'date' => 'required|date',
            'type' => 'in:0,1',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $on_leave_student_ids = [];

            // $current_date = Carbon::now()->toDateString();

            $students = Students::where('class_section_id', $class_section_id)->pluck('user_id');

            $on_leave_student_ids = Leave::with('leave_detail')->where('status', 1)
                ->whereIn('user_id', $students)
                ->whereHas('leave_detail', function ($query) use ($date) {
                    $query->whereDate('date', $date);
                })
                ->pluck('user_id')
                ->map(function ($user_id) {
                    return Students::where('user_id', $user_id)->pluck('id')->first();
                })
                ->filter()
                ->toArray();

            $sql = Attendance::where('class_section_id', $class_section_id)->where('date', $date);
            if (isset($attendance_type) && $attendance_type != '') {
                $sql->where('type', $attendance_type);
            }
            $data = $sql->get();
            $holiday = Holiday::where('date', $date)->get();
            if ($holiday->count()) {
                ResponseService::successResponse('data_update_successfully', $data, ['is_holiday' => true, 'holiday' => $holiday]);
            } else {
                if ($data->count()) {
                    ResponseService::successResponse('Data Fetched Successfully', $data, ['is_holiday' => false, 'on_leave_student_ids' => $on_leave_student_ids]);
                } else {
                    ResponseService::successResponse('Attendance not recorded', $data, ['is_holiday' => false, 'holiday' => ($holiday->count() == 0) ? null : $holiday, 'on_leave_student_ids' => $on_leave_student_ids]);
                }
            }
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function submitAttendance(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['attendance-create', 'attendance-edit']);

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            // 'student_id' => 'required',
            'attendance.*.student_id' => 'required',
            'attendance.*.type' => 'required|in:0,1',
            'date' => 'required|date',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = $request->class_section_id;
            $date = date('Y-m-d', strtotime($request->date));
            $getid = Attendance::where(['date' => $date, 'class_section_id' => $class_section_id])->get();
            foreach ($request->attendance as $attendance) {
                $studentAttendance = $getid->first(function ($query) use ($attendance) {
                    return $query->student_id == $attendance['student_id'];
                });

                if ($request->holiday != '' && $request->holiday == 3) {
                    $type = $request->holiday;
                } else {
                    $type = $attendance['type'];
                    if ($type == 0) {
                        $absent_student_ids[] = $attendance['student_id'];
                    }
                }
                $attendanceData[] = [
                    'id' => $studentAttendance->id ?? null,
                    'class_section_id' => $class_section_id,
                    'student_id' => $attendance['student_id'],
                    'session_year_id' => $session_year_id,
                    'date' => $date,
                    'type' => $type,
                    'status' => 1,
                ];
            }
            Attendance::upsert($attendanceData, ['id'], ['class_section_id', 'student_id', 'session_year_id', 'date', 'type', 'status']);
            // Send Notification to parents
            if (! empty($absent_student_ids)) {
                $student = Students::with('user')->whereIn('id', $absent_student_ids)->get();
                foreach ($student as $student) {
                    $user = Parents::where('id', $student->father_id)->orwhere('id', $student->mother_id)->pluck('user_id');
                    $title = 'Attendance Alert';
                    $body = $student->user->first_name.' '.$student->user->last_name.' '.'is Absent on'.' '.date('d-m-Y', strtotime($date));
                    $type = 'attendance';
                    $image = null;
                    $userinfo = null;

                    $notification = new Notification;
                    $notification->send_to = 3;
                    $notification->session_year_id = $session_year_id;
                    $notification->title = $title;
                    $notification->message = $body;
                    $notification->type = $type;
                    $notification->date = Carbon::now();
                    $notification->is_custom = 0;
                    $notification->save();

                    // Prepare data for batch insert
                    $userNotificationData = [];
                    foreach ($user as $data) {
                        $userNotificationData[] = [
                            'notification_id' => $notification->id,
                            'user_id' => $data,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
                    }
                }
                // Batch insert all user notifications
                if (! empty($userNotificationData)) {
                    UserNotification::insert($userNotificationData);
                }
            }
            ResponseService::successResponse('data_store_successfully');
        } catch (Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'subject_id' => 'nullable',
            'semester_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $user = Auth::user()->teacher;
            $class_section_id = $request->class_section_id;
            $semester_id = $request->semester_id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_id = ClassSection::where('id', $class_section_id)->value('class_id');

            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            // Verify that the teacher is assigned to this specific class section
            $isTeacherAssigned = ClassTeacher::where('class_section_id', $class_section_id)
                ->where('class_teacher_id', $user->id)
                ->where('session_year_id', $session_year_id)
                // ->when($isClassHaveSemester && $semester_id, fn ($q) => $q->where('semester_id', $semester_id)->orWhereNull('semester_id'))
                ->exists();

            if (! $isTeacherAssigned) {
                ResponseService::errorResponse('You are not assigned to this class section', null, 403);
            }

            // Get student IDs for this class section in the current session year
            $studentIds = StudentSessions::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->pluck('student_id');

            $sql = Students::with('user:id,first_name,last_name,image,gender,dob,current_address,permanent_address', 'class_section')
                ->whereIn('id', $studentIds);

            $data = $sql->orderBy('roll_number')->get();

            if (isset($request->subject_id)) {
                $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');
                $class_subject = ClassSubject::where('subject_id', $request->subject_id)
                    ->where('semester_id', $semester_id)
                    ->where('session_year_id', $session_year_id)
                    ->where('class_id', $class_id)->first();

                if ($class_subject->type == 'Elective') {
                    foreach ($data as $student) {
                        $student_id[] = $student->id;
                    }
                    $student_subject = StudentSubject::whereIn('student_id', $student_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('semester_id', $semester_id)
                        ->where('session_year_id', $session_year_id)
                        ->where('class_section_id', $class_section_id)->pluck('student_id');

                    if ($student_subject) {
                        $sql = Students::with('user:id,first_name,last_name,image,gender,dob,current_address,permanent_address', 'class_section')
                            ->whereIn('id', $student_subject);
                        $data = $sql->orderBy('id')->get();
                    }
                }
            }
            ResponseService::successResponse('Student Details Fetched Successfully', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $student_data_ids = Students::select('user_id', 'class_section_id', 'father_id', 'mother_id', 'guardian_id')->where('id', $request->student_id)->get();
            $student_total_present = Attendance::where('student_id', $request->student_id)->where('session_year_id', $session_year_id)->where('type', 1)->count();
            $student_total_absent = Attendance::where('student_id', $request->student_id)->where('session_year_id', $session_year_id)->where('type', 0)->count();

            $today_date_string = Carbon::now();
            $today_date_string->toDateTimeString();
            $today_date = date('Y-m-d', strtotime($today_date_string->toDateString()));

            $student_today_attendance = Attendance::where('student_id', $request->student_id)->where('date', $today_date)->get();
            if ($student_today_attendance->count()) {
                foreach ($student_today_attendance as $student_attendance) {
                    if ($student_attendance['type'] == 1) {
                        $today_attendance = 'Present';
                    } else {
                        $today_attendance = 'Absent';
                    }
                }
            } else {
                $today_attendance = 'Not Taken';
            }
            foreach ($student_data_ids as $student_data_ids) {
                $father_data = Parents::where('id', $student_data_ids['father_id'])->get();
                $mother_data = Parents::where('id', $student_data_ids['mother_id'])->get();
                if ($student_data_ids['guardian_id'] != 0) {
                    $guardian_data = Parents::where('id', $student_data_ids['guardian_id'])->get();

                    ResponseService::successResponse('Student Details Fetched Successfully', null, [
                        'gurdian_data' => $guardian_data,
                        'father_data' => $father_data,
                        'mother_data' => $mother_data,
                        'total_present' => $student_total_present,
                        'total_absent' => $student_total_absent,
                        'today_attendance' => $today_attendance,
                    ]);
                } else {
                    ResponseService::successResponse('Student Details Fetched Successfully', null, [
                        'father_data' => $father_data,
                        'mother_data' => $mother_data,
                        'total_present' => $student_total_present,
                        'total_absent' => $student_total_absent,
                        'today_attendance' => $today_attendance,
                    ]);
                }
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getTeacherTimetable(Request $request)
    {
        try {
            // Get authenticated teacher
            $teacher = $request->user()->teacher;

            // Fetch current session year ID once
            $sessionYearId = getSettings('session_year')['session_year'];

            // Get current semester for this session
            $currentSemester = Semester::where('session_year_id', $sessionYearId)
                ->get()
                ->first(fn ($s) => $s->current);

            // Get subject-teacher IDs for:
            // - current semester OR semester is null
            $subjectTeacherIds = SubjectTeacher::where('teacher_id', $teacher->id)
                ->where(function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester?->id)
                        ->orWhereNull('semester_id');
                })
                ->pluck('id');

            // Fetch timetable with required relations
            $timetable = Timetable::whereIn('subject_teacher_id', $subjectTeacherIds)
                ->where('session_year_id', $sessionYearId)
                ->where(function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester?->id)
                        ->orWhereNull('semester_id');
                })
                ->with(['class_section', 'subject'])
                ->get();

            // Standard success response
            return ResponseService::successResponse(
                'Timetable Fetched Successfully',
                $timetable
            );
        } catch (\Exception $e) {
            // Centralized error handling
            return ResponseService::errorResponse(
                'error_occurred',
                null,
                103,
                $e
            );
        }
    }

    public function submitExamMarksBySubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'exam_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $exam_published = Exam::where(['id' => $request->exam_id, 'publish' => 1])->first();
            if (isset($exam_published)) {
                ResponseService::errorResponse('exam_published', null, 400);
            }

            $teacher_id = Auth::user()->teacher->id;
            $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id');

            // check exam status
            $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
            $starting_date = $starting_date_db['min(date)'];
            $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
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
                ResponseService::errorResponse('exam_not_completed_yet', null, 400);
            } else {
                $grades = Grade::orderBy('ending_range', 'desc')->get();
                $exam_timetable = ExamTimetable::where('exam_id', $request->exam_id)->where('subject_id', $request->subject_id)->firstOrFail();
                foreach ($request->marks_data as $marks) {
                    $passing_marks = $exam_timetable->passing_marks;
                    if ($marks['obtained_marks'] >= $passing_marks) {
                        $status = 1;
                    } else {
                        $status = 0;
                    }
                    $marks_percentage = ($marks['obtained_marks'] / $exam_timetable['total_marks']) * 100;

                    $exam_grade = findExamGrade($exam_timetable->session_year_id, $marks_percentage);
                    if ($exam_grade == null) {
                        ResponseService::errorResponse('grades_data_does_not_exists', null, 400);
                    }

                    $exam_marks = ExamMarks::where(['exam_timetable_id' => $exam_timetable->id, 'subject_id' => $request->subject_id, 'student_id' => $marks['student_id']])->first();
                    if ($exam_marks) {
                        $exam_marks_db = ExamMarks::find($exam_marks->id);
                        $exam_marks_db->obtained_marks = $marks['obtained_marks'];
                        $exam_marks_db->passing_status = $status;
                        $exam_marks_db->grade = $exam_grade;
                        $exam_marks_db->save();
                        ResponseService::successResponse('data_update_successfully');
                    } else {
                        $exam_result_marks[] = [
                            'exam_timetable_id' => $exam_timetable->id,
                            'student_id' => $marks['student_id'],
                            'subject_id' => $request->subject_id,
                            'obtained_marks' => $marks['obtained_marks'],
                            'passing_status' => $status,
                            'session_year_id' => $exam_timetable->session_year_id,
                            'grade' => $exam_grade,
                        ];
                    }
                }
                if (isset($exam_result_marks)) {
                    ExamMarks::insert($exam_result_marks);
                    ResponseService::successResponse('data_store_successfully');
                }
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function submitExamMarksByStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'exam_id' => 'required|numeric',
            'student_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $exam_published = Exam::where(['id' => $request->exam_id, 'publish' => 1])->first();
            if (isset($exam_published)) {
                ResponseService::errorResponse('exam_published', null, 400);
            }

            $teacher_id = Auth::user()->teacher->id;
            // $class_section_id = Students::where('id',$request->student_id)->pluck('class_section_id');
            $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id');

            // exam status
            $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
            $starting_date = $starting_date_db['min(date)'];
            $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
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
                ResponseService::errorResponse('exam_not_completed_yet', null, 400);
            } else {
                $grades = Grade::orderBy('ending_range', 'desc')->get();

                foreach ($request->marks_data as $marks) {
                    $exam_timetable = ExamTimetable::where(['exam_id' => $request->exam_id, 'subject_id' => $marks['subject_id']])->firstOrFail();
                    $passing_marks = $exam_timetable->passing_marks;
                    if ($marks['obtained_marks'] >= $passing_marks) {
                        $status = 1;
                    } else {
                        $status = 0;
                    }
                    $marks_percentage = ($marks['obtained_marks'] / $exam_timetable->total_marks) * 100;

                    $exam_grade = findExamGrade($exam_timetable->session_year_id, $marks_percentage);
                    if ($exam_grade == null) {
                        ResponseService::errorResponse('grades_data_does_not_exists', null, 400);
                    }

                    $exam_marks = ExamMarks::where(['exam_timetable_id' => $exam_timetable->id, 'student_id' => $request->student_id, 'subject_id' => $marks['subject_id']])->first();
                    if ($exam_marks) {
                        $exam_marks_db = ExamMarks::find($exam_marks->id);
                        $exam_marks_db->obtained_marks = $marks['obtained_marks'];
                        $exam_marks_db->passing_status = $status;
                        $exam_marks_db->grade = $exam_grade;
                        $exam_marks_db->save();
                        ResponseService::successResponse('data_update_successfully');
                    } else {
                        $exam_result_marks[] = [
                            'exam_timetable_id' => $exam_timetable->id,
                            'student_id' => $request->student_id,
                            'subject_id' => $marks['subject_id'],
                            'obtained_marks' => $marks['obtained_marks'],
                            'passing_status' => $status,
                            'session_year_id' => $exam_timetable->session_year_id,
                            'grade' => $exam_grade,
                        ];
                    }
                }
                if (isset($exam_result_marks)) {
                    ExamMarks::insert($exam_result_marks);
                    ResponseService::successResponse('data_store_successfully');
                }
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function GetStudentExamResult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            // $teacher_id = Auth::user()->teacher->id;
            $class_section_id = Students::where('id', $request->student_id)->pluck('class_section_id');

            $class_data = ClassSection::where('id', $class_section_id)->with('class.medium', 'section')->get()->first();

            $exam_marks_db = ExamClass::with(['exam.timetable' => function ($q) use ($request, $class_data) {
                $q->where('class_id', $class_data->class_id)->with(['exam_marks' => function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                }])->with('subject:id,name,type,image,code');
            }])->with(['exam.results' => function ($q) use ($request) {
                $q->where('student_id', $request->student_id)->with(['student' => function ($q) {
                    $q->select('id', 'user_id', 'roll_number')->with('user:id,first_name,last_name');
                }])->with('session_year:id,name');
            }])->where('class_id', $class_data->class_id)->get();

            if (count($exam_marks_db)) {
                foreach ($exam_marks_db as $data_db) {
                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $data_db->exam_id, 'class_id' => $class_data->class_id])->first();
                    $starting_date = $starting_date_db['min(date)'];
                    $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $data_db->exam_id, 'class_id' => $class_data->class_id])->first();
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

                    // check wheather exam is completed or not
                    if ($exam_status == 2) {
                        $marks_array = [];

                        // check wheather timetable exists or not
                        if (count($data_db->exam->timetable)) {
                            foreach ($data_db->exam->timetable as $timetable_db) {
                                $total_marks = $timetable_db->total_marks;
                                $exam_marks = [];
                                if (count($timetable_db->exam_marks)) {
                                    foreach ($timetable_db->exam_marks as $marks_data) {
                                        $exam_marks = [
                                            'marks_id' => $marks_data->id,
                                            'subject_name' => $marks_data->subject->name,
                                            'subject_type' => $marks_data->subject->type,
                                            'total_marks' => $total_marks,
                                            'obtained_marks' => $marks_data->obtained_marks,
                                            'grade' => $marks_data->grade,
                                        ];
                                    }
                                } else {
                                    $exam_marks = (object) [];
                                }
                                if ($exam_marks != (object) []) {
                                    $marks_array[] = [
                                        'subject_id' => $timetable_db->subject->id,
                                        'subject_name' => $timetable_db->subject->name,
                                        'subject_type' => $timetable_db->subject->type,
                                        'total_marks' => $total_marks,
                                        'subject_code' => $timetable_db->subject->code,
                                        'marks' => $exam_marks,
                                    ];
                                }
                            }

                            $exam_result = [];
                            if (count($data_db->exam->results)) {
                                foreach ($data_db->exam->results as $result_data) {
                                    $exam_result = [
                                        'result_id' => $result_data->id,
                                        'exam_id' => $result_data->exam_id,
                                        'exam_name' => $data_db->exam->name,
                                        'class_name' => $class_data->class->name.'-'.$class_data->section->name.' '.$class_data->class->medium->name,
                                        'student_name' => $result_data->student->user->first_name.' '.$result_data->student->user->last_name,
                                        'exam_date' => $starting_date,
                                        'total_marks' => $result_data->total_marks,
                                        'obtained_marks' => $result_data->obtained_marks,
                                        'percentage' => $result_data->percentage,
                                        'grade' => $result_data->grade,
                                        'session_year' => $result_data->session_year->name,
                                    ];
                                }
                            } else {
                                $exam_result = (object) [];
                            }
                            if ($marks_array != null && $exam_result != null) {
                                $data[] = [
                                    'exam_id' => $data_db->exam_id,
                                    'exam_name' => $data_db->exam->name,
                                    'exam_date' => $starting_date,
                                    'marks_data' => $marks_array,
                                    'result' => $exam_result,
                                ];
                            }
                        }
                    }
                }
                ResponseService::successResponse('Exam Marks Fetched Successfully', $data ?? []);
            } else {
                ResponseService::successResponse('Exam Marks Fetched Successfully', []);
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function GetStudentExamMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            // $teacher_id = Auth::user()->teacher->id;
            $class_section_id = Students::where('id', $request->student_id)->pluck('class_section_id');

            $class_data = ClassSection::where('id', $class_section_id)->with('class.medium', 'section')->get()->first();

            $exam_marks_db = ExamClass::with(['exam.timetable' => function ($q) use ($request, $class_data) {
                $q->where('class_id', $class_data->class_id)->with(['exam_marks' => function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                }])->with('subject:id,name,type,image');
            }])->where('class_id', $class_data->class_id)->get();

            if (count($exam_marks_db)) {
                foreach ($exam_marks_db as $data_db) {
                    $marks_array = [];
                    foreach ($data_db->exam->timetable as $marks_db) {
                        $exam_marks = [];
                        if (count($marks_db->exam_marks)) {
                            foreach ($marks_db->exam_marks as $marks_data) {
                                $exam_marks = [
                                    'marks_id' => $marks_data->id,
                                    'subject_name' => $marks_data->subject->name,
                                    'subject_type' => $marks_data->subject->type,
                                    'total_marks' => $marks_data->timetable->total_marks,
                                    'obtained_marks' => $marks_data->obtained_marks,
                                    'grade' => $marks_data->grade,
                                ];
                            }
                        } else {
                            $exam_marks = [];
                        }
                        if ($exam_marks != []) {
                            $marks_array[] = [
                                'subject_id' => $marks_db->subject->id,
                                'subject_name' => $marks_db->subject->name,
                                'marks' => $exam_marks,
                            ];
                        }
                    }
                    $data[] = [
                        'exam_id' => $data_db->exam_id,
                        'exam_name' => $marks_db->exam->name,
                        'marks_data' => $marks_array,
                    ];
                }
                ResponseService::successResponse('Exam Marks Fetched Successfully', $data);
            } else {
                ResponseService::successResponse('Exam Marks Fetched Successfully', []);
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'in:0,1,2,3',
            'publish' => 'in:0,1',
            'class_section_id' => 'nullable',
            'get_timetable' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {

            $teacher = Auth::user()->teacher;

            $session_year_id = getSettings('session_year')['session_year'];

            $teacherSemesterIds = [];

            if (isset($request->class_section_id)) {

                // Verify teacher access to requested section
                $classTeacherQuery = ClassTeacher::where('class_teacher_id', $teacher->id)
                    ->where('session_year_id', $session_year_id)
                    ->where('class_section_id', $request->class_section_id);

                if (! $classTeacherQuery->exists()) {
                    ResponseService::errorResponse('no_permission_message');
                }

                $class_section_ids = collect([$request->class_section_id]);

                $class_ids = ClassSection::where('id', $request->class_section_id)
                    ->pluck('class_id');

                // Semester access for this section
                $teacherSemesterIds = $classTeacherQuery
                    ->whereNotNull('semester_id')
                    ->pluck('semester_id')
                    ->unique()
                    ->toArray();

            } else {

                $classTeacherRecords = ClassTeacher::where('class_teacher_id', $teacher->id)
                    ->where('session_year_id', $session_year_id)
                    ->get();

                $class_section_ids = $classTeacherRecords
                    ->pluck('class_section_id')
                    ->unique();

                $class_ids = ClassSection::whereIn('id', $class_section_ids)
                    ->pluck('class_id');

                $teacherSemesterIds = $classTeacherRecords
                    ->whereNotNull('semester_id')
                    ->pluck('semester_id')
                    ->unique()
                    ->toArray();
            }

            $sql = ExamClass::with(
                'exam.session_year:id,name',
                'exam.timetable.subject',
                'class',
                'class.medium',
                'class.streams'
            )
                ->whereIn('class_id', $class_ids)

                ->whereHas('exam', function ($q) use (
                    $session_year_id,
                    $class_ids,
                    $teacherSemesterIds
                ) {

                    $q->where('session_year_id', $session_year_id);

                    foreach ($class_ids as $class_id) {

                        $hasSemester = ClassSessionConfig::classHasSemester(
                            $class_id,
                            $session_year_id
                        );

                        if ($hasSemester) {

                            $q->whereIn('semester_id', $teacherSemesterIds);
                        }
                    }
                });

            if (isset($request->publish)) {

                $publish = $request->publish;

                $sql->whereHas('exam', function ($q) use ($publish) {
                    $q->where('publish', $publish);
                });
            }

            $exam_data_db = $sql->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))
                    ->where('exam_id', $data->exam_id)
                    ->whereIn('class_id', $class_ids)
                    ->first();

                $starting_date = $starting_date_db['min(date)'];

                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))
                    ->where('exam_id', $data->exam_id)
                    ->whereIn('class_id', $class_ids)
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

                // $request->status
                // 0 = all
                // 1 = upcoming
                // 2 = ongoing
                // 3 = completed

                if (isset($request->status)) {

                    if ($request->status == 0) {

                        $allowExam = true;

                    } elseif ($request->status == 1) {

                        $allowExam = ($exam_status == 0);

                    } elseif ($request->status == 2) {

                        $allowExam = ($exam_status == 1);

                    } else {

                        $allowExam = ($exam_status == 2);
                    }

                } else {

                    $allowExam = true;
                }

                if ($allowExam) {

                    $examPayload = [
                        'id' => $data->exam->id,
                        'name' => $data->exam->name,
                        'description' => $data->exam->description,
                        'publish' => $data->exam->publish,
                        'session_year' => $data->exam->session_year->name,
                        'exam_starting_date' => $starting_date,
                        'exam_ending_date' => $ending_date,
                        'exam_status' => $exam_status,
                        'class_id' => $data->class_id,
                        'class_name' => $data->class->name.'-'.$data->class->medium->name,
                        'class_streams' => $data->class->streams->name ?? null,
                    ];

                    if ($request->get_timetable == 1) {
                        $examPayload['exam_timetable'] = $data->exam->timetable;
                    }

                    $exam_data[] = $examPayload;
                }
            }

            ResponseService::successResponse(
                'Exam Marks Fetched Successfully',
                $exam_data ?? []
            );

        } catch (\Exception $e) {

            ResponseService::errorResponse(
                'error_occurred',
                null,
                103,
                $e
            );
        }
    }

    public function getExamDetails(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|integer',
            'class_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];

            $classId = $request->class_id;

            $classSection = ClassSection::with('class', 'section', 'class.medium', 'class.streams')
                ->where('class_id', $classId)
                ->first();

            $examData = Exam::with([
                'semester',
                'timetable' => function ($query) use ($request, $classId) {
                    $query->where([
                        'exam_id' => $request->exam_id,
                        'class_id' => $classId,
                    ])->with('subject');
                },
            ])
                ->where('id', $request->exam_id)
                ->where('session_year_id', $sessionYearId)
                ->get();

            // Transform: move semester inside subject
            $examData = $examData->map(function ($exam) {

                $semester = $exam->semester;

                if ($exam->timetable) {
                    $exam->timetable->transform(function ($tt) use ($semester) {

                        if ($tt->subject) {
                            $tt->subject->semester = $semester;
                        }

                        return $tt;
                    });
                }
                unset($exam->semester);

                return $exam;
            });

            ResponseService::successResponse('Data Fetched Successfully', $examData, [
                'class_id' => $classId,
                'class_section_id' => $classSection->id,
                'class_name' => $classSection->class->name.'-'.$classSection->section->name.' '.$classSection->class->medium->name,
                'stream_name' => $classSection->class->streams->name ?? null,
                'code' => 200,
            ]);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getProfileDetails()
    {
        try {
            $user = Auth::user()->load(['teacher']);
            $dynamicFields = null;
            $dynamicField = $user->teacher->dynamic_fields;

            $user = flattenMyModel($user);

            $data = json_decode($dynamicField, true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (! empty($item)) {
                        foreach ($item as $key => $value) {
                            $dynamicFields[$key] = $value;
                        }
                    }
                }
            } else {
                $dynamicFields = $data;
            }

            $user = array_merge($user, ['dynamic_fields' => $dynamicFields ?? null]);
            ResponseService::successResponse('Data Fetched Successfully', $user);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getNotifications()
    {
        try {
            // $user = $request->user()->id;
            $user = Auth::user()->id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $notification_id = UserNotification::where('user_id', $user)->pluck('notification_id');
            // Send To All Users(1) and Teachers(5)
            $notification = Notification::where('session_year_id', $session_year_id)
                ->where(function ($query) use ($notification_id) {
                    $query->whereIn('id', $notification_id)
                        ->orWhereIn('send_to', [1, 4]);
                })
                ->latest()
                ->paginate();
            ResponseService::successResponse('Data Fetched Successfully', $notification ?? '');
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getChatUserList(Request $request)
    {
        try {

            $offset = $request->offset;
            $limit = $request->limit;
            $user_type = $request->isParent;
            $search = $request->search;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $teacher = $request->user()->teacher;

            $class_section_ids = ClassTeacher::with('class_section')
                ->where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id')->toArray();

            $subject_teachers = SubjectTeacher::with('class_section')
                ->where('teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->whereNotIn('class_section_id', $class_section_ids)
                ->groupBy('class_section_id')->get();

            $data = [];
            $parents_ids = [];

            if ($class_section_ids) {
                $students = Students::with(['user', 'class_section.class', 'student_subjects.subject'])
                    ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                        $q->where('session_year_id', $session_year_id);
                    })->whereIn('class_section_id', $class_section_ids)->get();

                foreach ($students as $student) {
                    $parents_ids[] = $student->father_id;
                    $parents_ids[] = $student->mother_id;
                    $parents_ids[] = $student->guardian_id;
                }

                $parents_ids = array_filter(array_unique($parents_ids));

                if ($user_type == 0) {
                    foreach ($students as $student) {
                        $unreadCount = 0;
                        if ($student->user_id != 0) {
                            $lastMessage = ChatMessage::with('file')->where(function ($query) use ($student, $teacher) {
                                $query->where('modal_id', $student->user_id)
                                    ->where('sender_id', $teacher->user->id);
                            })
                                ->orWhere(function ($query) use ($student, $teacher) {
                                    $query->where('modal_id', $teacher->user->id)
                                        ->where('sender_id', $student->user_id);
                                })
                                ->select('id', 'body', 'date')
                                ->latest()
                                ->first();

                            $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $student->user_id)->first();

                            if ($lastReadMessage) {

                                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                if (! empty($lastReadMessageId)) {
                                    $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                } else {
                                    $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->count();
                                }
                            }

                            $student_subject = $student->subjects();

                            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

                            $elective_subjects = $student_subject['elective_subject'] ?? [];
                            if ($elective_subjects) {
                                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                            }
                            $subject_id = array_merge($core_subjects, $elective_subjects);

                            $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                            $data[] = [
                                'id' => $student->id,
                                'user_id' => $student->user_id, // Assuming this is the correct property name
                                'first_name' => $student->user->first_name ?? '',
                                'last_name' => $student->user->last_name ?? '',
                                'image' => $student->user->image ?? '',
                                'roll_no' => $student->roll_number,
                                'admission_no' => $student->admission_no,
                                'gender' => $student->user->gender,
                                'dob' => $student->user->dob,
                                'subjects' => $subjects,
                                'address' => $student->user->current_address,
                                'last_message' => $lastMessage ?? null,
                                'class_name' => $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name,
                                'isParent' => $user_type,
                                'unread_message' => $unreadCount ?? 0,
                            ];
                        }
                    }
                }
                if ($user_type == 1) {

                    $parents = Parents::with('user')->whereIn('id', $parents_ids)->get();
                    foreach ($parents as $parent) {
                        $unreadCount = 0;
                        $childArray = [];
                        if ($parent->user_id != 0) {
                            $children = $parent->children()->with('user', 'class_section')
                                ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                                    $q->where('session_year_id', $session_year_id);
                                })->get();

                            foreach ($children as $child) {
                                $child_subject = $child->subjects();

                                $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

                                $elective_subjects = $child_subject['elective_subject'] ?? [];

                                if ($elective_subjects) {
                                    $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                                }

                                $subject_id = array_merge($core_subjects, $elective_subjects);

                                $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                                $childArray[] = [
                                    'id' => $child->id,
                                    'user_id' => $child->user_id,
                                    'child_name' => $child->user->first_name.' '.$child->user->last_name,
                                    'class_name' => $child->class_section->class->name.' '.$child->class_section->section->name.' '.$child->class_section->class->medium->name,
                                    'admission_no' => $child->admission_no,
                                    'image' => $child->user->image,
                                    'subject' => $subjects ?? [],
                                ];
                            }

                            $lastMessage = ChatMessage::with('file')->where(function ($query) use ($parent, $teacher) {
                                $query->where('modal_id', $parent->user_id)
                                    ->where('sender_id', $teacher->user->id);
                            })
                                ->orWhere(function ($query) use ($parent, $teacher) {
                                    $query->where('modal_id', $teacher->user->id)
                                        ->where('sender_id', $parent->user_id);
                                })
                                ->select('body', 'date')
                                ->latest()
                                ->first();

                            $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $parent->user_id)->first();

                            if ($lastReadMessage) {

                                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                if (! empty($lastReadMessageId)) {
                                    $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                } else {
                                    $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->count();
                                }
                            }
                            $data[] = [
                                'id' => $parent->id,
                                'user_id' => $parent->user_id, // Assuming this is the correct property name
                                'first_name' => $parent->user->first_name ?? '',
                                'last_name' => $parent->user->last_name ?? '',
                                'email' => $parent->user->email ?? '',
                                'mobile_no' => $parent->user->mobile ?? '',
                                'occupation' => $parent->occupation ?? '',
                                'image' => $parent->user->image ?? '',
                                'last_message' => $lastMessage ?? null,
                                'children' => $childArray ?? [],
                                'isParent' => $user_type,
                                'unread_message' => $unreadCount ?? 0,
                            ];
                        }
                    }
                }
            }

            if ($subject_teachers) {

                foreach ($subject_teachers as $subject_teacher) {
                    $class_subject = ClassSubject::where('subject_id', $subject_teacher->subject_id)->where('class_id', $subject_teacher->class_section->class->id)->first();
                    $students = Students::with(['user', 'class_section.class', 'student_subjects.subject'])
                        ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                            $q->where('session_year_id', $session_year_id);
                        })->where('class_section_id', $subject_teacher->class_section_id)->get();
                    $parents_id = [];

                    $parents_id = $students->groupBy(['father_id', 'mother_id', 'guardian_id'])->keys()->all();

                    $common_parents_ids = array_intersect($parents_ids, $parents_id);

                    $unique_parents_ids = array_diff($parents_id, $common_parents_ids);

                    if ($user_type == 0) {
                        foreach ($students as $student) {
                            $unreadCount = 0;
                            if ($student->user_id != 0) {
                                $lastMessage = ChatMessage::with('file')->where(function ($query) use ($student, $teacher) {
                                    $query->where('modal_id', $student->user_id)
                                        ->where('sender_id', $teacher->user->id);
                                })
                                    ->orWhere(function ($query) use ($student, $teacher) {
                                        $query->where('modal_id', $teacher->user->id)
                                            ->where('sender_id', $student->user_id);
                                    })
                                    ->select('id', 'body', 'date')
                                    ->latest()
                                    ->first();

                                $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $student->user_id)->first();

                                if ($lastReadMessage) {

                                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                    if (! empty($lastReadMessageId)) {
                                        $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                    } else {
                                        $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->count();
                                    }
                                }

                                $student_subject = $student->subjects();

                                $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

                                $elective_subjects = $student_subject['elective_subject'] ?? [];
                                if ($elective_subjects) {
                                    $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                                }
                                $subject_id = array_merge($core_subjects, $elective_subjects);

                                $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();
                                $subjectArray = [];
                                foreach ($subjects as $subject) {
                                    $subjectArray[] = [
                                        'id' => $subject->id,
                                        'name' => $subject->name,
                                    ];
                                }
                                if ($class_subject->type == 'Elective') {
                                    // dd($student_subject['elective_subject']->pluck('subject_id'));

                                    $student_subject = $student->student_subjects->where('subject_id', $class_subject->subject_id);

                                    if (! empty($student_subject->toArray())) {
                                        $data[] = [
                                            'id' => $student->id,
                                            'user_id' => $student->user_id, // Assuming this is the correct property name
                                            'first_name' => $student->user->first_name ?? '',
                                            'last_name' => $student->user->last_name ?? '',
                                            'image' => $student->user->image ?? '',
                                            'roll_no' => $student->roll_number,
                                            'admission_no' => $student->admission_no,
                                            'gender' => $student->user->gender,
                                            'dob' => $student->user->dob,
                                            'subjects' => $subjectArray,
                                            'address' => $student->user->current_address,
                                            'last_message' => $lastMessage ?? null,
                                            'class_name' => $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name,
                                            'isParent' => $user_type,
                                            'unread_message' => $unreadCount ?? 0,
                                        ];
                                    }
                                } else {

                                    $data[] = [
                                        'id' => $student->id,
                                        'user_id' => $student->user_id, // Assuming this is the correct property name
                                        'first_name' => $student->user->first_name ?? '',
                                        'last_name' => $student->user->last_name ?? '',
                                        'image' => $student->user->image ?? '',
                                        'roll_no' => $student->roll_number,
                                        'admission_no' => $student->admission_no,
                                        'gender' => $student->user->gender,
                                        'dob' => $student->user->dob,
                                        'subjects' => $subjects,
                                        'address' => $student->user->current_address,
                                        'last_message' => $lastMessage ?? null,
                                        'class_name' => $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name,
                                        'isParent' => $user_type,
                                        'unread_message' => $unreadCount ?? 0,
                                    ];
                                }
                            }
                        }
                    }

                    if ($user_type == 1) {
                        $parents = Parents::with('user')->whereIn('id', $unique_parents_ids)->get();
                        foreach ($parents as $parent) {
                            $unreadCount = 0;
                            $childArray = [];
                            if ($parent->user_id != 0) {
                                $lastMessage = ChatMessage::with('file')->where(function ($query) use ($parent, $teacher) {
                                    $query->where('modal_id', $parent->user_id)
                                        ->where('sender_id', $teacher->user->id);
                                })
                                    ->orWhere(function ($query) use ($parent, $teacher) {
                                        $query->where('modal_id', $teacher->user->id)
                                            ->where('sender_id', $parent->user_id);
                                    })
                                    ->select('body', 'date')
                                    ->latest()
                                    ->first();

                                $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $parent->user_id)->first();

                                if ($lastReadMessage) {

                                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                    if (! empty($lastReadMessageId)) {
                                        $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                    } else {
                                        $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->count();
                                    }
                                }
                                $children = $parent->children()->with('user', 'class_section')
                                    ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                                        $q->where('session_year_id', $session_year_id);
                                    })->get();

                                foreach ($children as $child) {
                                    $child_subject = $child->subjects();

                                    $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

                                    $elective_subjects = $child_subject['elective_subject'] ?? [];

                                    if ($elective_subjects) {
                                        $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                                    }

                                    $subject_id = array_merge($core_subjects, $elective_subjects);

                                    $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                                    $childArray[] = [
                                        'id' => $child->id,
                                        'user_id' => $child->user_id,
                                        'child_name' => $child->user->first_name.' '.$child->user->last_name,
                                        'class_name' => $child->class_section->class->name.' '.$child->class_section->section->name.' '.$child->class_section->class->medium->name,
                                        'admission_no' => $child->admission_no,
                                        'image' => $child->user->image,
                                        'subject' => $subjects ?? [],
                                    ];
                                }

                                if ($class_subject->type == 'Elective') {
                                    $student_subject = $child->student_subjects->where('subject_id', $class_subject->subject_id);

                                    if (! empty($student_subject->toArray())) {
                                        $data[] = [
                                            'id' => $parent->id,
                                            'user_id' => $parent->user_id, // Assuming this is the correct property name
                                            'first_name' => $parent->user->first_name ?? '',
                                            'last_name' => $parent->user->last_name ?? '',
                                            'email' => $parent->user->email ?? '',
                                            'mobile_no' => $parent->user->mobile ?? '',
                                            'occupation' => $parent->occupation ?? '',
                                            'image' => $parent->user->image ?? '',
                                            'last_message' => $lastMessage ?? null,
                                            'children' => $childArray ?? [],
                                            'isParent' => $user_type,
                                            'unread_message' => $unreadCount ?? 0,
                                        ];
                                    }
                                } else {
                                    $data[] = [
                                        'id' => $parent->id,
                                        'user_id' => $parent->user_id, // Assuming this is the correct property name
                                        'first_name' => $parent->user->first_name ?? '',
                                        'last_name' => $parent->user->last_name ?? '',
                                        'email' => $parent->user->email ?? '',
                                        'mobile_no' => $parent->user->mobile ?? '',
                                        'occupation' => $parent->occupation ?? '',
                                        'image' => $parent->user->image ?? '',
                                        'last_message' => $lastMessage ?? null,
                                        'children' => $childArray ?? [],
                                        'isParent' => $user_type,
                                        'unread_message' => $unreadCount ?? 0,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            $total_items = count($data) ?? 0;

            $unreadusers = array_filter($data, function ($user) {
                return $user['unread_message'] > 0;
            });

            $totalunreadusers = count($unreadusers);

            if ($search) {
                $filteredData = array_filter($data, function ($teacher) use ($search) {
                    $name = $teacher['first_name'].' '.$teacher['last_name'];

                    return stristr($name, $search) !== false;
                });
                $data = collect($filteredData)->sortByDesc(function ($user) {
                    return optional($user['last_message'])->date ?? 0;
                })->splice($offset, $limit)->values();
            } else {
                $data = collect($data)->sortByDesc(function ($user) {
                    return optional($user['last_message'])->date ?? 0;
                })
                    ->splice($offset, $limit)
                    ->values();
            }
            ResponseService::successResponse('Data Fetched Successfully', ['items' => $data, 'total_items' => $total_items, 'total_unread_users' => $totalunreadusers], [], 100);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|numeric',
            'message' => 'required_without:file',
            'file.*' => 'nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sender_id = $request->user()->id;
            $receiver_id = $request->receiver_id;

            $message = new ChatMessage;
            $message->modal_id = $receiver_id;
            $message->modal_type = 'App/Models/User';
            $message->sender_id = $sender_id;
            $message->body = $request->message ?? '';
            $message->date = Carbon::now();
            $message->save();

            $count = 0;
            $unreadCount = 0;

            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $uploadedFile) {

                    $originalName = $uploadedFile->getClientOriginalName();
                    $filePath = $uploadedFile->storeAs('chatfile', $originalName, 'public');

                    $file = new ChatFile;
                    $file->file_type = 1;
                    $file->file_name = $filePath;
                    $file->message_id = $message->id;
                    $file->save();
                    $count++;
                }
            }

            $readMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $sender_id)->first();
            if (empty($readMessage)) {
                $readMessage = new ReadMessage;
                $readMessage->modal_id = $receiver_id;
                $readMessage->modal_type = 'App/Models/User';
                $readMessage->user_id = $sender_id;
                $readMessage->save();
            }

            $message = ChatMessage::with('file')->where('id', $message->id)->select('id', 'sender_id', 'body', 'date')->get();

            foreach ($message as $message) {
                $chatfile = [];
                foreach ($message->file as $file) {
                    if (! empty($file)) {
                        $chatfile[] = asset('storage/'.$file->file_name);
                    } else {
                        $chatfile[] = '';
                    }
                }

                $data = [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'body' => $message->body,
                    'date' => $message->date,
                    'files' => $chatfile,
                ];
            }

            $teacher = Teacher::with('user', 'subjects.subject')->where('user_id', $sender_id)->first();

            $subjectData = [];

            if ($teacher) {
                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = [
                        'id' => $subject->subject->id,
                        'name' => $subject->subject->name,
                    ];
                }
            }

            $lastReadMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $teacher->user_id)->first();

            if ($lastReadMessage) {

                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                if (! empty($lastReadMessageId)) {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $teacher->user_id)->where('id', '>', $lastReadMessageId)->count();
                } else {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $teacher->user_id)->count();
                }
            }

            $userinfo = [
                'id' => $teacher->id,
                'user_id' => $teacher->user->id,
                'first_name' => $teacher->user->first_name,
                'last_name' => $teacher->user->last_name,
                'email' => $teacher->user->email,
                'qualification' => $teacher->qualification,
                'image' => $teacher->user->image,
                'mobile_no' => $teacher->user->mobile,
                'subjects' => $subjectData,
                'last_message' => $data ?? null,
                'unread_message' => $unreadCount ?? 0,
            ];

            $title = $teacher->user->first_name.' '.$teacher->user->last_name;
            $body = $request->message ?? $count.' Files Received';
            $type = 'chat';
            $image = null;
            $user[] = $receiver_id;

            $userinfo = (object) $userinfo;
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            ResponseService::successResponse('message_sent_successfully', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getUserChatMessage(Request $request)
    {
        try {

            $offset = $request->offset;
            $limit = $request->limit;

            $messages = ChatMessage::with(['file' => function ($query) {
                $query->select('message_id', 'file_name');
            }])
                ->where(function ($query) use ($request) {
                    $query->where('modal_id', $request->user_id)
                        ->orWhere('modal_id', Auth::id());
                })
                ->where(function ($query) use ($request) {
                    $query->where('sender_id', $request->user_id)
                        ->orWhere('sender_id', Auth::id());
                })
                ->select('id', 'sender_id', 'body', 'date')
                ->latest('date');

            $total_items = $messages->count();

            $messages = $messages->offset($offset)->limit($limit)->get()->toArray();

            foreach ($messages as &$message) {
                $message['files'] = collect($message['file'])->map(function ($file) {
                    return asset('storage/'.$file['file_name']);
                })->toArray();

                unset($message['file']);
            }
            ResponseService::successResponse('Data Fetched Successfully', ['items' => $messages ?? [], 'total_items' => $total_items], [], 100);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function readAllMessages(Request $request)
    {
        try {
            $auth = Auth::id();
            $user = $request->user_id;

            $lastMessage = ChatMessage::where('sender_id', $user)->where('modal_id', $auth)->latest()->first();
            if ($lastMessage) {
                $message_id = $lastMessage->id;
            }

            // Update Read Message id
            $readMessage = ReadMessage::where('modal_id', $auth)->where('user_id', $user)->first();

            if ($readMessage) {
                $readMessage->last_read_message_id = $message_id;
                $readMessage->save();
            }
            ResponseService::successResponse('Message Read');
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentResultPdf(Request $request)
    {
        try {
            $father_name = null;
            $mother_name = null;
            $guardian_name = null;

            $id = $request->student_id;
            $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

            $settings = getSettings();
            $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();

            $student = Students::select('id', 'roll_number', 'admission_no', 'admission_date', 'user_id', 'class_section_id', 'guardian_id', 'father_id', 'mother_id')->with('user:id,first_name,last_name,dob', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name', 'guardian:id,first_name,last_name')->where('id', $id)->first();

            $student_name = $student->user->first_name.' '.$student->user->last_name;

            if ($student->father) {
                $father_name = $student->father->first_name.' '.$student->father->last_name;
                $mother_name = $student->mother->first_name.' '.$student->mother->last_name;
            }

            if ($student->guardian) {
                $guardian_name = $student->guardian->first_name.' '.$student->guardian->last_name;
            }
            $admission_date = $student->admission_date;
            $gr_no = $student->admission_no;
            $dob = date('d-m-Y', strtotime($student->user->dob));
            $roll_number = $student->roll_number;
            $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

            $class_id = $student->class_section->class->id;

            $student_subject = $student->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }
            $subject_id = array_merge($core_subjects, $elective_subjects);

            $subjects = Subject::whereIn('id', $subject_id)->get();

            $exams = Exam::with(['exam_classes' => function ($q) use ($class_id) {
                $q->where('class_id', $class_id);
            }])
                ->with(['timetable' => function ($q) use ($class_id, $subject_id) {
                    $q->where('class_id', $class_id)->whereIn('subject_id', $subject_id);
                }])
                ->where('session_year_id', $settings['session_year'])
                ->where('publish', 1)
                ->whereHas('timetable', function ($q) use ($class_id, $subject_id) {
                    $q->where('class_id', $class_id)->whereIn('subject_id', $subject_id);
                })->get();

            $examarray = [];

            foreach ($exams as $exam) {
                $timetable = $exam->timetable;

                $filtered_timetable = [];

                foreach ($timetable as $exam_timetable) {
                    if (in_array($exam_timetable->subject_id, $subject_id)) {
                        $exam_marks = ExamMarks::where('exam_timetable_id', $exam_timetable->id)
                            ->where('student_id', $student->id)
                            ->where('session_year_id', $settings['session_year'])
                            ->first();

                        $filtered_timetable[] = [
                            'id' => $exam_timetable->id,
                            'exam_id' => $exam_timetable->exam_id,
                            'class_id' => $exam_timetable->class_id,
                            'subject_id' => $exam_timetable->subject_id,
                            'total_marks' => $exam_timetable->total_marks,
                            'passing_marks' => $exam_timetable->passing_marks,
                            'session_year' => $exam_timetable->session_year_id,
                            'exam_marks' => $exam_marks,
                        ];
                    }
                }

                if (! empty($filtered_timetable)) {
                    $examarray[] = [
                        'id' => $exam->id,
                        'name' => $exam->name,
                        'publish' => $exam->publish,
                        'timetable' => $filtered_timetable,
                    ];
                }
            }

            $subjectMarks = [];
            $totalMarks = null;

            foreach ($subjects as $subject) {
                $examObtainedMarks = null;
                $examTotalMarks = null;
                $subjectGrade = null;
                $subjectType = $subject->type;

                foreach ($examarray as $exam_data) {
                    foreach ($exam_data['timetable'] as $timetable) {
                        if ($timetable['subject_id'] == $subject->id) {
                            $exam_marks = $timetable['exam_marks'];

                            if ($exam_marks) {
                                $ObtainedMarks = $exam_marks['obtained_marks'];
                                $totalMarks = $timetable['total_marks'];

                                $examObtainedMarks += $ObtainedMarks;
                                $examTotalMarks += $totalMarks;

                                $subjectMarks[$subject->name.' ('.$subjectType.')'][$exam_data['name']] = $ObtainedMarks.'/'.$totalMarks;

                                if ($totalMarks > 0) {  // Check if totalMarks is greater than 0
                                    $percent = round(($ObtainedMarks / $totalMarks) * 100, 2);
                                    $subjectGrade = DB::table('grades')
                                        ->where('starting_range', '<=', $percent)
                                        ->where('ending_range', '>=', $percent)
                                        ->pluck('grade')
                                        ->first();
                                } else {
                                    $subjectGrade = null; // If totalMarks is 0 or not set, set grade to null
                                }
                            }

                            break 2;
                        }
                    }
                }

                // Store subject-wise total marks
                $subjectMarks[$subject->name.' ('.$subjectType.')']['total_obtained'] = $examObtainedMarks;
                $subjectMarks[$subject->name.' ('.$subjectType.')']['total_marks'] = $examTotalMarks;
                $subjectMarks[$subject->name.' ('.$subjectType.')']['grade'] = $subjectGrade;
            }

            $obtainmarks = array_sum(array_column($subjectMarks, 'total_obtained'));
            $totalmarks = array_sum(array_column($subjectMarks, 'total_marks')) ?? '';

            if ($obtainmarks == null && $totalmarks == null) {
                $percentage = null;
                $grade = null;
                $result = null;
            } else {
                $percentage = round(($obtainmarks / $totalmarks) * 100, 2);
                $grade = DB::table('grades')
                    ->where('starting_range', '<=', $percentage)
                    ->where('ending_range', '>=', $percentage)
                    ->pluck('grade')
                    ->first();
                $result = ($percentage >= 40) ? 'Passed' : 'Failed';
            }

            $data = [
                'student_name' => $student_name,
                'guardian_name' => $father_name ?? $guardian_name,
                'gr_no' => $gr_no,
                'dob' => $dob,
                'roll_number' => $roll_number,
                'class_section' => $class_section,
                'sessionYear' => $sessionYear,
                'date' => $date,
                'father_name' => $father_name,
                'subjects' => $subjectMarks,
                'totalMarks' => $totalmarks,
                'obtainmarks' => $obtainmarks,
                'percentage' => $percentage,
                'grade' => $grade,
                'result' => $result,
            ];
            // Load the HTML
            $pdf = PDF::loadView('students.result_template', compact('data', 'settings', 'exams', 'subjects'));

            // Get The Output Of PDF
            $output = $pdf->output();
            ResponseService::successResponse('Data Fetched Successfully', null, ['pdf' => base64_encode($output)]);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function applyLeave(Request $request)
    {
        $allowed = ['reason', 'leave_details', 'files'];

        // reject any extra keys
        $unknown = array_diff(array_keys($request->all()), $allowed);

        if (! empty($unknown)) {
            ResponseService::validationError('Unknown fields: '.implode(', ', $unknown));
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required',
            'leave_details.*' => 'required|array',

            // This enforces that "files" must be an array
            'files' => 'nullable|array',

            // Only if files is a proper array, validate each file
            'files.*' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // $sessionYear = SessionYear::where('id', $session_year_id)->first();

            $leave_master = LeaveMaster::where('session_year_id', $session_year_id)->first();

            // $public_holiday = Holiday::whereDate('date', '>=', $sessionYear->start_date)->whereDate('date', '<=', $sessionYear->end_date)->get()->pluck('date')->toArray();

            if (! $leave_master) {
                ResponseService::errorResponse('Kindly contact the school admin to update settings for continued access.');
            }

            $dates = array_column($request->leave_details, 'date');
            $from_date = min($dates);
            $to_date = max($dates);

            $data = [
                'user_id' => $request->user()->id,
                'reason' => $request->reason,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'leave_master_id' => $leave_master->id,
                'session_year_id' => $session_year_id,
                'status' => '0',
            ];

            $leave = Leave::create($data);

            // $leave_details = array();

            foreach ($request->leave_details as $key => $value) {
                $leaveDetail = new LeaveDetail;
                $leaveDetail->leave_id = $leave->id;
                $leaveDetail->date = date('Y-m-d', strtotime($value['date']));
                $leaveDetail->type = $value['type'];
                $leaveDetail->session_year_id = $session_year_id;
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
            ResponseService::successResponse('data_store_successfully', $leave ?? '');
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getMyLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'status' => 'in:0,1,2',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $setting = getSettings();
            $session_year_id = $request->session_year_id ?? $setting['session_year'];
            $leaveMaster = LeaveMaster::where('session_year_id', $session_year_id)->first();
            $sql = Leave::with('leave_detail', 'file')->where('user_id', Auth::user()->id)->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }])->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }])->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            })->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })->when($request->month, function ($q) use ($request) {
                $q->whereHas('leave_detail', function ($q) use ($request) {
                    $q->whereMonth('date', $request->month);
                });
            })->orderBy('id', 'DESC')->get();

            $sql = $sql->map(function ($sql) {
                $total_leaves = ($sql->half_leave / 2) + $sql->full_leave;
                $sql->days = $total_leaves;

                return $sql;
            });

            $data = [
                'monthly_allowed_leaves' => $leaveMaster->total_leave ?? 0,
                'taken_leaves' => $sql->where('status', 1)->sum('days'),
                'leave_details' => $sql,
            ];
            ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteLeave(Request $request)
    {
        try {

            $request->validate([
                'leave_id' => 'required|integer',
            ]);

            $leave = Leave::where('id', $request->leave_id)
                ->where('user_id', Auth::id())
                ->first();

            if (! $leave) {
                return ResponseService::validationError('Leave not found or unauthorized.');
            }

            $leave->delete();

            return ResponseService::successResponse('Data Deleted Successfully');
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentLeaveList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'nullable|integer|between:1,12',
            'class_section_id' => 'required|integer|exists:class_sections,id',
            'session_year_id' => 'required|integer|exists:session_years,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $classSection = ClassSection::find($request->class_section_id);

            if (! $classSection) {
                return ResponseService::errorResponse('Class section not found');
            }

            $class_id = $classSection->class_id;

            $classHasSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $request->session_year_id
            );

            $teacherSemesterAccess = [];

            /*
            |--------------------------------------------------------------------------
            | Get Teacher Semester Access
            |--------------------------------------------------------------------------
            */
            if ($classHasSemester) {

                $teacherSemesterAccess = ClassTeacher::where(
                    'class_teacher_id',
                    Auth::user()->teacher->id
                )
                    ->where('class_section_id', $request->class_section_id)
                    ->where('session_year_id', $request->session_year_id)
                    ->whereNotNull('semester_id')
                    ->pluck('semester_id')
                    ->toArray();
            }

            /*
            |--------------------------------------------------------------------------
            | Base Leave Query
            |--------------------------------------------------------------------------
            */
            $leaveQuery = Leave::with([
                'leave_detail',
                'user.student',
                'file',
            ])
                ->where('session_year_id', $request->session_year_id)

                // Only Student Users
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'Student');
                })

                // Filter Student Session
                ->whereHas('user.student.studentSessions', function ($query) use ($request) {
                    $query
                        ->where('session_year_id', $request->session_year_id)
                        ->where('class_section_id', $request->class_section_id);
                });

            /*
            |--------------------------------------------------------------------------
            | Semester Based Leave Filtering
            |--------------------------------------------------------------------------
            |
            | If teacher has semester access then leave from_date
            | must fall inside ANY of the assigned semester ranges
            |
            */
            if ($classHasSemester && ! empty($teacherSemesterAccess)) {

                $semesters = Semester::whereIn('id', $teacherSemesterAccess)->get();

                $leaveQuery->where(function ($query) use ($semesters) {

                    foreach ($semesters as $semester) {

                        $query->orWhereBetween('from_date', [
                            $semester->start_date,
                            $semester->end_date,
                        ]);
                    }
                });
            }

            /*
            |--------------------------------------------------------------------------
            | Month Filter
            |--------------------------------------------------------------------------
            */
            if (! empty($request->month)) {

                $leaveQuery->whereHas('leave_detail', function ($query) use ($request) {
                    $query->whereMonth('date', $request->month);
                });
            }

            $leaves = $leaveQuery
                ->latest()
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Transform Response
            |--------------------------------------------------------------------------
            */
            $leaves->map(function ($leave) use ($request) {

                $studentSession = $leave->user
                    ->student
                    ->studentSessions()
                    ->where('session_year_id', $request->session_year_id)
                    ->where('class_section_id', $request->class_section_id)
                    ->first();

                // Attach class_section_id directly
                $leave->user->student->class_section_id =
                    optional($studentSession)->class_section_id;

                // Remove studentSessions from response
                unset($leave->user->student->studentSessions);

                // Total Leave Days
                $leave->total_days = $leave->leave_detail->sum(function ($detail) {
                    return $detail->type === 'Full' ? 1 : 0.5;
                });

                return $leave;
            });

            $data = [
                'total_leave_requests' => $leaves->count(),
                'leave_details' => $leaves,
            ];

            return ResponseService::successResponse(
                'Data Fetched Successfully',
                $data
            );

        } catch (\Throwable $e) {

            return ResponseService::errorResponse(
                'error_occurred',
                null,
                103,
                $e
            );
        }
    }

    public function studentLeaveStatusUpdate(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|integer',
            'status' => 'required|in:0,1,2',
            'reason_of_rejection' => 'nullable|string',
        ]);

        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $leave = Leave::findOrFail($request->leave_id);

            $student = Students::with('user')->where('user_id', $leave->user_id)->first();

            $fatherId = $student->father_id;
            $motherId = $student->mother_id;
            $guardianId = $student->guardian_id;

            $parentUserIds = Parents::whereIn('id', [
                $fatherId,
                $motherId,
                $guardianId,
            ])->pluck('user_id');

            $leave->status = $request->status;
            $leave->reason_of_rejection = $request->reason_of_rejection;
            $leave->save();

            $title = 'Leave Alert';
            $type = 'leave';
            $image = null;
            $userinfo = null;

            $fullName = $student->user->first_name.' '.$student->user->last_name;

            if ($request->status == 1) {
                $body = "{$fullName} leave has been approved.";
            } elseif ($request->status == 2) {
                if ($request->reason_of_rejection) {
                    $body = "{$fullName} leave is rejected due to {$request->reason_of_rejection}.";
                } else {
                    $body = "{$fullName} leave is rejected.";
                }
            } else {
                return ResponseService::successResponse('data_update_successfully');
            }

            // No mass assignment here
            $notification = new Notification;
            $notification->send_to = 3;
            $notification->session_year_id = $session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            $rows = $parentUserIds->map(fn ($id) => [
                'notification_id' => $notification->id,
                'user_id' => $id,
            ])->toArray();

            UserNotification::insert($rows);

            sendSimpleNotification($parentUserIds, $title, $body, $type, $image, $userinfo);

            return ResponseService::successResponse('data_update_successfully');
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function dashboard(Request $request)
    {
        try {
            $current_day = Carbon::now()->format('l');
            $current_date = Carbon::now()->format('Y-m-d');
            $setting = getSettings();
            $session_year_id = $setting['session_year'];

            $teacher = $request->user()->teacher;
            $user_id = $teacher->user_id;
            $class_section_id = $teacher->class_sections()->where('session_year_id', $session_year_id)->pluck('class_section_id');

            // Find the class in which teacher is assigns as Class Teacher
            $class_teacher = ClassSection::whereIn('id', $class_section_id)->with('class.medium', 'section', 'class.streams', 'class.shifts')->get();

            // Find the Classes in which teacher is taking subjects
            $class_section_ids = $teacher->classes()->where('session_year_id', $session_year_id)->pluck('class_section_id');

            $class_sections = ClassSection::whereIn('id', $class_section_ids)->with('class.medium', 'section', 'class.streams', 'class.shifts')->get();
            $class_section = $class_sections->diff($class_teacher);

            $class_teacher_section_ids = ClassTeacher::where('class_teacher_id', $teacher->id)->where('session_year_id', $session_year_id)->pluck('class_section_id');

            // Teacher assignments
            $classTeacherAssignments = ClassTeacher::with('semester')
                ->where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->get();

            // Base query
            $student_leave_request = Leave::where('status', 0)
                ->where('to_date', '>=', $current_date)
                ->where('session_year_id', $session_year_id)
                ->with('leave_detail', 'user.student.class_section', 'file');

            // Apply teacher access filters
            $student_leave_request->where(function ($mainQuery) use ($classTeacherAssignments) {
                foreach ($classTeacherAssignments as $assignment) {
                    // FULL SESSION ACCESS (No semester)
                    if (! $assignment->semester_id) {
                        $mainQuery->orWhere(function ($q) use ($assignment) {
                            $q->whereHas('user.student', function ($studentQuery) use ($assignment) {
                                $studentQuery->where('class_section_id', $assignment->class_section_id);
                            });
                        });

                        continue;
                    }

                    // SEMESTER BASED ACCESS
                    $semester = $assignment->semester;

                    if (! $semester) {
                        continue;
                    }

                    // Find next semester
                    $nextSemester = Semester::where('session_year_id', $assignment->session_year_id)
                        ->where('start_date', '>', $semester->end_date)
                        ->orderBy('start_date', 'asc')
                        ->first();

                    // Effective end date including break
                    $effectiveEndDate = $nextSemester
                        ? Carbon::parse($nextSemester->start_date)->subDay()
                        : $semester->end_date;

                    $mainQuery->orWhere(function ($q) use (
                        $assignment,
                        $semester,
                        $effectiveEndDate
                    ) {

                        // Match class section
                        $q->whereHas('user.student', function ($studentQuery) use ($assignment) {
                            $studentQuery->where('class_section_id', $assignment->class_section_id);
                        });

                        // Semester + break range
                        $q->whereBetween('from_date', [
                            $semester->start_date,
                            $effectiveEndDate,
                        ]);
                    });
                }
            });

            // Month filter
            if ($request->month) {
                $student_leave_request->whereHas('leave_detail', function ($q) use ($request) {
                    $q->whereMonth('date', $request->month);
                });
            }

            if ($request->class_section_id) {
                $student_leave_request->whereHas('user.student', function ($q) use ($request) {
                    $q->where('class_section_id', $request->class_section_id);
                });
            }

            $student_leave_request->orderBy('id', 'DESC');

            $student_leave_request = $student_leave_request->get();

            // Calculate total days
            foreach ($student_leave_request as $leave) {

                $totalDays = 0;

                foreach ($leave->leave_detail as $detail) {

                    if ($detail->type == 'Full') {
                        $totalDays += 1;
                    } else {
                        $totalDays += 0.5;
                    }
                }

                $leave->total_days = $totalDays;
            }

            $subject_id = SubjectTeacher::where('teacher_id', $teacher->id)->where('session_year_id', $session_year_id)->pluck('id');
            $timetable = Timetable::whereIn('subject_teacher_id', $subject_id)->where('session_year_id', $session_year_id)
                ->where('day_name', $current_day)->with('class_section', 'subject')->orderBy('start_time', 'ASC')->get();

            $class_ids = ClassSection::with('class')->whereIn('id', $class_teacher_section_ids)->pluck('class_id');

            $sql = ExamClass::with('exam.session_year:id,name', 'exam.timetable.subject', 'class', 'class.medium', 'class.streams')->whereIn('class_id', $class_ids);

            $exam_data_db = $sql->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where('exam_id', $data->exam_id)->whereIn('class_id', $class_ids)->first();
                $starting_date = $starting_date_db['min(date)'];

                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where('exam_id', $data->exam_id)->whereIn('class_id', $class_ids)->first();
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

                // $request->status  =  0 :- all exams , 1 :- Upcoming , 2 :- On Going , 3 :- Completed

                if ($exam_status == 0) {
                    $exam_data[] = [
                        'id' => $data->exam->id,
                        'name' => $data->exam->name,
                        'description' => $data->exam->description,
                        'publish' => $data->exam->publish,
                        'session_year' => $data->exam->session_year->name,
                        'exam_starting_date' => $starting_date,
                        'exam_ending_date' => $ending_date,
                        'exam_status' => $exam_status,
                        'class_id' => $data->class_id,
                        'class_name' => $data->class->name.'-'.$data->class->medium->name,
                        'class_streams' => $data->class->streams->name ?? null,
                        'exam_timetable' => $data->exam->timetable,
                    ];
                }
            }

            if (! empty($exam_data)) {
                usort($exam_data, function ($a, $b) {
                    return strtotime($a['exam_starting_date']) - strtotime($b['exam_starting_date']);
                });
            }

            $events = Event::where('start_date', '>=', $current_date)->limit(5)->latest()->get();

            foreach ($events as $event) {
                if ($event->type == 'multiple') {
                    $hasdaySchedule = MultipleEvent::where('event_id', $event->id)->first();
                    if ($hasdaySchedule) {
                        $eventsList[] = [
                            'id' => $event->id,
                            'has_day_schedule' => 1,
                            'title' => $event->title,
                            'type' => $event->type,
                            'start_date' => $event->start_date,
                            'end_date' => $event->end_date,
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time,
                            'image' => $event->image,
                            'description' => $event->description,
                        ];
                    } else {
                        $eventsList[] = [
                            'id' => $event->id,
                            'has_day_schedule' => 0,
                            'title' => $event->title,
                            'type' => $event->type,
                            'start_date' => $event->start_date,
                            'end_date' => $event->end_date,
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time,
                            'image' => $event->image,
                            'description' => $event->description,
                        ];
                    }
                } else {
                    $eventsList[] = [
                        'id' => $event->id,
                        'has_day_schedule' => 0,
                        'title' => $event->title,
                        'type' => $event->type,
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date,
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'image' => $event->image,
                        'description' => $event->description,
                    ];
                }
            }

            $staff_leave_requests = Leave::where('status', 1)
                ->where('to_date', '>=', Carbon::now()->format('Y-m-d'))
                ->where('user_id', '!=', $user_id)
                ->with(['leave_detail', 'user.roles'])
                ->orderBy('id', 'DESC')
                ->get();

            // Group leave requests by date categories
            $today = Carbon::now()->format('Y-m-d');
            $tomorrow = Carbon::now()->addDay()->format('Y-m-d');
            $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');

            // Filter leave requests into categories
            $staff_leave_data = [
                'today' => [],
                'tomorrow' => [],
                'upcoming' => [],
            ];

            foreach ($staff_leave_requests as $leaveRequest) {
                foreach ($leaveRequest->leave_detail as $detail) {

                    $leaveInfo = [
                        'user_name' => $leaveRequest->user->full_name,
                        'image' => $leaveRequest->user->image,
                        'date' => date('d-m-Y', strtotime($detail->date)),
                        'role' => $leaveRequest->user->roles->pluck('name')->implode(', '),
                        'type' => $detail->type,
                    ];

                    // dd($leaveInfo);
                    if ($detail->date == $today) {
                        $staff_leave_data['today'][] = $leaveInfo;
                    } elseif ($detail->date == $tomorrow) {
                        $staff_leave_data['tomorrow'][] = $leaveInfo;
                    } elseif ($detail->date > $upcoming_date) {
                        $staff_leave_data['upcoming'][] = $leaveInfo;
                    }
                }
            }

            $staff_leave_data['upcoming'] = collect($staff_leave_data['upcoming'])
                ->sortBy(function ($item) {
                    return [$item['date'], $item['user_name']];
                })
                ->values()
                ->toArray();

            $gallery = app(GalleryService::class)->getGallery();

            $data = [
                'class_teacher' => $class_teacher ?? [],
                'other_classes' => $class_section ?? [],
                'student_leave_request' => $student_leave_request ?? [],
                'timetable' => $timetable ?? [],
                'upcoming_exams' => isset($exam_data) ? $exam_data : [],
                'staff_leaves' => $staff_leave_data ?? [],
                'events' => $eventsList ?? [],
                'gallery' => $gallery,
            ];
            ResponseService::successResponse('Data Fetched Successfully', $data ?? []);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateTimetableLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timetable_id' => 'required',
            'live_class_link' => 'nullable|url',
            'link_name' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $timetable = Timetable::where('id', $request->timetable_id)->first();
            $timetable->live_class_url = $request->live_class_link;
            $timetable->link_name = $request->link_name;
            $timetable->save();
            ResponseService::successResponse('data_update_successfully');
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
