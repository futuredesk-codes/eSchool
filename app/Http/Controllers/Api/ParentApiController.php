<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimetableCollection;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\ChatFile;
use App\Models\ChatMessage;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\File;
use App\Models\Holiday;
use App\Models\InstallmentFee;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Notification;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\PaidInstallmentFee;
use App\Models\Parents;
use App\Models\PaymentTransaction;
use App\Models\ReadMessage;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\StudentOnlineExamStatus;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\UserNotification;
use App\Services\AcademicCalendarService;
use App\Services\Payment\PaymentService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ParentApiController extends Controller
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

        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];

        $compulsory_fees_mode = getSettings('compulsory_fee_payment_mode');
        $compulsory_fees_mode = $compulsory_fees_mode['compulsory_fee_payment_mode'] ?? 0;

        $session_year = SessionYear::where('id', $session_year_id)->first();
        $isInstallment = $session_year->include_fee_installments;

        $due_date = $session_year->fee_due_date;
        $free_app_use_date = $session_year->free_app_use_date;

        $current_date = Carbon::now()->toDateString();

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $auth = Auth::user();
            if (! $auth->hasRole('Parent')) {
                ResponseService::errorResponse('Invalid Login Credentials', null, 101);
            }

            if ($request->fcm_id) {
                $auth->fcm_id = $request->fcm_id;
                $auth->save();
            }
            if ($request->device_type) {
                $auth->device_type = $request->device_type;
                $auth->save();
            }

            $token = $auth->createToken($auth->first_name)->plainTextToken;
            $user = $auth->load(['parent']);

            $children = Students::where('father_id', $user->parent->id)->orWhere('mother_id', $user->parent->id)->orWhere('guardian_id', $user->parent->id)->with('class_section')
                ->whereHas('studentSessions', function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                })->get();

            $parentDynamicFields = null;
            $dynamicField = $user->parent->dynamic_fields;

            if (! empty($dynamicField)) {
                $data = json_decode($dynamicField, true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if ($item != null) {
                            foreach ($item as $key => $value) {
                                $parentDynamicFields[$key] = $value;
                            }
                        }
                    }
                } else {
                    $parentDynamicFields = $data;
                }
            } else {
                $parentDynamicFields = null;
            }

            $user = flattenMyModel($user);

            $hasAnyValidSession = false;
            $hasAnyActiveStudent = false;

            foreach ($children as $child) {
                $child->first_name = $child->user->first_name;
                $child->last_name = $child->user->last_name;
                $child->image = $child->user->image;
                $child->dob = $child->user->dob;
                $child->current_address = $child->user->current_address;
                $child->permanent_address = $child->user->permanent_address;

                $studentSession = StudentSessions::where('session_year_id', $session_year_id)->where('student_id', $child->id)->first();

                // if new session is not activated for student
                if ($studentSession) {
                    $hasAnyValidSession = true;

                    // Active / studying check
                    if ($studentSession->status == 1) {
                        $hasAnyActiveStudent = true;
                    }

                    $class_id = $studentSession->class_section->class_id;

                    if ($compulsory_fees_mode == 1) {
                        if (isset($free_app_use_date)) {
                            if ($current_date >= $free_app_use_date) {
                                $fees_paid = FeesPaid::where('student_id', $child->id)->where('session_year_id', $session_year_id)->first();

                                if ($isInstallment == 0) {
                                    if ($fees_paid) {
                                        if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                            $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                        } else {
                                            $child->is_fee_payment_due = 0;
                                        }
                                    } else {
                                        $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                    }
                                } else {

                                    if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                        $child->is_fee_payment_due = 0;
                                    } else {
                                        // Installment case
                                        $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                        if ($installment_db->count()) {
                                            $installment_db_data = $installment_db->get();
                                            foreach ($installment_db_data as $data) {
                                                $paid_installment_data = PaidInstallmentFee::where(['student_id' => $child->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                                                $installment_data[] = [
                                                    'id' => $data->id,
                                                    'name' => $data->name,
                                                    'due_date' => date('Y-m-d', strtotime($data->due_date)),
                                                    'due_charges' => $data->due_charges,
                                                    'is_paid' => $paid_installment_data->status ?? 0,
                                                ];
                                            }
                                        }
                                        // Find the first unpaid installment and set its due date
                                        foreach ($installment_data as $data) {
                                            if ($data['is_paid'] == 0) {
                                                $due_date = $data['due_date'];
                                                break; // Stop after the first unpaid installment
                                            }
                                        }
                                        $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                    }
                                }
                            } else {
                                $child->is_fee_payment_due = 0;
                            }
                        } else {
                            $fees_paid = FeesPaid::where('student_id', $child->id)->where('session_year_id', $session_year_id)->first();

                            if ($isInstallment == 0) {
                                // Non-installment case
                                if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                    $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                } else {
                                    $child->is_fee_payment_due = 0;
                                }
                            } else {

                                if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                    $child->is_fee_payment_due = 0;
                                } else {
                                    // Installment case
                                    $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                    if ($installment_db->count()) {
                                        $installment_db_data = $installment_db->get();
                                        foreach ($installment_db_data as $data) {
                                            $paid_installment_data = PaidInstallmentFee::where(['student_id' => $child->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                                            $installment_data[] = [
                                                'id' => $data->id,
                                                'name' => $data->name,
                                                'due_date' => date('Y-m-d', strtotime($data->due_date)),
                                                'due_charges' => $data->due_charges,
                                                'is_paid' => $paid_installment_data->status ?? 0,
                                            ];
                                        }
                                    }
                                    // Find the first unpaid installment and set its due date
                                    foreach ($installment_data as $data) {
                                        if ($data['is_paid'] == 0) {
                                            $due_date = $data['due_date'];
                                            break; // Stop after the first unpaid installment
                                        }
                                    }
                                    $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            }
                        }
                    } else {
                        $child->is_fee_payment_due = 0;
                    }
                } else {
                    $child->is_fee_payment_due = 0;
                }

                $dynamicFields = null;
                $dynamicField = $child->dynamic_fields;

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
                $child->dynamic_fields = ! empty($dynamicFields) ? $dynamicFields : null;

                unset($child->user);

                if ($studentSession) {
                    $classSectionName = $studentSession->class_section->class->name.' '.$studentSession->class_section->section->name;

                    $child->is_semester_on_in_class = $studentSession->class_section->class->getIncludeSemestersForSession($session_year_id);
                    // Set Stream name
                    $streamName = $studentSession->class_section->class->streams->name ?? null;
                    if ($streamName !== null) {
                        $child->class_section_name = $classSectionName.' '.$streamName;
                    } else {
                        $child->class_section_name = $classSectionName;
                    }

                    // Set Medium name
                    $child->medium_name = $studentSession->class_section->class->medium->name;

                    // Set Shift name
                    $child->shift_id = $studentSession->class_section->class->shifts->id ?? '';
                    $child->shift = Shift::find($child->shift_id);
                    if ($child->shift) {
                        $child->shift->id;
                        $child->shift->title;
                        $child->shift->start_time;
                    }

                    $child->class_id = $studentSession->class_section->class_id;
                    $child->class_section_id = $studentSession->class_section_id;
                } else {
                    // Fallback to old behavior if no student session (though it might fail the valid session check later)
                    $classSectionName = $child->class_section->class->name.' '.$child->class_section->section->name;

                    $child->is_semester_on_in_class = $child->class_section->class->getIncludeSemestersForSession($session_year_id);
                    // Set Stream name
                    $streamName = $child->class_section->class->streams->name ?? null;
                    if ($streamName !== null) {
                        $child->class_section_name = $classSectionName.' '.$streamName;
                    } else {
                        $child->class_section_name = $classSectionName;
                    }

                    // Set Medium name
                    $child->medium_name = $child->class_section->class->medium->name;

                    // Set Shift name
                    $child->shift_id = $child->class_section->class->shifts->id ?? '';
                    $child->shift = Shift::find($child->shift_id);
                    if ($child->shift) {
                        $child->shift->id;
                        $child->shift->title;
                        $child->shift->start_time;
                    }
                    $child->class_id = $child->class_section->class_id;
                    $child->class_section_id = $child->class_section->id;
                }

                unset($child->class_section);

                // Set Category
                $child->category_name = $child->category->name;
                unset($child->category);
            }

            // If no child has a valid session
            if (! $hasAnyValidSession) {
                ResponseService::errorResponse(
                    'Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.',
                    null,
                    null
                );
            }

            // If all children are inactive
            if (! $hasAnyActiveStudent) {
                ResponseService::errorResponse(
                    'Your account is deactivated. Please contact admin for further help.',
                    null,
                    null
                );
            }

            $data = array_merge($user, ['dynamic_fields' => $parentDynamicFields ?? null, 'children' => $children->toArray()]);
            ResponseService::successResponse('User logged-in!', $data, ['token' => $token], 100);
        } else {
            ResponseService::errorResponse('Invalid Login Credentials', null, 101);
        }
    }

    /**
     * Get academic calendar PDF combining holidays, events, and exams.
     */
    public function getAcademicCalendarPdf(Request $request, AcademicCalendarService $service)
    {
        try {
            $student = Students::with('class_section')
                ->findOrFail($request->child_id);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $classIds = [StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)
                ->with('class_section')
                ->first()->class_section->class_id];

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

    public function subjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
            $subjects = $children->subjects();
            ResponseService::successResponse('Student Subject Fetched Successfully.', $subjects, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function classSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
            $subjects = $children->classSubjects();
            ResponseService::successResponse('Class Subject Fetched Successfully.', $subjects, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
            $child_subject = $children->subjects();

            $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

            $elective_subjects = $child_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = StudentSessions::where('student_id', $children->id)
                ->where('session_year_id', $session_year_id)
                ->first()
                ->class_section_id;
            $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $timetables = Timetable::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })
                ->with(['subject_teacher' => function ($q) use ($subject_id) {
                    $q->whereIn('subject_id', $subject_id);
                }])->orderBy('day', 'asc')->orderBy('start_time', 'asc')->get();

            $new_timetable = [];
            foreach ($timetables as $timetable) {
                if ($timetable->subject_teacher != null) {
                    $new_timetable[] = $timetable;
                }
            }
            ResponseService::successResponse('Timetable Fetched Successfully', new TimetableCollection($new_timetable), [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * subject_id : 2
     * lesson_id : 1 //OPTIONAL
     */
    public function getLessons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'lesson_id' => 'nullable|numeric',
            'subject_id' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = $request->user();
            $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
            $session_year_id = getSettings('session_year')['session_year'];
            $class_section_id = StudentSessions::where('student_id', $children->id)
                ->where('session_year_id', $session_year_id)
                ->first()
                ->class_section_id;

            $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $data = Lesson::where('class_section_id', $class_section_id)->where('subject_id', $request->subject_id)->with('topic', 'file');
            if ($request->lesson_id) {
                $data->where('id', $request->lesson_id);
            }

            if ($isClassHaveSemester && $currentSemester) {
                $data->where(function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                });
            }

            $data = $data->get();
            ResponseService::successResponse('Lessons Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * lesson_id : 1
     * topic_id : 1    //OPTIONAL
     */
    public function getLessonTopics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'topic_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            // $subjects = $children->subjects();
            $session_year_id = getSettings('session_year')['session_year'];

            // $student = $request->user()->student;
            $class_section_id = StudentSessions::where('student_id', $children->id)
                ->where('session_year_id', $session_year_id)
                ->first()
                ->class_section_id;

            $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $data = LessonTopic::where('lesson_id', $request->lesson_id)->with(['file', 'lesson']);
            if ($request->topic_id) {
                $data->where('id', $request->topic_id);
            }
            if ($isClassHaveSemester && $currentSemester) {
                $data->whereHas('lesson', function ($q) use ($currentSemester) {
                    $q->where(function ($subQuery) use ($currentSemester) {
                        $subQuery->where('semester_id', $currentSemester->id)
                            ->orWhereNull('semester_id');
                    });
                });
            }

            $data = $data->get();
            ResponseService::successResponse('Topics Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function getAssignments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'assignment_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'is_submitted' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = $request->user();
            $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
            $child_subject = $children->subjects();
            $child_id = $request->child_id;
            $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

            $elective_subjects = $child_subject['elective_subject'] ?? [];

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $class_section_id = StudentSessions::where('student_id', $child_id)
                ->where('session_year_id', $session_year_id)
                ->first()->class_section_id;

            $data = Assignment::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)->with('file', 'subject')
                ->with('submission', function ($q) use ($child_id) {
                    $q->where('student_id', $child_id)->with('file');
                });

            if ($request->assignment_id) {
                $data->where('id', $request->assignment_id);
            }
            if ($request->subject_id) {
                $data->where('subject_id', $request->subject_id);
            }
            if ($request->is_submitted) {
                if ($request->is_submitted == 1) {
                    $data->whereHas('submission', function ($q) use ($child_id) {
                        $q->where('student_id', $child_id);
                    })->get();
                } elseif ($request->is_submitted == 0) {
                    $data->has('submission', '<', 1)->get();
                }
            }

            $data = $data->orderBy('id', 'desc')->paginate();
            $data->getCollection()->transform(function ($assignment) {
                $submission = $assignment->submission;

                if ($submission instanceof \Illuminate\Database\Eloquent\Collection) {
                    $assignment->setRelation(
                        'submission',
                        $submission->isNotEmpty() ? $submission->first() : null
                    );
                }

                return $assignment;
            });

            ResponseService::successResponse('Assignments Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * month : 4 //OPTIONAL
     * year : 2022 //OPTIONAL
     */
    public function getAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'month' => 'nullable|numeric',
            'year' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $attendance = Attendance::where('student_id', $children->id)->where('session_year_id', $session_year_id);
            $holidays = new Holiday;
            $session_year_data = SessionYear::find($session_year_id);
            if (isset($request->month)) {
                $attendance = $attendance->whereMonth('date', $request->month);
                $holidays = $holidays->whereMonth('date', $request->month);
            }

            if (isset($request->year)) {
                $attendance = $attendance->whereYear('date', $request->year);
                $holidays = $holidays->whereYear('date', $request->year);
            }
            $attendance = $attendance->get();
            $holidays = $holidays->get();
            ResponseService::successResponse('Attendance Details Fetched Successfully', ['attendance' => $attendance, 'holidays' => $holidays, 'session_year' => $session_year_data], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAnnouncements(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:subject,noticeboard,class',
            'child_id' => 'required_if:type,subject,class|numeric',
            'subject_id' => 'required_if:type,subject|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $children = null;
            if ($request->type !== 'noticeboard') {
                $user = $request->user();
                $children = $user->parent->children()->first()->where('id', $request->child_id)->first();
                if (empty($children)) {
                    ResponseService::errorResponse('Invalid Child ID', null, 106);
                }
                $class_id = $children->class_section->class->id;
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = StudentSessions::where('student_id', $children->id)
                ->where('session_year_id', $session_year_id)
                ->first()
                ->class_section_id;

            if (isset($request->type) && $request->type == 'subject') {
                $table = SubjectTeacher::where('class_section_id', $class_section_id)->where('subject_id', $request->subject_id)->get()->pluck('id');
                if (empty($table)) {
                    ResponseService::errorResponse('Invalid Subject ID', null, 106);
                }
            }
            $data = Announcement::with('file')->where('session_year_id', $session_year_id);

            if (isset($request->type) && $request->type == 'noticeboard') {
                $data = $data->where('table_type', '');
            }

            if (isset($request->type) && $request->type == 'class') {
                $data = $data->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id);
            }

            if (isset($request->type) && $request->type == 'subject') {
                $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);
                $data = $data->where('table_type', "App\Models\SubjectTeacher")->whereIn('table_id', $table);
                if ($isClassHaveSemester) {
                    $currentSemester = Semester::where('session_year_id', $session_year_id)
                        ->get()
                        ->first(fn ($s) => $s->current);
                    $data->where('semester_id', $currentSemester->id);
                }
            }

            $data = $data->orderBy('id', 'desc')->paginate();
            ResponseService::successResponse('Announcement Details Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getTeachers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $user = $request->user();
            $children = Students::where('id', $request->child_id)->first();
            $student_subject = $children->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }
            $subject_id = array_merge($core_subjects, $elective_subjects);

            // Resolve class_section_id from StudentSessions for the current session year
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $children->id)->first();
            $class_section_id = $studentSession ? $studentSession->class_section_id : $studentSession->class_section_id;

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $studentSession->class_section->class_id,
                $session_year_id
            );

            $class_teachers_id = ClassTeacher::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })
                ->pluck('class_teacher_id')->toArray();

            $subject_teacher_id = SubjectTeacher::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)
                ->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })
                ->pluck('teacher_id')->toArray();

            $teacher_ids = array_merge($class_teachers_id, $subject_teacher_id);

            $teachers = Teacher::whereIn('id', $teacher_ids)->with([
                'user:id,first_name,last_name,image,mobile,email,dob',
                'subjects' => function ($query) use ($class_section_id, $session_year_id) {
                    $query->where('class_section_id', $class_section_id)
                        ->where('session_year_id', $session_year_id)
                        ->with('subject');
                },
            ])->get();
            foreach ($teachers as $teacher) {
                $subjectData = [];
                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = $subject->subject->name;
                }
                $subjects = implode(', ', $subjectData);

                $data[] = [
                    'id' => $teacher->id,
                    'user_id' => $teacher->user->id,
                    'first_name' => $teacher->user->first_name,
                    'last_name' => $teacher->user->last_name,
                    'qualification' => $teacher->qualification,
                    'email' => $teacher->user->email,
                    'image' => $teacher->user->image,
                    'dob' => $teacher->user->dob,
                    'mobile_no' => $teacher->user->mobile,
                    'subjects' => $subjects,
                ];
            }
            ResponseService::successResponse('Teacher Details Fetched Successfully', $data ?? [], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|nullable',
            'status' => 'nullable:digits:0,1,2,3',
            'get_timetable' => 'nullable',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::with('class_section')->where('id', $request->child_id)->first();

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)
                ->with('class_section')->first()->class_section->class_id;

            $child_subject = $student->subjects();

            $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

            $elective_subjects = $child_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            if ($request->filled('semester_id')) {
                $semester_id = Semester::where('id', $request->semester_id)
                    ->where('session_year_id', $session_year_id)
                    ->first();

                if (! $semester_id) {
                    ResponseService::validationError('Invalid semester_id for current session year.');
                }
            } else {
                $semester_id = Semester::where('session_year_id', $session_year_id)
                    ->get()
                    ->first(fn ($s) => $s->current);
            }

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $exam_data_db = ExamClass::with('exam.session_year:id,name', 'exam.timetable.subject')
                ->where('class_id', $class_id)
                ->whereHas('exam.timetable', function ($query) use ($subject_id) {
                    $query->whereIn('subject_id', $subject_id);
                })
                ->when($isClassHaveSemester, function ($query) use ($semester_id) {
                    $query->whereHas('exam', function ($q) use ($semester_id) {
                        $q->where('semester_id', $semester_id->id);
                    });
                })
                ->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $data->exam->id, 'class_id' => $class_id])->first();
                $starting_date = $starting_date_db['min(date)'];
                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $data->exam->id, 'class_id' => $class_id])->first();
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

                if (isset($request->status)) {
                    if ($request->status == 0) {
                        if ($request->get_timetable == 1) {
                            $exam_data[] = [
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                                'exam_timetable' => $data->exam->timetable,
                            ];
                        } else {
                            $exam_data[] = [
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                            ];
                        }
                    } elseif ($request->status == 1) {
                        if ($exam_status == 0) {
                            if ($request->get_timetable == 1) {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                    'exam_timetable' => $data->exam->timetable,
                                ];
                            } else {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                ];
                            }
                        }
                    } elseif ($request->status == 2) {
                        if ($exam_status == 1) {
                            if ($request->get_timetable == 1) {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                    'exam_timetable' => $data->exam->timetable,
                                ];
                            } else {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                ];
                            }
                        }
                    } else {
                        if ($exam_status == 2) {
                            if ($request->get_timetable == 1) {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                    'exam_timetable' => $data->exam->timetable,
                                ];
                            } else {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                ];
                            }
                        }
                    }
                } else {
                    if ($request->get_timetable == 1) {
                        $exam_data[] = [
                            'id' => $data->exam->id,
                            'name' => $data->exam->name,
                            'description' => $data->exam->description,
                            'publish' => $data->exam->publish,
                            'session_year' => $data->exam->session_year->name,
                            'exam_starting_date' => $starting_date,
                            'exam_ending_date' => $ending_date,
                            'exam_status' => $exam_status,
                            'exam_timetable' => $data->exam->timetable,
                        ];
                    } else {
                        $exam_data[] = [
                            'id' => $data->exam->id,
                            'name' => $data->exam->name,
                            'description' => $data->exam->description,
                            'publish' => $data->exam->publish,
                            'session_year' => $data->exam->session_year->name,
                            'exam_starting_date' => $starting_date,
                            'exam_ending_date' => $ending_date,
                            'exam_status' => $exam_status,
                        ];
                    }
                }
            }
            ResponseService::successResponse('Exam List Fetched Successfully', $exam_data ?? [], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|nullable',
            'exam_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::with('class_section')->where('id', $request->child_id)->first();
            $student_subject = $student->subjects();

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] == null ? [] : $student_subject['elective_subject']->pluck('subject_id')->toArray();

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $exam_data = Exam::with([
                'timetable' => function ($q) use ($request, $class_id, $subject_id) {
                    $q->where([
                        'exam_id' => $request->exam_id,
                        'class_id' => $class_id,
                    ])
                        ->whereIn('subject_id', $subject_id)
                        ->with(['subject'])
                        ->orderBy('date');
                },
            ])
                ->where('id', $request->exam_id)
                ->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })
                ->first();

            $data = isset($exam_data) ? $exam_data->timetable : [];
            ResponseService::successResponse('Exam Details Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric|exists:students,id',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $studentSession = StudentSessions::where('student_id', $request->child_id)
                ->where('session_year_id', $session_year_id)
                ->with('class_section')
                ->first();

            if (! $studentSession) {
                ResponseService::errorResponse('No session found for this student in the current session year');
            }

            $class_id = $studentSession->class_section->class_id;
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            $semesterId = null;
            if ($isClassHaveSemester) {
                if ($request->filled('semester_id')) {
                    $semester = Semester::where('id', $request->semester_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    if (! $semester) {
                        ResponseService::validationError('Invalid semester_id for current session year.');
                    }

                    $semesterId = $semester->id;
                } else {
                    $currentSemester = Semester::where('session_year_id', $session_year_id)
                        ->get()
                        ->first(fn ($semester) => $semester->current);

                    if (! $currentSemester) {
                        ResponseService::validationError('Current semester not found for current session year.');
                    }

                    $semesterId = $currentSemester->id;
                }
            }

            $exam_result_db = ExamResult::with(['student' => function ($q) {
                $q->select('id', 'user_id', 'roll_number')->with('user:id,first_name,last_name');
            }])->with('exam', 'session_year:id,name')->where('student_id', $request->child_id)
                ->where('session_year_id', $session_year_id)
                ->when($isClassHaveSemester && $semesterId, function ($query) use ($semesterId) {
                    $query->whereHas('exam', function ($q) use ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    });
                })
                ->get();

            if (count($exam_result_db)) {
                foreach ($exam_result_db as $result) {
                    $exam_timetable_id = ExamTimetable::where('exam_id', $result->exam_id)->pluck('id');

                    $exam_marks_db = ExamMarks::whereIn('exam_timetable_id', $exam_timetable_id)
                        ->where('student_id', $result->student_id)->get();

                    $class_data = ClassSection::where('id', $result->class_section_id)->with('class.medium', 'section')->first();

                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where([
                        'exam_id' => $result->exam_id,
                        'class_id' => $class_data->class_id,
                        'session_year_id' => $session_year_id,
                    ])->first();
                    $starting_date = $starting_date_db['min(date)'];

                    $exam_result = [];
                    $exam_result = [
                        'result_id' => $result->id,
                        'exam_id' => $result->exam_id,
                        'exam_name' => $result->exam->name,
                        'class_name' => $class_data->class->name.'-'.$class_data->section->name.' '.$class_data->class->medium->name,
                        'student_name' => $result->student->user->first_name.' '.$result->student->user->last_name,
                        'exam_date' => $starting_date,
                        'total_marks' => $result->total_marks,
                        'obtained_marks' => $result->obtained_marks,
                        'percentage' => $result->percentage,
                        'grade' => $result->grade,
                        'session_year' => $result->session_year->name,
                    ];

                    $exam_marks = [];
                    foreach ($exam_marks_db as $marks) {
                        $exam_marks[] = [
                            'marks_id' => $marks->id,
                            'subject_name' => $marks->subject->name,
                            'subject_type' => $marks->subject->type,
                            'total_marks' => $marks->timetable->total_marks,
                            'passing_marks' => $marks->timetable->passing_marks,
                            'obtained_marks' => $marks->obtained_marks,
                            'teacher_review' => $marks->teacher_review,
                            'grade' => $marks->grade,
                        ];
                    }
                    $data[] = [
                        'result' => $exam_result,
                        'exam_marks' => $exam_marks,
                    ];
                }
                ResponseService::successResponse('Exam Result Fetched Successfully', $data, [], 200);
            } else {
                ResponseService::successResponse('Exam Result Fetched Successfully', [], [], 200);
            }
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Get Fees Details
    public function getFeesDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $current_date = Carbon::now('UTC');
            $studentSession = StudentSessions::where('student_id', $request->child_id)
                ->where('session_year_id', $session_year_id)->first();
            if (! $studentSession) {
                ResponseService::errorResponse('No session found for this student in the current session year');
            }
            $class_id = $studentSession->class_section->class_id;

            // Total optional fees amount
            $optional_fees_amount = FeesClass::select(DB::raw('SUM(amount) as optional_fees_amount'))
                ->where(['class_id' => $class_id, 'choiceable' => 1, 'session_year_id' => $session_year_id])->first();
            $optional_fees_amount = $optional_fees_amount['optional_fees_amount'];

            // Total optional fees amount
            $compulsory_fees_amount = FeesClass::select(DB::raw('SUM(amount) as compulsory_fees_amount'))
                ->where(['class_id' => $class_id, 'choiceable' => 0, 'session_year_id' => $session_year_id])->first();
            $compulsory_fees_amount = $compulsory_fees_amount['compulsory_fees_amount'];

            // Fees Class Data
            $fees_class = FeesClass::where('class_id', $class_id)->where('session_year_id', $session_year_id)->with('fees_type')->get();

            // arrays for data
            $compulsory_fees_data = [];
            $optional_fees_data = [];
            $installment_data = [];

            $payment_transaction_db = PaymentTransaction::where(['student_id' => $request->child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->latest()->first();

            if ($payment_transaction_db) {
                if ($payment_transaction_db->date) {
                    $datetime = Carbon::parse($payment_transaction_db->date);
                    $time = $datetime->format('H:i:s');
                    $addHour = $datetime->copy()->addHour();
                    if (Carbon::now()->gt($addHour)) {
                        $payment_transaction_db->payment_status = 0;
                        $payment_transaction_db->save();
                    }
                }
                $payment_transaction_status = $payment_transaction_db->payment_status;
            }
            // Get the Compulsory Fees Data and Optional Fees Data
            foreach ($fees_class as $data) {
                if ($data->choiceable == 1) {
                    $paid_optional_data = FeesChoiceable::where(['student_id' => $request->child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'fees_type_id' => $data['fees_type_id'], 'status' => 1])->first();
                    $optional_fees_data[] = [
                        'id' => $data->fees_type_id,
                        'name' => $data->fees_type->name,
                        'amount' => $data->amount,
                        'is_paid' => $paid_optional_data->status ?? 0,
                        'paid_date' => $paid_optional_data->date ?? null,
                    ];
                } else {
                    $is_fully_paid_data = FeesPaid::where(['student_id' => $request->child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'is_fully_paid' => 1]);
                    $compulsory_fees_data[] = [
                        'id' => $data->fees_type_id,
                        'name' => $data->fees_type->name,
                        'amount' => $data->amount,
                        'is_paid' => ! empty($is_fully_paid_data->count()) ? 1 : 0,
                        'paid_on' => ! empty($is_fully_paid_data->count()) ? ((isset($is_fully_paid_data->first()->date) && ! empty($is_fully_paid_data->first()->date)) ? date('Y-m-d', strtotime($is_fully_paid_data->first()->date)) : null) : '',
                    ];
                }
            }

            // Checking for Due Charges Paid with Fully Compulsory Amount and Add it to Compulsory Fees Data Array
            if (isset($compulsory_fees_data) && ! empty($compulsory_fees_data)) {
                $paid_charges_due = FeesChoiceable::where(['student_id' => $request->child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'is_due_charges' => 1]);
                if ($paid_charges_due->count()) {
                    array_push($compulsory_fees_data, [
                        'id' => '',
                        'name' => 'Due Charges',
                        'amount' => $paid_charges_due->first()->total_amount,
                        'is_paid' => 1,
                    ]);
                }
            }

            // check the installments data for current session year
            $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
            if ($installment_db->count()) {
                $installment_db_data = $installment_db->get();
                foreach ($installment_db_data as $data) {
                    $paid_installment_data = PaidInstallmentFee::where([
                        'student_id' => $request->child_id,
                        'class_id' => $class_id,
                        'session_year_id' => $session_year_id,
                        'installment_fee_id' => $data['id'],
                        'status' => 1,
                    ])->first();

                    $installment_data[] = [
                        'id' => $data->id,
                        'name' => $data->name,
                        'due_date' => date('Y-m-d', strtotime($data->due_date)),
                        'due_charges' => $data->due_charges,
                        'is_paid' => $paid_installment_data->status ?? 0,
                        'paid_date' => $paid_installment_data->date ?? null,
                        'paid_due_charges' => ! empty($paid_installment_data) ? number_format($paid_installment_data->due_charges ?? 0, 2) : '',
                    ];
                }
            }
            // Due Date And Due Charges From Session Year For Fully pay Compulsory Amount (Without Installments)
            $session_year_data = SessionYear::where('id', $session_year_id)->first();
            $due_date = date('Y-m-d', strtotime($session_year_data->fee_due_date));
            $due_charges = $session_year_data->fee_due_charges;
            ResponseService::successResponse('Fees Details Fetched Successfully', null, [
                'compulsory_fees_data' => $compulsory_fees_data ?? [''],
                'optional_fees_data' => $optional_fees_data ?? [''],
                'installment_data' => $installment_data ?? (object) null,
                'compulsory_fees_total' => $compulsory_fees_amount ?? 0,
                'optional_fees_total' => $optional_fees_amount ?? 0,
                'compulsory_due_date' => $due_date,
                'compulsory_due_charges' => $due_charges,
                'current_date' => $current_date,
                'is_fee_pending' => (isset($payment_transaction_status) && $payment_transaction_status == 2) ? 1 : 0,
            ], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Store Fees Transaction
    public function storeFeesTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
            'payment_method' => 'required|in:1,2,3,4',
            'amount' => 'required',
            'type_of_fee' => 'required|in:0,1,2',
            'is_fully_paid' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $parent_id = Auth::user()->parent->id;
            $email = Auth::user()->email;
            $mobile = Auth::user()->mobile ?? '';
            $name = Auth::user()->full_name;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // variables for storing the data
            $payment_gateway_details = [];

            $class_id = StudentSessions::where('student_id', $request->child_id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            // variables for storing the data
            $optional_fees_store = [];
            $installment_fees_store = [];
            $paid_installment_id = [];
            $optional_fees_id = [];

            // add the data to transaction table local
            $payment_transaction_db = new PaymentTransaction;
            $payment_transaction_db->student_id = $request->child_id;
            $payment_transaction_db->class_id = $class_id;
            $payment_transaction_db->parent_id = $parent_id;
            $payment_transaction_db->mode = 2;
            $payment_transaction_db->type_of_fee = $request->type_of_fee;
            $payment_transaction_db->payment_gateway = $request->payment_method;
            $payment_transaction_db->payment_status = 2;
            $payment_transaction_db->total_amount = $request->amount;
            $payment_transaction_db->date = date('Y-m-d H:i:s');
            $payment_transaction_db->session_year_id = $session_year_id;
            $payment_transaction_db->save();

            // If Optional Fees Passed then insert data
            if (isset($request->optional_fees_data) && ! empty($request->optional_fees_data)) {
                foreach ($request->optional_fees_data as $data) {
                    $optional_fees_store = [
                        'student_id' => $request->child_id,
                        'class_id' => $class_id,
                        'fees_type_id' => $data['id'],
                        'is_due_charges' => 0,
                        'total_amount' => $data['amount'],
                        'session_year_id' => $session_year_id,
                        'date' => date('Y-m-d'),
                        'payment_transaction_id' => $payment_transaction_db->id,
                        'status' => 0,
                    ];
                    $optional_fees_id[] = FeesChoiceable::insertGetId($optional_fees_store);
                }
            }

            // If Installment Fees Passed then insert data
            if (isset($request->installment_data) && ! empty($request->installment_data)) {
                foreach ($request->installment_data as $data) {
                    $installment_fees_store = [
                        'class_id' => $class_id,
                        'student_id' => $request->child_id,
                        'parent_id' => $parent_id,
                        'installment_fee_id' => $data['id'],
                        'session_year_id' => $session_year_id,
                        'amount' => $data['amount'],
                        'due_charges' => $data['due_charges'] ?? null,
                        'date' => date('Y-m-d'),
                        'payment_transaction_id' => $payment_transaction_db->id,
                        'status' => 0,
                    ];
                    $paid_installment_id[] = PaidInstallmentFee::insertGetId($installment_fees_store);
                }
            }
            $paymentIntent = PaymentService::create($request->payment_method)->createAndFormatPaymentIntent(round((float) $request->amount, 2), [
                'student_id' => $request->child_id,
                'parent_id' => $parent_id,
                'class_id' => $class_id,
                'email' => $email,
                'name' => $name,
                'mobile' => $mobile,
                'session_year_id' => $session_year_id,
                'payment_transaction_id' => $payment_transaction_db->id,
                'is_fully_paid' => $request->is_fully_paid,
                'type_of_fee' => $request->type_of_fee,
                'is_due_charges' => (isset($request->due_charges) && $request->due_charges >= 0) ? 1 : 0,
                'due_charges' => $request->due_charges,
                'optional_fees_paid' => json_encode($optional_fees_id) ?? '',
                'installment_fees_paid' => json_encode($paid_installment_id) ?? '',
            ]);

            $payment_transaction_update = PaymentTransaction::find($payment_transaction_db->id);
            $payment_transaction_update->order_id = $paymentIntent['id'];
            $payment_transaction_update->save();

            $payment_gateway_details = [
                ...$paymentIntent,
                'payment_transaction_id' => $payment_transaction_db->id,
            ];
            ResponseService::successResponse('Fees Transaction Stored Successfully', [], ['payment_gateway_details' => $payment_gateway_details], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // add the transaction data in transaction table
    public function storeFees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'payment_id' => 'nullable',
            'payment_signature' => 'nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Updating the Payment Transaction
            $transaction_db = PaymentTransaction::findOrFail($request->transaction_id);
            if ($request->payment_id) {
                $transaction_db->payment_id = $request->payment_id;
            }
            if ($request->payment_signature) {
                $transaction_db->payment_signature = $request->payment_signature;
            }
            $transaction_db->save();
            ResponseService::successResponse('Data Updated Successfully', null, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // get the fees paid list
    public function feesPaidList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $fees_paid = FeesPaid::where(['student_id' => $request->child_id])->with('session_year:id,name', 'class.medium')->get();
            ResponseService::successResponse('Fees Paid List Fetched Successfully', $fees_paid, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Generate The Reciept
    public function feesPaidReceiptPDF(Request $request)
    {
        try {
            $parent = Auth::user();
            $parent_name = $parent->first_name.' '.$parent->last_name;
            $logo = env('LOGO2');
            $logo = public_path('/storage/'.$logo);
            $school_name = env('APP_NAME');
            $school_address = getSettings('school_address');
            $school_address = $school_address['school_address'];

            $currency_symbol = getSettings('currency_symbol');
            if (isset($currency_symbol) && count($currency_symbol)) {
                $currency_symbol = $currency_symbol['currency_symbol'];
            } else {
                $currency_symbol = null;
            }

            // Getting the Fees Paid Data
            $fees_paid = FeesPaid::where('id', $request->fees_paid_id)->with('student.user:id,first_name,last_name', 'class', 'session_year')->get()->first();

            // Check That Fees Paid Data Exists Or Not
            if (! $fees_paid) {
                ResponseService::errorResponse('No Fees Paid Found', null, 103);
            }

            // Variables
            $student_id = $fees_paid->student_id;
            $class_id = $fees_paid->class_id;
            $session_year_id = $fees_paid->session_year_id;

            // Paid Installment Data
            $paid_installment = PaidInstallmentFee::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'status' => 1])->with('installment_fee')->get();

            // Fees Choiceable Data
            $optional_fees_type_id = FeesClass::where(['class_id' => $class_id, 'choiceable' => 1])->pluck('fees_type_id');
            $fees_choiceable = FeesChoiceable::whereIn('fees_type_id', $optional_fees_type_id)->where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'status' => 1])->with('fees_type')->orderby('id', 'asc')->get();

            // Fees Class Data
            $fees_class = FeesClass::where(['class_id' => $class_id, 'choiceable' => 0])->with('fees_type')->get();

            // Session Year Data
            $session_year = SessionYear::where('id', $session_year_id)->first();

            // Load the HTML
            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'school_name', 'fees_paid', 'paid_installment', 'fees_choiceable', 'currency_symbol', 'school_address', 'fees_class', 'session_year'));

            // Get The Output Of PDF
            $output = $pdf->output();
            ResponseService::successResponse('Fees Paid Receipt PDF Fetched Successfully', null, ['pdf' => base64_encode($output)], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::where('id', $request->child_id)->first();

            $student_subject = $student->subjects();
            // $class_subject = $student->classSubjects();

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            // get current
            $time_data = Carbon::now()->toArray();
            $current_date_time = $time_data['formatted'];

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            // checks the subject id param is passed or not .
            // query meets the condition for both class section and class
            if (isset($request->subject_id) && ! empty($request->subject_id)) {
                $exam_data_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])
                    ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                        if ($currentSemester) {
                            $q->where('semester_id', $currentSemester->id);
                        }
                    })
                    ->has('question_choice')->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })->orWhere(function ($query) use ($class_id, $session_year_id, $current_date_time, $student, $request, $isClassHaveSemester, $currentSemester) {
                        $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])
                            ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                                if ($currentSemester) {
                                    $q->where('semester_id', $currentSemester->id);
                                }
                            })
                            ->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                                $q->where('student_id', $student->id);
                            });
                    })->orderby('start_date')->paginate(15)->toArray();
            } else {
                $exam_data_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])->whereIn('subject_id', $subject_id)
                    ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                        if ($currentSemester) {
                            $q->where('semester_id', $currentSemester->id);
                        }
                    })
                    ->has('question_choice')->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })->orWhere(function ($query) use ($class_id, $subject_id, $session_year_id, $current_date_time, $student, $isClassHaveSemester, $currentSemester) {
                        $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])->whereIn('subject_id', $subject_id)
                            ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                                if ($currentSemester) {
                                    $q->where('semester_id', $currentSemester->id);
                                }
                            })
                            ->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                                $q->where('student_id', $student->id);
                            });
                    })->orderby('start_date')->paginate(15)->toArray();
            }
            if (isset($exam_data_db) && ! empty($exam_data_db)) {

                $exam_data = [];
                $exam_list = [];
                // making the array of exam data
                foreach ($exam_data_db['data'] as $data) {

                    // total marks of exams
                    $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->first();
                    $total_marks = $total_marks['sum(marks)'];

                    if ($data['model_type'] == 'App\Models\ClassSection') {
                        $class_section_data = ClassSection::where('id', $data['model_id'])->with('class.medium', 'section')->first();
                        $class_name = $class_section_data->class->name.' - '.$class_section_data->section->name.' '.$class_section_data->class->medium->name;
                    } else {
                        $class_data = ClassSchool::where('id', $data['model_id'])->with('medium')->first();
                        $class_name = $class_data->name.' '.$class_data->medium->name;
                    }

                    $exam_list[] = [
                        'exam_id' => $data['id'],
                        'class' => [
                            'id' => $data['model_id'],
                            'name' => $class_name,
                        ],
                        'subject' => [
                            'id' => $data['subject_id'],
                            'name' => $data['subject']['name'].' - '.$data['subject']['type'],
                        ],
                        'title' => $data['title'],
                        'exam_key' => $data['exam_key'],
                        'duration' => $data['duration'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'total_marks' => $total_marks,
                    ];
                }

                // adding the exam data with pagination data
                $exam_data = [
                    'current_page' => $exam_data_db['current_page'],
                    'data' => $exam_list,
                    'from' => $exam_data_db['from'],
                    'last_page' => $exam_data_db['last_page'],
                    'per_page' => $exam_data_db['per_page'],
                    'to' => $exam_data_db['to'],
                    'total' => $exam_data_db['total'],
                ];
            } else {
                // if no data found
                $exam_data = null;
            }
            ResponseService::successResponse('Exam List Fetched Successfully', $exam_data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamResultList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::where('id', $request->child_id)->first();

            // current session year id
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            // get the class subject id on the basis of subject id passed
            // query meets the condition for both class section and class
            if (isset($request->subject_id) && ! empty($request->subject_id)) {
                $online_exam_db = OnlineExam::where([
                    'model_type' => 'App\Models\ClassSection',
                    'model_id' => $class_section_id,
                    'subject_id' => $request->subject_id,
                    'session_year_id' => $session_year_id,
                ])->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })->orWhere(function ($query) use ($class_id, $session_year_id, $request, $student) {
                    $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'session_year_id' => $session_year_id, 'subject_id' => $request->subject_id])->with('subject')->whereHas('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                })->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                    if ($currentSemester) {
                        $q->where('semester_id', $currentSemester->id);
                    }
                })->with('subject')->paginate(10)->toArray();
            } else {
                $online_exam_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'session_year_id' => $session_year_id])->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })->orWhere(function ($query) use ($class_id, $session_year_id, $student) {
                    $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'session_year_id' => $session_year_id])->with('subject')->whereHas('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                })->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                    if ($currentSemester) {
                        $q->where('semester_id', $currentSemester->id);
                    }
                })->with('subject')->paginate(10)->toArray();
            }
            $exam_list_data = [];
            foreach ($online_exam_db['data'] as $data) {
                // get the choice question id
                $exam_submitted_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('question_id');
                $exam_submitted_date = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('submitted_date')->first();

                $question_ids = OnlineExamQuestionChoice::whereIn('id', $exam_submitted_question_ids)->pluck('question_id');

                $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('option_id');

                // removes the question id of the question if one of the answer of particular question is wrong
                foreach ($question_ids as $question_id) {
                    $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id', $question_id)->whereNotIn('answer', $exam_attempted_answers)->count();
                    if ($check_questions_answers_exists) {
                        unset($question_ids[array_search($question_id, $question_ids->toArray())]);
                    }
                }

                $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id', $question_ids)->whereIn('answer', $exam_attempted_answers)->pluck('question_id');

                // get the data of only attempted data
                $total_obtained_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->whereIn('question_id', $exam_correct_answers_question_id)->first();
                $total_obtained_marks_exam = $total_obtained_marks_exam['sum(marks)'];
                $total_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->first();
                $total_marks_exam = $total_marks_exam['sum(marks)'];

                $exam_list_data[] = [
                    'online_exam_id' => $data['id'],
                    'subject' => [
                        'id' => $data['subject_id'],
                        'name' => $data['subject']['name'].' - '.$data['subject']['type'],
                    ],
                    'title' => $data['title'],
                    'obtained_marks' => $total_obtained_marks_exam ?? '0',
                    'total_marks' => $total_marks_exam ?? '0',
                    'exam_submitted_date' => $exam_submitted_date ?? date('Y-m-d', strtotime($data['end_date'])),
                ];
            }
            $exam_list = [
                'current_page' => $online_exam_db['current_page'],
                'data' => $exam_list_data ?? '',
                'from' => $online_exam_db['from'],
                'last_page' => $online_exam_db['last_page'],
                'per_page' => $online_exam_db['per_page'],
                'to' => $online_exam_db['to'],
                'total' => $online_exam_db['total'],
            ];
            ResponseService::successResponse('Exam List Fetched Successfully', $exam_list, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamResult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required_|numeric',
            'online_exam_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::where('id', $request->child_id)->first();

            // get the total questions count
            $total_questions = OnlineExamQuestionChoice::where('online_exam_id', $request->online_exam_id)->count();

            // get the exam's choiced question id
            $exam_choiced_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $request->online_exam_id])->pluck('question_id');

            // get the questions id
            $question_ids = OnlineExamQuestionChoice::whereIn('id', $exam_choiced_question_ids)->pluck('question_id');

            // get the options submitted by student
            $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $request->online_exam_id])->pluck('option_id');

            // removes the question id of the question if one of the answer of particular question is wrong
            foreach ($question_ids as $question_id) {
                $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id', $question_id)->whereNotIn('answer', $exam_attempted_answers)->count();
                if ($check_questions_answers_exists) {
                    unset($question_ids[array_search($question_id, $question_ids->toArray())]);
                }
            }

            // get the correct answers counter
            $exam_correct_answers = OnlineExamQuestionAnswer::whereIn('question_id', $question_ids)->whereIn('answer', $exam_attempted_answers)->groupby('question_id')->pluck('question_id')->count();

            // question id of correct answers
            $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id', $question_ids)->whereIn('answer', $exam_attempted_answers)->pluck('question_id');

            // data of correct answers
            $exam_correct_answers_data = OnlineExamQuestionAnswer::whereIn('question_id', $question_ids)->whereIn('answer', $exam_attempted_answers)->groupby('question_id')->get();

            // array of correct answer with choiced exam id and marks
            $correct_answers_data = [];
            foreach ($exam_correct_answers_data as $correct_data) {
                $choice_questions = OnlineExamQuestionChoice::where(['online_exam_id' => $request->online_exam_id, 'question_id' => $correct_data->question_id])->first();
                $correct_answers_data[] = [
                    'question_id' => $choice_questions->id,
                    'marks' => $choice_questions->marks,
                ];
            }

            // get questions ids
            $all_questions_ids = OnlineExamQuestionChoice::whereNotIn('question_id', $question_ids)->where('online_exam_id', $request->online_exam_id)->pluck('question_id');

            // get the incorrect answers && unattempted counter
            $exam_in_correct_answers = OnlineExamQuestionAnswer::whereIn('question_id', $all_questions_ids)->whereNotIn('answer', $exam_attempted_answers)->groupby('question_id')->pluck('question_id')->count();

            // data of in correct && unattempted answers
            $exam_in_correct_answers_data = OnlineExamQuestionAnswer::whereIn('question_id', $all_questions_ids)->whereNotIn('answer', $exam_attempted_answers)->groupby('question_id')->get();

            // array of in correct answer && unattempted with choiced exam id and marks
            $in_correct_answers_data = [];
            foreach ($exam_in_correct_answers_data as $in_correct_data) {
                $choice_questions = OnlineExamQuestionChoice::where(['online_exam_id' => $request->online_exam_id, 'question_id' => $in_correct_data->question_id])->first();
                if (isset($choice_questions) && ! empty($choice_questions)) {
                    $in_correct_answers_data[] = [
                        'question_id' => $choice_questions->id,
                        'marks' => $choice_questions->marks,
                    ];
                }
            }

            // total obtained and total marks
            $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $request->online_exam_id)->whereIn('question_id', $exam_correct_answers_question_id)->first();
            $total_obtained_marks = $total_obtained_marks['sum(marks)'];
            $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $request->online_exam_id)->first();
            $total_marks = $total_marks['sum(marks)'];

            // final array data
            $exam_result = [
                'total_questions' => $total_questions,
                'correct_answers' => [
                    'total_questions' => $exam_correct_answers,
                    'question_data' => $correct_answers_data ?? '',
                ],
                'in_correct_answers' => [
                    'total_questions' => $exam_in_correct_answers,
                    'question_data' => $in_correct_answers_data ?? '',
                ],
                'total_obtained_marks' => $total_obtained_marks ?? '0',
                'total_marks' => $total_marks,
            ];
            ResponseService::successResponse('Exam Result Fetched Successfully', $exam_result ?? '', [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::where('id', $request->child_id)->first();

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            // get current
            $time_data = Carbon::now()->toArray();
            $current_date_time = $time_data['formatted'];

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $exam_query = OnlineExam::where([
                'model_type' => 'App\Models\ClassSection',
                'model_id' => $class_section_id,
                'subject_id' => $request->subject_id,
                'session_year_id' => $session_year_id,
                ['start_date', '<=', $current_date_time],
            ])->orWhere(function ($query) use ($class_id, $session_year_id, $request, $current_date_time) {
                $query->where([
                    'model_type' => 'App\Models\ClassSchool',
                    'model_id' => $class_id,
                    'subject_id' => $request->subject_id,
                    'session_year_id' => $session_year_id,
                    ['start_date', '<=', $current_date_time],
                ]);
            })->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester->id);
            });

            $exam_exists = $exam_query->count();
            $exam_query_without_session_year = OnlineExam::where([
                'model_type' => 'App\Models\ClassSection',
                'model_id' => $class_section_id,
                'subject_id' => $request->subject_id,
                ['start_date', '<=', $current_date_time],
            ])->orWhere(function ($query) use ($class_id, $request, $current_date_time) {
                $query->where([
                    'model_type' => 'App\Models\ClassSchool',
                    'model_id' => $class_id,
                    'subject_id' => $request->subject_id,
                    ['start_date', '<=', $current_date_time],
                ]);
            });

            // checks the exams exists
            if (isset($exam_exists) && ! empty($exam_exists)) {
                // total online exams id and counts
                $total_exam_ids = $exam_query->pluck('id');
                // online exam ids attempted
                $attempted_online_exam_ids = StudentOnlineExamStatus::where('student_id', $student->id)->whereIn('online_exam_id', $total_exam_ids)->pluck('online_exam_id');

                // get the submitted answers (i.e. option id)
                $online_exams_attempted_answers = OnlineExamStudentAnswer::where('student_id', $student->id)->whereIn('online_exam_id', $total_exam_ids)->pluck('option_id');

                // get the submitted choiced question id
                $online_exams_submitted_question_ids = OnlineExamStudentAnswer::where('student_id', $student->id)->whereIn('online_exam_id', $total_exam_ids)->pluck('question_id');

                // get the questions id
                $get_question_ids = OnlineExamQuestionChoice::whereIn('id', $online_exams_submitted_question_ids)->pluck('question_id');

                // removes the question id of the question if one of the answer of particular question is wrong
                foreach ($get_question_ids as $question_id) {
                    $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id', $question_id)->whereNotIn('answer', $online_exams_attempted_answers)->count();
                    if ($check_questions_answers_exists) {
                        unset($get_question_ids[array_search($question_id, $get_question_ids->toArray())]);
                    }
                }
                // get the correct answers question id
                $correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id', $get_question_ids)->whereIn('answer', $online_exams_attempted_answers)->pluck('question_id');

                // total exams
                $total_exams = $exam_query_without_session_year->count();

                // total exam attempted
                $total_attempted_exams = StudentOnlineExamStatus::where('student_id', $student->id)->whereIn('online_exam_id', $total_exam_ids)->count();

                // total missed exams
                $total_missed_exams = $exam_query_without_session_year->whereNotIn('id', $attempted_online_exam_ids)->count();

                // get the correct choiced question id and marks
                $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->whereIn('online_exam_id', $total_exam_ids)->whereIn('question_id', $correct_answers_question_id)->first();
                $total_obtained_marks = $total_obtained_marks['sum(marks)'];

                // overall total marks
                $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->whereIn('online_exam_id', $total_exam_ids)->first();
                $total_marks = $total_marks['sum(marks)'];

                if ($total_obtained_marks) {
                    $percentage = number_format(($total_obtained_marks * 100) / $total_marks, 2);
                }

                // particular online exam data
                $online_exams_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id, ['start_date', '<=', $current_date_time]])->orWhere(function ($query) use ($class_id, $session_year_id, $request, $current_date_time) {
                    $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id, ['start_date', '<=', $current_date_time]]);
                })->with(['student_attempt' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                }])->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })->has('question_choice')->paginate(10)->toArray();

                $exam_list = [];
                $total_obtained_marks_exam = '';
                foreach ($online_exams_db['data'] as $data) {
                    $exam_submitted_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('question_id');
                    $get_exam_question_ids = OnlineExamQuestionChoice::whereIn('id', $exam_submitted_question_ids)->pluck('question_id');

                    $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('option_id');

                    // removes the question id of the question if one of the answer of particular question is wrong
                    foreach ($get_exam_question_ids as $question_id) {
                        $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id', $question_id)->whereNotIn('answer', $exam_attempted_answers)->count();
                        if ($check_questions_answers_exists) {
                            unset($get_exam_question_ids[array_search($question_id, $get_exam_question_ids->toArray())]);
                        }
                    }

                    $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id', $get_exam_question_ids)->whereIn('answer', $exam_attempted_answers)->pluck('question_id');

                    $total_obtained_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->whereIn('question_id', $exam_correct_answers_question_id)->first();
                    $total_obtained_marks_exam = $total_obtained_marks_exam['sum(marks)'];
                    $total_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->first();
                    $total_marks_exam = $total_marks_exam['sum(marks)'];

                    $exam_list[] = [
                        'online_exam_id' => $data['id'],
                        'title' => $data['title'],
                        'obtained_marks' => $total_obtained_marks_exam ?? '0',
                        'total_marks' => $total_marks_exam ?? '0',
                    ];
                }

                // array of final data
                $online_exam_report_data = [
                    'total_exams' => $total_exams,
                    'attempted' => $total_attempted_exams,
                    'missed_exams' => $total_missed_exams,
                    'total_marks' => $total_marks ?? '0',
                    'total_obtained_marks' => $total_obtained_marks ?? '0',
                    'percentage' => $percentage ?? '0',
                    'exam_list' => [
                        'current_page' => $online_exams_db['current_page'],
                        'data' => $exam_list,
                        'from' => $online_exams_db['from'],
                        'last_page' => $online_exams_db['last_page'],
                        'per_page' => $online_exams_db['per_page'],
                        'to' => $online_exams_db['to'],
                        'total' => $online_exams_db['total'],
                    ],
                ];
            }
            ResponseService::successResponse('Online Exam Report Fetched Successfully', $online_exam_report_data ?? [], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAssignmentReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = Students::where('id', $request->child_id)->first();

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $student = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first();
            $student_class_section_id = $student->class_section_id;
            $student_class_id = $student->class_section->class_id;

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $student_class_id,
                $session_year_id
            );

            $assignment = Assignment::where([
                'class_section_id' => $student_class_section_id,
                'session_year_id' => $session_year_id,
                'subject_id' => $request->subject_id,
            ])->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester->id);
            });
            $assignment_count_query = clone $assignment;
            $total_assignment_submitted_points = $assignment->sum('points');

            $assignment_for_ids = clone $assignment;
            $assingment_ids = $assignment_for_ids->pluck('id');
            $assignment_submission = AssignmentSubmission::where('student_id', $student->id)->whereIn('assignment_id', $assingment_ids);
            $assignment_submission_count_query = clone $assignment_submission;
            $total_points_obtained = $assignment_submission->sum('points');

            if ($total_points_obtained) {
                $percentage = number_format(($total_points_obtained * 100) / $total_assignment_submitted_points, 2);
            }

            $assignment_submission_for_ids = clone $assignment_submission;
            $submitted_assignment_ids = $assignment_submission_for_ids->pluck('assignment_id');

            $submitted_assignment_data_db = (clone $assignment)->with('submission')->whereIn('id', $submitted_assignment_ids);
            $submitted_assignment_data_with_points = $submitted_assignment_data_db->paginate(10)->toArray();

            $submitted_assingment_data = [];
            foreach ($submitted_assignment_data_with_points['data'] as $submitted_data) {
                $submitted_assingment_data[] = [
                    'assignment_id' => $submitted_data['id'],
                    'assignment_name' => $submitted_data['name'],
                    'obtained_points' => $submitted_data['submission']['points'],
                    'total_points' => $submitted_data['points'],
                ];
            }
            // Use the cloned queries for accurate counts
            $total_assignments = $assignment_count_query->count();
            $total_submitted_assignments = $assignment_submission_count_query->count();
            $total_assingment_unsubmitted = $total_assignments - $total_submitted_assignments;
            $assingment_report = [
                'assignments' => $total_assignments,
                'submitted_assignments' => $total_submitted_assignments,
                'unsubmitted_assignments' => $total_assingment_unsubmitted,
                'total_points' => (string) $total_assignment_submitted_points ?? '0',
                'total_obtained_points' => (string) $total_points_obtained ?? '0',
                'percentage' => $percentage ?? '0',
                'submitted_assignment_with_points_data' => [
                    'current_page' => $submitted_assignment_data_with_points['current_page'],
                    'data' => $submitted_assingment_data,
                    'from' => $submitted_assignment_data_with_points['from'],
                    'last_page' => $submitted_assignment_data_with_points['last_page'],
                    'per_page' => $submitted_assignment_data_with_points['per_page'],
                    'to' => $submitted_assignment_data_with_points['to'],
                    'total' => $submitted_assignment_data_with_points['total'],
                ],
            ];
            ResponseService::successResponse('Assignment Report Fetched Successfully', $assingment_report, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
        // return response()->json($response, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    // public function getAssignmentReport(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'child_id' => 'required|numeric',
    //         'subject_id' => 'required|numeric'
    //     ]);
    //     if ($validator->fails()) {
    //         ResponseService::validationError($validator->errors()->first());
    //     }
    //     try {
    //         $student = Students::where('id', $request->child_id)->first();

    //         $session_year = getSettings('session_year');
    //         $session_year_id = $session_year['session_year'];

    //         // get the assignments ids
    //         $assingment_ids = Assignment::where(['class_section_id' => $student->class_section_id, 'session_year_id' => $session_year_id, 'subject_id' => $request->subject_id])->pluck('id');

    //         //total assignments of class
    //         $total_assignments = Assignment::where(['class_section_id' => $student->class_section_id, 'session_year_id' => $session_year_id, 'subject_id' => $request->subject_id])->count();

    //         //total assignment submiited
    //         $total_submitted_assignments = AssignmentSubmission::where('student_id', $student->id)->whereIn('assignment_id', $assingment_ids)->count();

    //         // submitted assingment id
    //         $submitted_assignment_ids = AssignmentSubmission::where('student_id', $student->id)->whereIn('assignment_id', $assingment_ids)->pluck('assignment_id');

    //         //total assignment unsubmitted
    //         $total_assingment_unsubmitted = Assignment::where(['class_section_id' => $student->class_section_id, 'subject_id' => $request->subject_id])->whereNotIn('id', $submitted_assignment_ids)->count();

    //         //total points of assignment submitted
    //         $total_assignment_submitted_points = Assignment::select(DB::raw("sum(points)"))->where('class_section_id', $student->class_section_id)->whereIn('id', $submitted_assignment_ids)->whereNot('points', null)->first();
    //         $total_assignment_submitted_points = $total_assignment_submitted_points['sum(points)'];

    //         // total obtained assignment points
    //         $assingment_id_with_points = Assignment::where(['class_section_id' => $student->class_section_id, 'subject_id' => $request->subject_id])->whereIn('id', $submitted_assignment_ids)->whereNot('points', null)->pluck('id');
    //         $total_points_obtained = AssignmentSubmission::select(DB::raw("sum(points)"))->whereIn('assignment_id', $assingment_id_with_points)->first();
    //         $total_points_obtained = $total_points_obtained['sum(points)'];

    //         if ($total_points_obtained) {
    //             //percentage
    //             $percentage = number_format(($total_points_obtained * 100) / $total_assignment_submitted_points, 2);
    //         }

    //         $submitted_assignment_data_db = Assignment::with('submission')->where(['class_section_id' => $student->class_section_id, 'subject_id' => $request->subject_id])->whereIn('id', $submitted_assignment_ids)->whereNot('points', null);
    //         $submitted_assignment_data_with_points = $submitted_assignment_data_db->paginate(10)->toArray();

    //         $submitted_assingment_data = array();
    //         foreach ($submitted_assignment_data_with_points['data'] as $submitted_data) {
    //             $submitted_assingment_data[] = array(
    //                 'assignment_id' => $submitted_data['id'],
    //                 'assignment_name' => $submitted_data['name'],
    //                 'obtained_points' => $submitted_data['submission']['points'],
    //                 'total_points' => $submitted_data['points']
    //             );
    //         }
    //         $assingment_report = array(
    //             'assignments' => $total_assignments,
    //             'submitted_assignments' => $total_submitted_assignments,
    //             'unsubmitted_assignments' => $total_assingment_unsubmitted,
    //             'total_points' => $total_assignment_submitted_points ?? "0",
    //             'total_obtained_points' => $total_points_obtained ?? "0",
    //             'percentage' => $percentage ?? "0",
    //             'submitted_assignment_with_points_data' => array(
    //                 'current_page' => $submitted_assignment_data_with_points['current_page'],
    //                 'data' => $submitted_assingment_data,
    //                 'from' => $submitted_assignment_data_with_points['from'],
    //                 'last_page' => $submitted_assignment_data_with_points['last_page'],
    //                 'per_page' => $submitted_assignment_data_with_points['per_page'],
    //                 'to' => $submitted_assignment_data_with_points['to'],
    //                 'total' => $submitted_assignment_data_with_points['total'],
    //             )
    //         );
    //         ResponseService::successResponse("Assignment Report Fetched Successfully", $assingment_report, [], 200);
    //     } catch (Throwable $e) {
    //         ResponseService::errorResponse("error_occurred", null, 103, $e);
    //     }
    //     // return response()->json($response, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    // }

    public function getFeesPaymentTransactions(Request $request)
    {
        try {

            $parent_id = Auth::user()->parent->id;
            $child_id = Students::where('father_id', $parent_id)->orWhere('mother_id', $parent_id)
                ->orWhere('guardian_id', $parent_id)->pluck('id');
            $fees_payment_transactions = PaymentTransaction::whereIn('student_id', $child_id)
                ->with('session_year')->with(['student' => function ($q) {
                    $q->select('id', 'user_id')->with('user:id,first_name,last_name');
                }])->orderBy('id', 'desc')->paginate(15);

            foreach ($fees_payment_transactions as $transaction) {
                $date = $transaction->date;
                if ($date) {
                    $datetime = Carbon::parse($date);
                    $time = $datetime->format('H:i:s');
                    $addHour = $datetime->copy()->addHour();
                    if (Carbon::now()->gt($addHour)) {
                        $transaction->payment_status = 0;
                        $transaction->save();
                    }
                }
            }
            $fees_payment_transactions = $fees_payment_transactions->toArray();
            ResponseService::successResponse('Fees Payment Transactions Fetched Successfully', [
                'current_page' => $fees_payment_transactions['current_page'],
                'transaction-data' => $fees_payment_transactions['data'],
                'from' => $fees_payment_transactions['from'],
                'last_page' => $fees_payment_transactions['last_page'],
                'per_page' => $fees_payment_transactions['per_page'],
                'to' => $fees_payment_transactions['to'],
                'total' => $fees_payment_transactions['total'],
            ]);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getProfileDetails()
    {
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $compulsory_fees_mode = getSettings('compulsory_fee_payment_mode');
            $compulsory_fees_mode = $compulsory_fees_mode['compulsory_fee_payment_mode'] ?? 0;

            $session_year = SessionYear::where('id', $session_year_id)->first();
            $isInstallment = $session_year->include_fee_installments;

            $due_date = $session_year->fee_due_date;
            $free_app_use_date = $session_year->free_app_use_date;

            $current_date = Carbon::now()->toDateString();

            $user = Auth::user();
            $children = Students::where('father_id', $user->parent->id)->orWhere('mother_id', $user->parent->id)->orWhere('guardian_id', $user->parent->id)->with('class_section')
                ->whereHas('studentSessions', function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                })->get();

            $parentDynamicField = null;
            $dynamicField = $user->parent->dynamic_fields;
            $user = flattenMyModel($user);

            $data1 = ! empty($dynamicField) ? json_decode($dynamicField, true) : null;
            if (is_array($data1)) {
                foreach ($data1 as $item) {
                    if (! empty($item)) {
                        foreach ($item as $key => $value) {
                            $parentDynamicFields[$key] = $value;
                        }
                    }
                }
            } else {
                $parentDynamicFields = $data1;
            }

            foreach ($children as $child) {
                $child->first_name = $child->user->first_name;
                $child->last_name = $child->user->last_name;
                $child->image = $child->user->image;
                $child->dob = $child->user->dob;
                $child->current_address = $child->user->current_address;
                $child->permanent_address = $child->user->permanent_address;

                $studentSession = StudentSessions::where('session_year_id', $session_year_id)->where('student_id', $child->id)->first();
                $class_id = $studentSession ? $studentSession->class_section->class_id : $child->class_section->class_id;

                if ($compulsory_fees_mode == 1) {
                    if (isset($free_app_use_date)) {
                        if ($current_date >= $free_app_use_date) {
                            $fees_paid = FeesPaid::where('student_id', $child->id)->where('session_year_id', $session_year_id)->first();
                            if ($isInstallment == 0) {
                                if ($fees_paid) {
                                    if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                        $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                    } else {
                                        $child->is_fee_payment_due = 0;
                                    }
                                } else {
                                    $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            } else {

                                if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                    $child->is_fee_payment_due = 0;
                                } else {
                                    // Installment case
                                    $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                    if ($installment_db->count()) {
                                        $installment_db_data = $installment_db->get();
                                        foreach ($installment_db_data as $data) {
                                            $paid_installment_data = PaidInstallmentFee::where(['student_id' => $child->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                                            $installment_data[] = [
                                                'id' => $data->id,
                                                'name' => $data->name,
                                                'due_date' => date('Y-m-d', strtotime($data->due_date)),
                                                'due_charges' => $data->due_charges,
                                                'is_paid' => $paid_installment_data->status ?? 0,
                                            ];
                                        }
                                    }
                                    // Find the first unpaid installment and set its due date
                                    foreach ($installment_data as $data) {
                                        if ($data['is_paid'] == 0) {
                                            $due_date = $data['due_date'];
                                            break; // Stop after the first unpaid installment
                                        }
                                    }
                                    $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            }
                        } else {
                            $child->is_fee_payment_due = 0;
                        }
                    } else {
                        $fees_paid = FeesPaid::where('student_id', $child->id)->where('session_year_id', $session_year_id)->first();

                        if ($isInstallment == 0) {
                            // Non-installment case
                            if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                            } else {
                                $child->is_fee_payment_due = 0;
                            }
                        } else {

                            if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                $child->is_fee_payment_due = 0;
                            } else {
                                // Installment case
                                $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                if ($installment_db->count()) {
                                    $installment_db_data = $installment_db->get();
                                    foreach ($installment_db_data as $data) {
                                        $paid_installment_data = PaidInstallmentFee::where(['student_id' => $child->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                                        $installment_data[] = [
                                            'id' => $data->id,
                                            'name' => $data->name,
                                            'due_date' => date('Y-m-d', strtotime($data->due_date)),
                                            'due_charges' => $data->due_charges,
                                            'is_paid' => $paid_installment_data->status ?? 0,
                                        ];
                                    }
                                }
                                // Find the first unpaid installment and set its due date
                                foreach ($installment_data as $data) {
                                    if ($data['is_paid'] == 0) {
                                        $due_date = $data['due_date'];
                                        break; // Stop after the first unpaid installment
                                    }
                                }
                                $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                            }
                        }
                    }
                } else {
                    $child->is_fee_payment_due = 0;
                }

                $dynamicFields = null;
                $dynamicField = $child->dynamic_fields;

                $childdata = json_decode($dynamicField, true);

                if (is_array($childdata)) {
                    foreach ($childdata as $item) {
                        foreach ($item as $key => $value) {
                            $dynamicFields[$key] = $value;
                        }
                    }
                } else {
                    $dynamicFields = $childdata;
                }

                $child->dynamic_fields = ! empty($dynamicFields) ? $dynamicFields : null;

                unset($child->user);

                if ($studentSession) {
                    // Set Class Section name
                    $classSectionName = $studentSession->class_section->class->name.' '.$studentSession->class_section->section->name;
                    $child->is_semester_on_in_class = $studentSession->class_section->class->getIncludeSemestersForSession($session_year_id);
                    // Set Stream name
                    $streamName = $studentSession->class_section->class->streams->name ?? null;
                    if ($streamName !== null) {
                        $child->class_section_name = $classSectionName.' '.$streamName;
                    } else {
                        $child->class_section_name = $classSectionName;
                    }

                    // Set Medium name
                    $child->medium_name = $studentSession->class_section->class->medium->name;

                    // Set Shift name
                    $child->shift_id = $studentSession->class_section->class->shifts->id ?? '';
                    $child->shift = Shift::find($child->shift_id);
                    if ($child->shift) {
                        $child->shift->id;
                        $child->shift->title;
                        $child->shift->start_time;
                    }

                    $child->class_id = $studentSession->class_section->class_id;
                    $child->class_section_id = $studentSession->class_section_id;
                } else {
                    // Set Class Section name
                    $classSectionName = $child->class_section->class->name.' '.$child->class_section->section->name;
                    $child->is_semester_on_in_class = $child->class_section->class->getIncludeSemestersForSession($session_year_id);
                    // Set Stream name
                    $streamName = $child->class_section->class->streams->name ?? null;
                    if ($streamName !== null) {
                        $child->class_section_name = $classSectionName.' '.$streamName;
                    } else {
                        $child->class_section_name = $classSectionName;
                    }

                    // Set Medium name
                    $child->medium_name = $child->class_section->class->medium->name;

                    // Set Shift name
                    $child->shift_id = $child->class_section->class->shifts->id ?? '';
                    $child->shift = Shift::find($child->shift_id);
                    if ($child->shift) {
                        $child->shift->id;
                        $child->shift->title;
                        $child->shift->start_time;
                    }

                    $child->class_id = $child->class_section->class_id;
                    $child->class_section_id = $child->class_section->id;
                }

                unset($child->class_section);
                // Set Category name
                $child->category_name = $child->category->name;
                unset($child->category);
            }
            // Ensure proper data types for API response
            $childrenArray = $children->toArray();

            // Convert data types for each child as per API requirements
            foreach ($childrenArray as &$child) {
                // Convert is_new_admission from bool to int
                if (isset($child['is_new_admission'])) {
                    $child['is_new_admission'] = (int) $child['is_new_admission'];
                }

                // Convert height from double to string
                if (isset($child['height'])) {
                    $child['height'] = (string) $child['height'];
                }

                // Convert weight from double to string
                if (isset($child['weight'])) {
                    $child['weight'] = (string) $child['weight'];
                }
            }

            $data = array_merge($user, ['dynamic_fields' => $parentDynamicFields ?? null, 'children' => $childrenArray]);
            ResponseService::successResponse('Data Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Make Transaction Fail API
    public function failPaymentTransactionStatus(Request $request)
    {
        try {
            $update_status = PaymentTransaction::findOrFail($request->payment_transaction_id);
            $total_amount = $update_status->total_amount;
            $update_status->payment_status = 0;
            $update_status->save();

            $parent_id = Auth::user()->parent->id;
            $user = Parents::where('id', $parent_id)->pluck('user_id');
            $body = 'Amount :- '.$total_amount;
            $type = 'online';
            $image = null;
            $userinfo = null;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $notification = new Notification;
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 2;
            $notification->title = 'Payment Failed';
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

            sendSimpleNotification($user, 'Payment Failed', $body, $type, $image, $userinfo);
            ResponseService::successResponse('Data Updated Successfully', null, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getNotifications()
    {
        try {
            $user = Auth::user()->id;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $notification_id = UserNotification::where('user_id', $user)->pluck('notification_id');
            // Send To All Users(1) and Parents(4)
            $notification = Notification::where('session_year_id', $session_year_id)
                ->where(function ($query) use ($notification_id) {
                    $query->whereIn('id', $notification_id)->orWhereIn('send_to', [1, 4]);
                })->latest()->paginate();

            ResponseService::successResponse('Data Fetched Successfully', $notification ?? '', [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getPaymentStatus(Request $request)
    {
        ResponseService::logCurlRequest();
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $settings = getSettings();

            if (isset($settings['razorpay_status']) && $settings['razorpay_status']) {
                $secretkey = $settings['razorpay_secret_key'] ?? '';
            }

            if (isset($settings['stripe_status']) && $settings['stripe_status']) {
                $secretkey = $settings['stripe_secret_key'] ?? '';
            }

            $url = 'https://api.stripe.com/v1/payment_intents/'.$request->payment_intent_id;

            $payment_status = Http::withHeaders([
                'Authorization' => 'Bearer '.$secretkey,
            ])->get($url);

            $data = $payment_status['status'];
            ResponseService::successResponse('Data Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getChatUserList(Request $request)
    {
        try {
            $offset = $request->offset;
            $limit = $request->limit;
            $search = $request->search;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $user = $request->user();
            $parent = $user->parent;

            // Get current semester (once, shared across all children)
            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $childrens = $parent->children()
                ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                })->get();

            $class_section_ids = [];
            $subject_id = [];

            // Track which class_section_ids require semester filtering and which don't
            $semesterClassSectionIds = []; // class_section_id => semester_id
            $nonSemesterClassSectionIds = []; // plain array

            foreach ($childrens as $children) {
                $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                    ->where('student_id', $children->id)->first();

                $sectionId = $studentSession
                    ? $studentSession->class_section_id
                    : $children->class_section_id;

                $class_section_ids[] = $sectionId;

                // --- Semester check ---
                // Resolve class_id from ClassSection
                $classSection = ClassSection::find($sectionId);
                $class_id = $classSection ? $classSection->class_id : null;

                $isClassHaveSemester = $class_id
                    ? ClassSessionConfig::classHasSemester($class_id, $session_year_id)
                    : false;

                if ($isClassHaveSemester && $currentSemester) {
                    $semesterClassSectionIds[$sectionId] = $currentSemester->id;
                } else {
                    $nonSemesterClassSectionIds[] = $sectionId;
                }
                // --- End semester check ---

                $student_subject = $children->subjects();

                $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
                $elective_subjects = $student_subject['elective_subject'] ?? [];
                if ($elective_subjects) {
                    $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                }
                $subject_id = array_merge($subject_id, $core_subjects, $elective_subjects);
            }

            $class_section_ids = array_unique(array_filter($class_section_ids));
            $subject_id = array_unique($subject_id);

            // --- Build class teacher IDs with semester awareness ---
            $class_teachers_id = [];

            // Semester-filtered class sections
            foreach ($semesterClassSectionIds as $csi => $semId) {
                $ids = ClassTeacher::where('class_section_id', $csi)
                    ->where('session_year_id', $session_year_id)
                    ->where('semester_id', $semId)
                    ->pluck('class_teacher_id')->toArray();
                $class_teachers_id = array_merge($class_teachers_id, $ids);
            }

            // Non-semester class sections (original behaviour)
            if (! empty($nonSemesterClassSectionIds)) {
                $ids = ClassTeacher::whereIn('class_section_id', $nonSemesterClassSectionIds)
                    ->where('session_year_id', $session_year_id)
                    ->pluck('class_teacher_id')->toArray();
                $class_teachers_id = array_merge($class_teachers_id, $ids);
            }

            // --- Build subject teacher IDs with semester awareness ---
            $subject_teacher_id = [];

            // Semester-filtered
            foreach ($semesterClassSectionIds as $csi => $semId) {
                $ids = SubjectTeacher::where('class_section_id', $csi)
                    ->where('session_year_id', $session_year_id)
                    ->whereIn('subject_id', $subject_id)
                    ->where('semester_id', $semId)
                    ->pluck('teacher_id')->toArray();
                $subject_teacher_id = array_merge($subject_teacher_id, $ids);
            }

            // Non-semester (original behaviour)
            if (! empty($nonSemesterClassSectionIds)) {
                $ids = SubjectTeacher::whereIn('class_section_id', $nonSemesterClassSectionIds)
                    ->where('session_year_id', $session_year_id)
                    ->whereIn('subject_id', $subject_id)
                    ->pluck('teacher_id')->toArray();
                $subject_teacher_id = array_merge($subject_teacher_id, $ids);
            }

            $teacher_ids = array_unique(array_merge($class_teachers_id, $subject_teacher_id));

            // --- Teacher query (unchanged) ---
            if ($search) {
                $teachers = Teacher::whereIn('id', $teacher_ids)
                    ->where(function ($query) use ($search) {
                        $query->whereHas('user', function ($subquery) use ($search) {
                            $subquery->where('first_name', 'like', "%$search%")
                                ->orWhere('last_name', 'like', "%$search%");
                        });
                    })
                    ->with('user:id,first_name,last_name,image,mobile,email', 'subjects.subject')
                    ->offset($offset)->limit($limit)->get();
            } else {
                $teachers = Teacher::whereIn('id', $teacher_ids)
                    ->with('user:id,first_name,last_name,image,mobile,email', 'subjects.subject')
                    ->offset($offset)->limit($limit)->get();
            }

            $data = [];

            foreach ($teachers as $teacher) {
                $unreadCount = 0;
                $subjectData = [];

                // --- Semester-aware class teacher section lookup ---
                $class_teacher_section_ids = [];

                foreach ($semesterClassSectionIds as $csi => $semId) {
                    $ids = ClassTeacher::where('class_teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->where('semester_id', $semId)
                        ->where('class_section_id', $csi)
                        ->pluck('class_section_id')->toArray();
                    $class_teacher_section_ids = array_merge($class_teacher_section_ids, $ids);
                }

                if (! empty($nonSemesterClassSectionIds)) {
                    $ids = ClassTeacher::where('class_teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->whereIn('class_section_id', $nonSemesterClassSectionIds)
                        ->pluck('class_section_id')->toArray();
                    $class_teacher_section_ids = array_merge($class_teacher_section_ids, $ids);
                }

                // --- Semester-aware subject teacher section lookup ---
                $subject_teacher_class_section_ids = [];

                foreach ($semesterClassSectionIds as $csi => $semId) {
                    $ids = SubjectTeacher::where('teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->where('semester_id', $semId)
                        ->where('class_section_id', $csi)
                        ->pluck('class_section_id')->toArray();
                    $subject_teacher_class_section_ids = array_merge($subject_teacher_class_section_ids, $ids);
                }

                if (! empty($nonSemesterClassSectionIds)) {
                    $ids = SubjectTeacher::where('teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->whereIn('class_section_id', $nonSemesterClassSectionIds)
                        ->pluck('class_section_id')->toArray();
                    $subject_teacher_class_section_ids = array_merge($subject_teacher_class_section_ids, $ids);
                }

                $teacher_class_section_ids = array_unique(
                    array_merge($class_teacher_section_ids, $subject_teacher_class_section_ids)
                );

                $classes = ClassSection::whereIn('id', $class_teacher_section_ids)
                    ->with('class.medium', 'section', 'class.streams', 'class.shifts')->get();

                $studentsArray = [];
                foreach ($childrens as $child) {
                    $students = Students::with('user')
                        ->where('id', $child->id)
                        ->whereIn('class_section_id', $teacher_class_section_ids)
                        ->get();

                    foreach ($students as $student) {
                        $studentsArray[] = $student->user->first_name.' '.$student->user->last_name;
                    }
                }

                $class_names = [];
                foreach ($classes as $class) {
                    if ($class) {
                        $class_names[] = ($class->class->name ?? '').' '
                            .($class->section->name ?? '').' '
                            .($class->class->medium->name ?? '');
                    }
                }

                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = [
                        'id' => $subject->subject->id ?? '',
                        'name' => $subject->subject->name ?? '',
                    ];
                }

                $lastMessage = ChatMessage::where(function ($query) use ($user, $teacher) {
                    $query->where('modal_id', $teacher->user->id)
                        ->where('sender_id', $user->id);
                })
                    ->orWhere(function ($query) use ($user, $teacher) {
                        $query->where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id);
                    })
                    ->select('id', 'body', 'date')
                    ->latest()
                    ->first();

                $lastReadMessage = ReadMessage::where('modal_id', $user->id)
                    ->where('user_id', $teacher->user->id)->first();

                if ($lastReadMessage) {
                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                    if (! empty($lastReadMessageId)) {
                        $unreadCount = ChatMessage::where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id)
                            ->where('id', '>', $lastReadMessageId)->count();
                    } else {
                        $unreadCount = ChatMessage::where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id)->count();
                    }
                }

                $data[] = [
                    'id' => $teacher->id,
                    'user_id' => $teacher->user->id,
                    'first_name' => $teacher->user->first_name,
                    'last_name' => $teacher->user->last_name,
                    'qualification' => $teacher->qualification,
                    'email' => $teacher->user->email,
                    'image' => $teacher->user->image,
                    'class_teacher' => $class_names,
                    'dob' => $teacher->user->dob,
                    'mobile_no' => $teacher->user->mobile,
                    'subjects' => $subjectData,
                    'student_name' => $studentsArray ?? [],
                    'last_message' => $lastMessage ?? null,
                    'unread_message' => $unreadCount ?? 0,
                ];
            }

            $total_items = count($data);

            $unreadusers = array_filter($data, fn ($t) => $t['unread_message'] > 0);
            $totalunreadusers = count($unreadusers);

            $data = collect($data)->sortByDesc(function ($user) {
                return optional($user['last_message'])->date ?? 0;
            })->values();

            ResponseService::successResponse('Data Fetched Successfully', [
                'items' => $data,
                'total_items' => $total_items,
                'total_unread_users' => $totalunreadusers,
            ], [], 200);
        } catch (Throwable $e) {
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

            $parent = Parents::with('user')->where('user_id', $sender_id)->first();

            $lastReadMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $parent->user_id)->first();

            if ($lastReadMessage) {

                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                if (! empty($lastReadMessageId)) {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $parent->user_id)->where('id', '>', $lastReadMessageId)->count();
                } else {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $parent->user_id)->count();
                }
            }

            $children = $parent->children()->with('user', 'class_section')->get();

            foreach ($children as $child) {
                $child_subject = $child->subjects();

                $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

                $elective_subjects = $child_subject['elective_subject'] ?? [];

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

                $childArray[] = [
                    'id' => $child->id,
                    'user_id' => $child->user_id,
                    'child_name' => $child->user->first_name.' '.$child->user->last_name,
                    'class_name' => $child->class_section->class->name.' '.$child->class_section->section->name.' '.$child->class_section->class->medium->name,
                    'admission_no' => $child->admission_no,
                    'image' => $child->user->image,
                    'subject' => $subjectArray ?? [],
                ];
            }

            $userinfo = [
                'id' => $parent->id,
                'user_id' => $parent->user_id, // Assuming this is the correct property name
                'first_name' => $parent->user->first_name,
                'last_name' => $parent->user->last_name,
                'email' => $parent->user->email,
                'mobile_no' => $parent->user->mobile,
                'occupation' => $parent->occupation,
                'image' => $parent->user->image,
                'last_message' => $data ?? null,
                'children' => $childArray ?? [],
                'isParent' => 1,
                'unread_message' => $unreadCount ?? 0,
            ];

            $title = $parent->user->first_name.' '.$parent->user->last_name;
            $body = $request->message ?? $count.' Files Received';
            $type = 'chat';
            $image = null;
            $user[] = $receiver_id;

            $userinfo = (object) $userinfo;
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            ResponseService::successResponse('Message Sent Successfully', $data, [], 200);
        } catch (Throwable $e) {
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
                if (isset($message['file'])) {
                    $message['files'] = collect($message['file'])->map(function ($file) {
                        return asset('storage/'.$file['file_name']);
                    })->toArray();

                    unset($message['file']);
                } else {
                    $message['files'] = []; // or handle the case where 'file' is not set
                }
            }
            ResponseService::successResponse('Data Fetched Successfully', [
                'items' => $messages ?? [],
                'total_items' => $total_items,
            ], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function readAllMessages(Request $request)
    {
        try {
            $user = Auth::id();
            $teacher = $request->user_id;

            $lastMessage = ChatMessage::where('sender_id', $teacher)->where('modal_id', $user)->latest()->first();
            if ($lastMessage) {
                $message_id = $lastMessage->id;
            }

            // Update Read Message id
            $readMessage = ReadMessage::where('modal_id', $user)->where('user_id', $teacher)->first();

            if ($readMessage) {
                $readMessage->last_read_message_id = $message_id;
                $readMessage->save();
            }
            ResponseService::successResponse('Message Read');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function applyLeave(Request $request)
    {
        $allowed = ['child_id', 'reason', 'leave_details', 'files'];

        // reject any extra keys
        $unknown = array_diff(array_keys($request->all()), $allowed);

        if (! empty($unknown)) {
            ResponseService::validationError('Unknown fields: '.implode(', ', $unknown));
        }

        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
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
            $parentUserId = Auth::id();

            // Fetch student ONLY if it belongs to logged-in parent
            $student = Students::where('id', $request->child_id)
                ->where(function ($query) use ($parentUserId) {

                    $query->whereHas('father', function ($q) use ($parentUserId) {
                        $q->where('user_id', $parentUserId);
                    })
                        ->orWhereHas('mother', function ($q) use ($parentUserId) {
                            $q->where('user_id', $parentUserId);
                        })
                        ->orWhereHas('guardian', function ($q) use ($parentUserId) {
                            $q->where('user_id', $parentUserId);
                        });
                })
                ->with('user')
                ->first();

            if (! $student) {
                return ResponseService::validationError('Child not found or unauthorized.');
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $student_class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $teacher_ids = ClassTeacher::where('class_section_id', $student_class_section_id)->pluck('class_teacher_id');
            $user = Teacher::whereIn('id', $teacher_ids)->pluck('user_id')->toArray();

            $sessionYear = SessionYear::where('id', $session_year_id)->first();

            $public_holiday = Holiday::whereDate('date', '>=', $sessionYear->start_date)->whereDate('date', '<=', $sessionYear->end_date)->get()->pluck('date')->toArray();

            $dates = array_column($request->leave_details, 'date');
            $from_date = min($dates);
            $to_date = max($dates);

            $data = [
                'user_id' => $student->user_id,
                'reason' => $request->reason,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'leave_master_id' => null,
                'status' => '0',
                'session_year_id' => $session_year_id,
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

            $title = 'Leave Alert';

            $type = 'leave';
            $image = null;
            $userinfo = null;

            $body = $student->user->first_name.' '.$student->user->last_name.' '.'has added a new leave request';

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
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            ResponseService::successResponse('Data Stored Successfully', $leave ?? '', [], 200);
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getMyLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'status' => 'in:0,1,2',
        ]);
        try {

            $setting = getSettings();
            $session_year_id = $setting['session_year'];
            $student = Students::where('id', $request->child_id)->first();

            $sql = Leave::with('leave_detail', 'file')->where('user_id', $student->user_id)->where('session_year_id', $session_year_id)->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }])->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }]);

            if (isset($request->status)) {
                $sql->where('status', $request->status);
            }

            if ($request->month) {
                $sql->whereHas('leave_detail', function ($q) use ($request) {
                    $q->whereMonth('date', $request->month);
                });
            }

            $sql->orderBy('id', 'DESC');
            $sql = $sql->get();

            $sql = $sql->map(function ($sql) {
                $total_leaves = ($sql->half_leave / 2) + $sql->full_leave;
                $sql->days = $total_leaves;

                return $sql;
            });

            $data = [
                'taken_leaves' => $sql->where('status', 1)->sum('days'),
                'leave_details' => $sql,
            ];
            ResponseService::successResponse('Data Fetched Successfully', $data, [], 200);
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

            $parentUserId = Auth::id();

            $leave = Leave::where('id', $request->leave_id)
                ->whereHas('user.student', function ($studentQuery) use ($parentUserId) {

                    $studentQuery->where(function ($q) use ($parentUserId) {

                        $q->whereHas('father', function ($fatherQuery) use ($parentUserId) {
                            $fatherQuery->where('user_id', $parentUserId);
                        })
                            ->orWhereHas('mother', function ($motherQuery) use ($parentUserId) {
                                $motherQuery->where('user_id', $parentUserId);
                            })
                            ->orWhereHas('guardian', function ($guardianQuery) use ($parentUserId) {
                                $guardianQuery->where('user_id', $parentUserId);
                            });
                    });
                })
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
}
