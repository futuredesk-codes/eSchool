<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Imports\StudentsImport;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\FormField;
use App\Models\Grade;
use App\Models\OnlineExamStudentAnswer;
use App\Models\Parents;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\StudentOnlineExamStatus;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\MailService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Throwable;

class StudentController extends Controller
{
    public function index()
    {
        if (! Auth::user()->can('student-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $is_restricted_teacher = Auth::user()->hasRole('Teacher') && ! Auth::user()->can('student-create');

        // For restricted teachers, class sections will be loaded dynamically via AJAX based on session year
        if ($is_restricted_teacher) {
            $class_section = collect();
        } else {
            $class_section = ClassSection::with('class', 'section', 'class.medium', 'class.streams')->get();
        }

        $category = Category::where('status', 1)->get();
        $formFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return view('students.details', compact('class_section', 'category', 'formFields', 'session_years', 'is_restricted_teacher'));
    }

    public function create()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $class_section = ClassSection::with('class', 'section', 'streams')
            ->get();

        // $class_section = collect(); // default empty

        // // condition for teacher
        // if ($teacher = Auth::user()->teacher) {
        //     $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher->id)->pluck('class_section_id');
        //     $class_section = ClassSection::with('class', 'section', 'streams')
        //         ->whereIn('id', $class_section_ids)->get();
        // } else {
        //     // condition for super admin
        //     $class_section = ClassSection::with('class', 'section', 'streams')
        //         ->get();
        // }

        $studentFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
        $parentFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
        $category = Category::where('status', 1)->get();
        $data = getSettings('session_year');
        $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
        $get_student = Students::withTrashed()->select('id')->latest('id')->pluck('id')->first();
        $admission_no = $session_year.($get_student + 1);
        $admission_date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        return view('students.index', compact('class_section', 'category', 'admission_no', 'studentFields', 'parentFields', 'admission_date'));
    }

    public function createBulkData()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::with('class', 'section')->get();
        // $category = Category::where('status', 1)->get();
        // $data = getSettings('session_year');
        // $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
        // $get_student = Students::select('id')->latest('id')->pluck('id')->first();
        // $admission_no = $session_year . ($get_student + 1);

        return view('students.add_bulk_data', compact('class_section'));
    }

    public function storeBulkData(Request $request)
    {
        if (! Auth::user()->can('student-create') || ! Auth::user()->can('student-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'file' => 'required|mimes:csv,txt,xlsx,xls',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $class_section_id = $request->class_section_id;
            Excel::import(new StudentsImport($class_section_id), $request->file);
            ResponseService::successResponse(trans('data_store_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors from the import
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            ResponseService::errorResponse($firstError, null, 422);
        } catch (Throwable $e) {
            $message = 'Oops! Something went wrong.';
            $exceptionMessage = strtolower((string) $e->getMessage());
            if (str_contains($exceptionMessage, 'file') && (str_contains($exceptionMessage, 'xlsx') || str_contains($exceptionMessage, 'csv') || str_contains($exceptionMessage, 'xls'))) {
                $message = trans('please_select_valid_file');
            }
            ResponseService::errorResponse($message, null, 103, $e);
        }
    }

    public function update(Request $request)
    {
        if (! Auth::user()->can('student-create') || ! Auth::user()->can('student-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $request->validate(
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'mobile' => 'nullable|numeric|regex:/^[0-9]{7,16}$/',
                'image' => 'mimes:jpeg,png,jpg|image|max:2048',
                'dob' => 'required',
                'class_section_id' => 'required',
                'category_id' => 'required',
                'admission_no' => 'required|unique:students,admission_no,'.$request->edit_id.',user_id',
                'roll_number' => 'required',
                'admission_date' => 'required|date_format:d-m-Y',
                'current_address' => 'required',
                'permanent_address' => 'required',
                'height' => 'required|numeric|min:0',
                'weight' => 'required|numeric|min:0',
                'edit_parent_guardian_type' => 'required|in:parent,guardian',
            ],
            [
                'mobile.regex' => __('The mobile number must be a length of 7 to 15 digits.'),
                'height.required' => 'Height is required.',
                'height.numeric' => 'Height must be a number.',
                'height.min' => 'Height must be greater than 0.',
                'weight.required' => 'Weight is required.',
                'weight.numeric' => 'Weight must be a number.',
                'weight.min' => 'Weight must be greater than 0.',
            ]
        );
        try {
            // Add Father in User and Parent table data
            if ($request->edit_parent_guardian_type == 'parent') {
                if (! intval($request->father_email)) {
                    $request->validate([
                        'father_email' => 'required|email|unique:users,email,'.$request->father_email.'|unique:parents,email,'.$request->father_email,
                        'father_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                if (! intval($request->mother_email)) {
                    $request->validate([
                        'mother_email' => 'required|email|unique:users,email,'.$request->mother_email.'|unique:parents,email,'.$request->mother_email,
                        'mother_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }
                if (! intval($request->father_email)) {
                    $father_user = new User;
                    $father_user->image = $request->file('father_image')->store('parents', 'public');
                    $father_user->password = Hash::make(str_replace('/', '', $request->father_dob));
                    $father_user->first_name = $request->father_first_name;
                    $father_user->last_name = $request->father_last_name;
                    $father_user->email = $request->father_email;
                    $father_user->mobile = $request->father_mobile;
                    $father_user->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_user->gender = 'Male';
                    $father_user->save();

                    $father_parent = new Parents;
                    $father_parent->user_id = $father_user->id;
                    $father_parent->first_name = $request->father_first_name;
                    $father_parent->last_name = $request->father_last_name;
                    $father_parent->image = $father_user->getRawOriginal('image');
                    $father_parent->occupation = $request->father_occupation;
                    $father_parent->mobile = $request->father_mobile;
                    $father_parent->email = $request->father_email;
                    $father_parent->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_parent->gender = 'Male';
                    $father_parent->dynamic_fields = '';
                    $father_parent->save();
                    $father_parent_id = $father_parent->id;
                } else {
                    $father_parent_id = $request->father_email;
                }

                // Add Mother in User and Parent table data
                if (! intval($request->mother_email)) {
                    $mother_user = new User;
                    $mother_user->image = $request->file('mother_image')->store('parents', 'public');
                    $mother_user->password = Hash::make(str_replace('/', '', $request->mother_dob));
                    $mother_user->first_name = $request->mother_first_name;
                    $mother_user->last_name = $request->mother_last_name;
                    $mother_user->email = $request->mother_email;
                    $mother_user->mobile = $request->mother_mobile;
                    $mother_user->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_user->gender = 'Female';
                    $mother_user->save();

                    $mother_parent = new Parents;
                    $mother_parent->user_id = 0;
                    $mother_parent->first_name = $request->mother_first_name;
                    $mother_parent->last_name = $request->mother_last_name;
                    $mother_parent->image = $mother_user->getRawOriginal('image');
                    $mother_parent->occupation = $request->mother_occupation;
                    $mother_parent->mobile = $request->mother_mobile;
                    $mother_parent->email = $request->mother_email;
                    $mother_parent->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_parent->gender = 'Female';
                    $mother_parent->dynamic_fields = '';
                    $mother_parent->save();
                    $mother_parent_id = $mother_parent->id;
                } else {
                    $mother_parent_id = $request->mother_email;
                }
            } elseif ($request->edit_parent_guardian_type == 'guardian') {
                if (isset($request->guardian_email) && ! intval($request->guardian_email)) {
                    $request->validate([
                        'guardian_email' => 'required|email|unique:parents,email,'.$request->guardian_email,
                        'guardian_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }
                if (isset($request->guardian_email)) {
                    if (! intval($request->guardian_email)) {
                        $guardian_email = $request->guardian_email;
                        $guardian_user = new User;

                        $guardian_image = $request->file('guardian_image');
                        // made file name with combination of current time
                        $file_name = time().'-'.$guardian_image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'parents/'.$file_name;
                        // resized image
                        resizeImage($guardian_image);
                        // stored image to storage/public/parents folder
                        $destinationPath = storage_path('app/public/parents');
                        $guardian_image->move($destinationPath, $file_name);

                        $guardian_user->image = $file_path;
                        $guardian_user->password = Hash::make(str_replace('/', '', $request->guardian_dob));
                        $guardian_user->first_name = $request->guardian_first_name;
                        $guardian_user->last_name = $request->guardian_last_name;
                        $guardian_user->email = $guardian_email;
                        $guardian_user->mobile = $request->guardian_mobile;
                        $guardian_user->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_user->gender = $request->guardian_gender;
                        $guardian_user->save();

                        $guardian_parent = new Parents;
                        $guardian_parent->user_id = $guardian_user->id;
                        $guardian_parent->first_name = $request->guardian_first_name;
                        $guardian_parent->last_name = $request->guardian_last_name;
                        $guardian_parent->image = $guardian_user->getRawOriginal('image');
                        $guardian_parent->occupation = $request->guardian_occupation;
                        $guardian_parent->mobile = $request->guardian_mobile;
                        $guardian_parent->email = $request->guardian_email;
                        $guardian_parent->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_parent->gender = $request->guardian_gender;
                        $guardian_parent->dynamic_fields = '';
                        $guardian_parent->save();
                        $guardian_parent_id = $guardian_parent->id;
                    } else {
                        $guardian_parent_id = $request->guardian_email;
                    }
                } else {
                    $guardian_parent_id = 0;
                }
            }

            // Create Student User First
            $user = User::find($request->edit_id);
            // $user->password = Hash::make(str_replace('/', '', $request->dob));
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            // $user->email = (isset($request->email)) ? $request->email : "";
            // $user->email = $request->admission_no;
            $user->mobile = (isset($request->mobile)) ? $request->mobile : '';
            $user->dob = date('Y-m-d', strtotime($request->dob ?? '2000-01-01'));
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->gender = $request->gender;

            // If Image exists then upload new image and delete the old image
            if ($request->hasFile('image')) {
                if (Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }

                $student_image = $request->file('image');
                // made file name with combination of current time
                $file_name = time().'-'.$student_image->getClientOriginalName();
                // made file path to store in database
                $file_path = 'students/'.$file_name;
                // resized image
                resizeImage($student_image);
                // stored image to storage/public/students folder
                $destinationPath = storage_path('app/public/students');
                $student_image->move($destinationPath, $file_name);
                $user->image = $file_path;
            }
            $user->save();

            $student = Students::where('user_id', $user->id)->firstOrFail();
            // Student dynamic fields
            $formFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
            $data = [];
            $status = 0;
            $dynamic_data = json_decode($student->dynamic_fields ?? '[]', true);

            foreach ($formFields as $form_field) {
                // INPUT TYPE CHECKBOX
                if ($form_field->type == 'checkbox') {
                    if ($status == 0) {
                        $data[] = $request->input('checkbox', []);
                        $status = 1;
                    }
                } elseif ($form_field->type == 'file') {
                    // INPUT TYPE FILE
                    $get_file = '';
                    $field = str_replace(' ', '_', $form_field->name);
                    if (! is_null($dynamic_data)) {
                        foreach ($dynamic_data as $field_data) {
                            if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                $get_file = $field_data[$field];
                            }
                        }
                    }
                    $hidden_file_name = $field;

                    if ($request->hasFile($field)) {
                        if ($get_file) {
                            Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                        }
                        $data[] = [
                            str_replace(' ', '_', $form_field->name) => $request->file($field)->store('students', 'public'),
                        ];
                    } else {
                        if ($request->$hidden_file_name) {
                            $data[] = [
                                str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name,
                            ];
                        }
                    }
                } else {
                    $field = str_replace(' ', '_', $form_field->name);
                    $data[] = [
                        str_replace(' ', '_', $form_field->name) => $request->$field,
                    ];
                }
            }
            $status = 0;
            // End student dynamic field
            $student->class_section_id = $request->class_section_id;
            $student->category_id = $request->category_id;

            $student->roll_number = $request->roll_number;
            $student->caste = $request->caste;
            $student->religion = $request->religion;
            $student->admission_date = date('Y-m-d', strtotime($request->admission_date ?? '2000-01-01'));
            $student->blood_group = $request->blood_group;
            $student->height = $request->height;
            $student->weight = $request->weight;
            $student->father_id = $father_parent_id ?? 0;
            $student->mother_id = $mother_parent_id ?? 0;
            $student->guardian_id = $guardian_parent_id ?? 0;
            $student->dynamic_fields = json_encode($data);
            $student->update();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Exception $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e,
            ];
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        // if (!Auth::user()->can('student-create') || !Auth::user()->can('student-edit')) {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $request->validate(
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'mobile' => 'nullable|numeric|regex:/^[0-9]{7,16}$/',
                'image' => 'mimes:jpeg,png,jpg|image|max:2048',
                'dob' => 'required',
                'class_section_id' => 'required',
                'category_id' => 'required',
                // admission_no is generated server-side, not from user input
                'admission_date' => 'required|date_format:d-m-Y',
                'current_address' => 'required',
                'permanent_address' => 'required',
                'height' => 'required|numeric|min:0',
                'weight' => 'required|numeric|min:0',
                'parent_guardian_type' => 'required|in:parent,guardian',
            ],
            [
                'mobile.regex' => __('The mobile number must be a length of 7 to 15 digits.'),
                'height.required' => 'Height is required.',
                'height.numeric' => 'Height must be a number.',
                'height.min' => 'Height must be greater than 0.',
                'weight.required' => 'Weight is required.',
                'weight.numeric' => 'Weight must be a number.',
                'weight.min' => 'Weight must be greater than 0.',
                'guardian_mobile.regex' => 'The guardian mobile number must be a length of 7 to 15 digits.',
            ]
        );

        $response = [];
        try {
            DB::beginTransaction();

            // Generate admission_no server-side to prevent tampering
            $data = getSettings('session_year');
            $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
            $get_student = Students::withTrashed()->select('id')->latest('id')->pluck('id')->first();
            $admission_no = $session_year.($get_student + 1);

            $parentRole = Role::where('name', 'Parent')->first();
            $studentRole = Role::where('name', 'Student')->first();
            $father_parent_id = null;
            $mother_parent_id = null;
            $guardian_parent_id = null;
            // Add Father in User and Parent table data
            if ($request->parent_guardian_type == 'parent') {
                if (! intval($request->father_email)) {
                    $request->validate([
                        'father_email' => [
                            'required',
                            'email',
                            'unique:users,email',
                            'unique:parents,email',
                            'different:mother_email',
                        ],
                        'father_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                if (! intval($request->mother_email)) {
                    $request->validate([
                        'mother_email' => [
                            'required',
                            'email',
                            'unique:users,email',
                            'unique:parents,email',
                            'different:father_email',
                        ],
                        'mother_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                $request->validate([
                    'father_email' => 'different:mother_email',
                    'mother_email' => 'different:father_email',
                ], [
                    'father_email.different' => 'Father and Mother email cannot be the same.',
                    'mother_email.different' => 'Father and Mother email cannot be the same.',
                ]);

                $father_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->father_dob ?? '1990-01-01')));

                if (! intval($request->father_email)) {
                    $father_email = $request->father_email;
                    $father_user = new User;

                    $father_image = $request->file('father_image');
                    // made file name with combination of current time
                    $file_name = time().'-'.$father_image->getClientOriginalName();
                    // made file path to store in database
                    $file_path = 'parents/'.$file_name;
                    // resized image
                    resizeImage($father_image);
                    // stored image to storage/public/parents folder
                    $destinationPath = storage_path('app/public/parents');
                    $father_image->move($destinationPath, $file_name);

                    $father_user->image = $file_path;
                    $father_user->password = Hash::make($father_plaintext_password);
                    $father_user->first_name = $request->father_first_name;
                    $father_user->last_name = $request->father_last_name;
                    $father_user->email = $father_email;
                    $father_user->mobile = $request->father_mobile;
                    $father_user->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_user->gender = 'Male';
                    $father_user->save();
                    $father_user->assignRole($parentRole);

                    $father_parent = new Parents;
                    // Parent Dynamic FormField
                    $fatherFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
                    $data = [];
                    $status = 0;
                    $dynamic_data = json_decode($father_parent->dynamic_fields ?? '[]', true);
                    // dd($dynamic_data);

                    foreach ($fatherFields as $form_field) {

                        // INPUT TYPE CHECKBOX
                        if ($form_field->type == 'checkbox') {
                            if ($status == 0) {
                                $data[] = $request->input('father_checkbox', []);
                                $status = 1;
                            }
                        } elseif ($form_field->type == 'file') {
                            // INPUT TYPE FILE
                            $get_file = '';
                            $field = 'father_'.str_replace(' ', '_', $form_field->name);
                            if ($dynamic_data && count($dynamic_data) > 0) {
                                foreach ($dynamic_data as $field_data) {
                                    if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                        $get_file = $field_data[$field];
                                    }
                                }
                            }
                            $hidden_file_name = 'file-'.$field;

                            if ($request->hasFile($field)) {
                                if ($get_file) {
                                    Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                                }
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('parent', 'public')];
                            } else {
                                if ($request->$hidden_file_name) {
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                }
                            }
                        } else {
                            $field = 'father_'.str_replace(' ', '_', $form_field->name);
                            $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                        }
                    }
                    // End Parent Dynamic FormField
                    $father_parent->user_id = $father_user->id;
                    $father_parent->first_name = $request->father_first_name;
                    $father_parent->last_name = $request->father_last_name;
                    $father_parent->image = $father_user->getRawOriginal('image');
                    $father_parent->occupation = $request->father_occupation;
                    $father_parent->mobile = $request->father_mobile;
                    $father_parent->email = $request->father_email;
                    $father_parent->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_parent->gender = 'Male';
                    $father_parent->dynamic_fields = json_encode($data);
                    $father_parent->save();
                    $father_parent_id = $father_parent->id;
                    $father_email = $request->father_email;
                    $father_name = $request->father_first_name.' '.$request->father_last_name;
                } else {
                    $father_parent_id = $request->father_email;
                    $father_email = Parents::where('id', $request->father_email)->pluck('email')->first();
                    $fatherData = Parents::where('id', $request->father_email)->select('first_name', 'last_name')->first();
                    $father_name = $fatherData->full_name;
                }

                // Add Mother in User and Parent table data
                $mother_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->mother_dob)));
                if (! intval($request->mother_email)) {
                    $mother_email = $request->mother_email;
                    $mother_user = new User;

                    $mother_image = $request->file('mother_image');
                    // made file name with combination of current time
                    $file_name = time().'-'.$mother_image->getClientOriginalName();
                    // made file path to store in database
                    $file_path = 'parents/'.$file_name;
                    // resized image
                    resizeImage($mother_image);
                    // stored image to storage/public/parents folder
                    $destinationPath = storage_path('app/public/parents');
                    $mother_image->move($destinationPath, $file_name);

                    $mother_user->image = $file_path;
                    $mother_user->password = Hash::make($mother_plaintext_password);
                    $mother_user->first_name = $request->mother_first_name;
                    $mother_user->last_name = $request->mother_last_name;
                    $mother_user->email = $mother_email;
                    $mother_user->mobile = $request->mother_mobile;
                    $mother_user->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_user->gender = 'Female';
                    $mother_user->save();
                    $mother_user->assignRole($parentRole);

                    $mother_parent = new Parents;

                    // Parent Dynamic FormField
                    $motherFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
                    $data = [];
                    $status = 0;
                    $dynamic_data = json_decode($mother_parent->dynamic_fields ?? '[]', true);

                    foreach ($motherFields as $form_field) {

                        // INPUT TYPE CHECKBOX
                        if ($form_field->type == 'checkbox') {
                            if ($status == 0) {
                                $data[] = $request->input('mother_checkbox', []);
                                $status = 1;
                            }
                        } elseif ($form_field->type == 'file') {
                            // INPUT TYPE FILE
                            $get_file = '';
                            $field = 'mother_'.str_replace(' ', '_', $form_field->name);
                            if ($dynamic_data && count($dynamic_data) > 0) {
                                foreach ($dynamic_data as $field_data) {
                                    if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                        $get_file = $field_data[$field];
                                    }
                                }
                            }
                            $hidden_file_name = 'file-'.$field;

                            if ($request->hasFile($field)) {
                                if ($get_file) {
                                    Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                                }
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('parent', 'public')];
                            } else {
                                if ($request->$hidden_file_name) {
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                }
                            }
                        } else {
                            $field = 'mother_'.str_replace(' ', '_', $form_field->name);
                            $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                        }
                    }
                    // End Parent Dynamic FormField

                    $mother_parent->user_id = $mother_user->id;
                    $mother_parent->first_name = $request->mother_first_name;
                    $mother_parent->last_name = $request->mother_last_name;
                    $mother_parent->image = $mother_user->getRawOriginal('image');
                    $mother_parent->occupation = $request->mother_occupation;
                    $mother_parent->mobile = $request->mother_mobile;
                    $mother_parent->email = $request->mother_email;
                    $mother_parent->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_parent->gender = 'Female';
                    $mother_parent->dynamic_fields = json_encode($data);
                    $mother_parent->save();
                    $mother_parent_id = $mother_parent->id;
                    $mother_email = $request->mother_email;
                    $mother_name = $request->mother_first_name.' '.$request->mother_last_name;
                } else {
                    $mother_parent_id = $request->mother_email;
                    $mother_email = Parents::where('id', $request->mother_email)->pluck('email')->first();
                    $motherData = Parents::where('id', $request->mother_email)->select('first_name', 'last_name')->first();
                    $mother_name = $motherData->full_name;
                }
            } elseif ($request->parent_guardian_type == 'guardian') {
                if (isset($request->guardian_email)) {
                    if (isset($request->guardian_email) && ! intval($request->guardian_email)) {
                        $request->validate([
                            'guardian_email' => 'required|email|unique:parents,email',
                            'guardian_first_name' => 'required',
                            'guardian_last_name' => 'required',
                            'guardian_mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
                            'guardian_gender' => 'required',
                            'guardian_dob' => 'required',
                            'guardian_occupation' => 'required',
                            'guardian_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                        ]);
                    }
                    $guardian_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->guardian_dob)));
                    if (! intval($request->guardian_email)) {
                        $guardian_email = $request->guardian_email;
                        $guardian_user = new User;

                        $guardian_image = $request->file('guardian_image');
                        // made file name with combination of current time
                        $file_name = time().'-'.$guardian_image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'parents/'.$file_name;
                        // resized image
                        resizeImage($guardian_image);
                        // stored image to storage/public/parents folder
                        $destinationPath = storage_path('app/public/parents');
                        $guardian_image->move($destinationPath, $file_name);

                        $guardian_user->image = $file_path;
                        $guardian_user->password = Hash::make($guardian_plaintext_password);
                        $guardian_user->first_name = $request->guardian_first_name;
                        $guardian_user->last_name = $request->guardian_last_name;
                        $guardian_user->email = $guardian_email;
                        $guardian_user->mobile = $request->guardian_mobile;
                        $guardian_user->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_user->gender = $request->guardian_gender;
                        $guardian_user->save();
                        $guardian_user->assignRole($parentRole);

                        $guardian_parent = new Parents;

                        // Parent Dynamic FormField
                        $guardianFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
                        $data = [];
                        $status = 0;
                        $dynamic_data = json_decode($guardian_parent->dynamic_fields ?? '[]', true);

                        foreach ($guardianFields as $form_field) {

                            // INPUT TYPE CHECKBOX
                            if ($form_field->type == 'checkbox') {
                                if ($status == 0) {
                                    $data[] = $request->input('guardian_checkbox', []);
                                    $status = 1;
                                }
                            } elseif ($form_field->type == 'file') {
                                // INPUT TYPE FILE
                                $get_file = '';
                                $field = 'guardian_'.str_replace(' ', '_', $form_field->name);
                                if ($dynamic_data && count($dynamic_data) > 0) {
                                    foreach ($dynamic_data as $field_data) {
                                        if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                            $get_file = $field_data[$field];
                                        }
                                    }
                                }
                                $hidden_file_name = 'file-'.$field;

                                if ($request->hasFile($field)) {
                                    if ($get_file) {
                                        Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                                    }
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('parent', 'public')];
                                } else {
                                    if ($request->$hidden_file_name) {
                                        $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                    }
                                }
                            } else {
                                $field = 'guardian_'.str_replace(' ', '_', $form_field->name);
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                            }
                        }
                        // End Parent Dynamic FormField

                        $guardian_parent->user_id = $guardian_user->id;
                        $guardian_parent->first_name = $request->guardian_first_name;
                        $guardian_parent->last_name = $request->guardian_last_name;
                        $guardian_parent->image = $guardian_user->getRawOriginal('image');
                        $guardian_parent->occupation = $request->guardian_occupation;
                        $guardian_parent->mobile = $request->guardian_mobile;
                        $guardian_parent->email = $guardian_email;
                        $guardian_parent->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_parent->gender = $request->guardian_gender;
                        $guardian_parent->dynamic_fields = json_encode($data);
                        $guardian_parent->save();
                        $guardian_parent_id = $guardian_parent->id;
                        $guardian_name = $request->guardian_first_name.' '.$request->guardian_last_name;
                    } else {
                        $guardian_parent_id = Parents::where('id', $request->guardian_email)->pluck('id')->first();
                        $guardian_email = Parents::where('id', $request->guardian_email)->pluck('email')->first();
                        $guardianData = Parents::where('id', $request->guardian_email)->select('first_name', 'last_name')->first();
                        $guardian_name = $guardianData->full_name;
                    }
                }
            }

            // Create Student User First

            $user = new User;

            // roll number
            $roll_number_db = Students::select(DB::raw('max(roll_number)'))->where('class_section_id', $request->class_section_id)->first();
            $roll_number_db = $roll_number_db['max(roll_number)'];
            $roll_number = $roll_number_db + 1;

            $child_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob ?? '2000-01-01')));

            if ($request->hasFile('image')) {
                $student_image = $request->file('image');
                $file_name = time().'-'.$student_image->getClientOriginalName();
                $file_path = 'students/'.$file_name;
                resizeImage($student_image);
                $destinationPath = storage_path('app/public/students');
                $student_image->move($destinationPath, $file_name);
                $user->image = $file_path;
            }

            $user->password = Hash::make($child_plaintext_password);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            //            $user->email = (isset($request->email)) ? $request->email : "";
            $user->email = $admission_no;
            $user->gender = $request->gender;
            $user->mobile = $request->mobile;
            $user->dob = date('Y-m-d', strtotime($request->dob ?? '2000-01-01'));
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->save();
            $user->assignRole($studentRole);

            $student = new Students;

            // Student dynamic fields
            $studentFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
            $data = [];
            $status = 0;
            $dynamic_data = json_decode($student->dynamic_fields ?? '[]', true);
            foreach ($studentFields as $form_field) {
                // INPUT TYPE CHECKBOX
                if ($form_field->type == 'checkbox') {
                    if ($status == 0) {
                        $data[] = $request->input('checkbox', []);
                        $status = 1;
                    }
                } elseif ($form_field->type == 'file') {
                    // INPUT TYPE FILE
                    $get_file = '';
                    $field = str_replace(' ', '_', $form_field->name);
                    if ($dynamic_data && count($dynamic_data) > 0) {
                        foreach ($dynamic_data as $field_data) {
                            if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                $get_file = $field_data[$field];
                            }
                        }
                    }
                    $hidden_file_name = 'file-'.$field;

                    if ($request->hasFile($field)) {
                        if ($get_file) {
                            Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                        }
                        $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('student', 'public')];
                    } else {
                        if ($request->$hidden_file_name) {
                            $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                        }
                    }
                } else {
                    $field = str_replace(' ', '_', $form_field->name);
                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                }
            }

            // End student dynamic field
            $student->user_id = $user->id;
            $student->class_section_id = $request->class_section_id;
            $student->category_id = $request->category_id;
            $student->admission_no = $admission_no;
            $student->roll_number = $roll_number;
            $student->caste = $request->caste;
            $student->religion = $request->religion;
            $student->admission_date = date('Y-m-d', strtotime($request->admission_date ?? '2000-01-01'));
            $student->blood_group = $request->blood_group;
            $student->height = $request->height;
            $student->weight = $request->weight;
            $student->father_id = $father_parent_id;
            $student->mother_id = $mother_parent_id;
            $student->guardian_id = $guardian_parent_id;
            $student->dynamic_fields = json_encode($data);
            $student->save();

            // -------------------------------------------------
            // Create student session entry for current session
            // -------------------------------------------------
            $currentSessionId = getSettings('session_year')['session_year'];
            StudentSessions::create([
                'student_id' => $student->id,
                'session_year_id' => $currentSessionId,
                'previous_session_year_id' => null,
                'class_section_id' => $request->class_section_id,
                'status' => 1,
                'result' => 1,
            ]);

            if ($request->class_section_id) {
                $classSection = ClassSection::where('id', $request->class_section_id)->with('class.medium', 'class.streams', 'section')->first();
                $class_section_name = $classSection->class->name.' - '.$classSection->section->name.' '.$classSection->class->medium->name.'  '.($classSection->class->streams->name ?? '');
            }

            // Send User Credentials via Email
            $settings = getSettings();
            $school_name = $settings['school_name'];
            $school_email = $settings['school_email'];
            $school_contact = $settings['school_phone'];

            if ($request->parent_guardian_type == 'parent') {
                $father_data = [
                    'subject' => 'Welcome to '.$school_name,
                    'email' => $father_email,
                    'name' => ' '.$father_name,
                    'username' => ' '.$father_email,
                    'password' => ' '.$father_plaintext_password,
                    'child_name' => ' '.$request->first_name.' '.$request->last_name,
                    'child_grnumber' => ' '.$admission_no,
                    'child_password' => ' '.$child_plaintext_password,
                    'type' => 'application_accept',
                    'class_name' => $class_section_name,
                    'school_name' => $school_name,
                    'school_email' => $school_email,
                    'school_contact' => $school_contact,
                ];
                MailService::sendWithFallback('students.email', $father_data, function ($message) use ($father_data) {
                    $message->to($father_data['email'])->subject($father_data['subject']);
                });

                $mother_data = [
                    'subject' => 'Welcome to '.$school_name,
                    'email' => $mother_email,
                    'name' => ' '.$mother_name,
                    'username' => ' '.$mother_email,
                    'password' => ' '.$mother_plaintext_password,
                    'child_name' => ' '.$request->first_name.' '.$request->last_name,
                    'child_grnumber' => ' '.$admission_no,
                    'child_password' => ' '.$child_plaintext_password,
                    'type' => 'application_accept',
                    'class_name' => $class_section_name,
                    'school_name' => $school_name,
                    'school_email' => $school_email,
                    'school_contact' => $school_contact,
                ];
                MailService::sendWithFallback('students.email', $mother_data, function ($message) use ($mother_data) {
                    $message->to($mother_data['email'])->subject($mother_data['subject']);
                });
            } else {
                $guardian_data = [
                    'subject' => 'Welcome to '.$school_name,
                    'email' => $guardian_email,
                    'name' => ' '.$guardian_name,
                    'username' => ' '.$guardian_email,
                    'password' => ' '.$guardian_plaintext_password,
                    'child_name' => ' '.$request->first_name.' '.$request->last_name,
                    'child_grnumber' => ' '.$admission_no,
                    'child_password' => ' '.$child_plaintext_password,
                    'type' => 'application_accept',
                    'class_name' => $class_section_name,
                    'school_name' => $school_name,
                    'school_email' => $school_email,
                    'school_contact' => $school_contact,
                ];
                MailService::sendWithFallback('students.email', $guardian_data, function ($message) use ($guardian_data) {
                    $message->to($guardian_data['email'])->subject($guardian_data['subject']);
                });
            }

            DB::commit();
            ResponseService::successResponse(trans('data_store_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            ResponseService::errorResponse($e->validator->errors()->first(), $e->validator->errors(), 422);
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                ResponseService::successResponse(trans('email_not_send'));
            } else {
                DB::rollback();
                report($e);
                ResponseService::errorResponse(trans('error_occurred'), $e);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (! Auth::user()->can('student-list')) {
            return response()->json(['message' => trans('no_permission_message')]);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        $user = Auth::user();
        $is_restricted_teacher = $user->hasRole('Teacher') && ! $user->can('student-create');

        $sql = StudentSessions::where('session_year_id', $session_year_id)
            ->whereHas('student.user', function ($query) {
                $query->where('status', 1);
            })
            ->with([
                'student.user',
                'student.category',
                'student.father',
                'student.mother',
                'student.guardian',
                'class_section.class.streams',
                'class_section.section',
            ])

            // Restrict to class teacher's class sections for this session year
            ->when($is_restricted_teacher, function ($q) use ($user, $session_year_id) {
                $class_section_ids = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                    ->where('session_year_id', $session_year_id)
                    ->pluck('class_section_id')
                    ->toArray();
                $q->whereIn('class_section_id', $class_section_ids);
            })

            /* ---------- SEARCH (on Students) ---------- */
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('admission_no', 'LIKE', "%$search%")
                            ->orWhere('roll_number', 'LIKE', "%$search%")
                            ->orWhere('caste', 'LIKE', "%$search%")
                            ->orWhere('religion', 'LIKE', "%$search%")
                            ->orWhereHas('user', function ($q) use ($search) {
                                $q->where('first_name', 'LIKE', "%$search%")
                                    ->orWhere('last_name', 'LIKE', "%$search%")
                                    ->orWhereRaw("concat(first_name,' ',last_name) LIKE ?", ["%$search%"])
                                    ->orWhere('email', 'LIKE', "%$search%");
                            });
                    });
                });
            })

            /* ---------- CLASS FILTER ---------- */
            ->when(request('class_id'), function ($q) {
                $q->where('class_section_id', request('class_id'));
            })

            ->when(request('class_section_id'), function ($q) {
                $q->where('class_section_id', request('class_section_id'));
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;

        $rows = [];
        $no = 1;
        $data = getSettings('date_formate');

        // Batch load related data existence to avoid N+1 queries
        $studentIds = $res->pluck('student_id')->unique()->toArray();
        $studentsWithSubmissions = AssignmentSubmission::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithAttendance = Attendance::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithExamMarks = ExamMarks::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithExamResults = ExamResult::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithOnlineAnswers = OnlineExamStudentAnswer::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithOnlineStatus = StudentOnlineExamStatus::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithSessions = StudentSessions::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithSubjects = StudentSubject::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();

        foreach ($res as $sessionRow) {

            $student = $sessionRow->student;

            if (! $student) {
                continue;
            }

            $operate = '';

            if (Auth::user()->can('student-edit')) {
                $operate .= '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata"
            data-id='.$student->id.'
            data-url='.url('students').'
            title="Edit"
            data-toggle="modal"
            data-target="#editModal">
            <i class="fa fa-edit"></i>
        </a>&nbsp;&nbsp;';
            }

            if (Auth::user()->can('student-delete')) {
                $relatedData = [];
                if (in_array($student->id, $studentsWithSubmissions)) {
                    $relatedData[] = 'assignment submissions';
                }
                if (in_array($student->id, $studentsWithAttendance)) {
                    $relatedData[] = 'attendances';
                }
                if (in_array($student->id, $studentsWithExamMarks)) {
                    $relatedData[] = 'exam marks';
                }
                if (in_array($student->id, $studentsWithExamResults)) {
                    $relatedData[] = 'exam result';
                }
                if (in_array($student->id, $studentsWithOnlineAnswers)) {
                    $relatedData[] = 'online exam answers';
                }
                if (in_array($student->id, $studentsWithOnlineStatus)) {
                    $relatedData[] = 'online exam status';
                }
                if (in_array($student->id, $studentsWithSessions)) {
                    $relatedData[] = 'student sessions';
                }
                if (in_array($student->id, $studentsWithSubjects)) {
                    $relatedData[] = 'student subjects';
                }

                $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata"
            data-id='.$student->id.'
            data-user_id='.$student->user_id.'
            data-url='.url('students', $student->user_id).'
            data-warning-text="This student will be deleted permanently from the system."'
                    .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').'
            title="Delete">
            <i class="fa fa-trash"></i>
        </a>&nbsp;&nbsp;';
            }

            if (Auth::user()->can('generate-document')) {
                $operate .= '<div class="dropdown">
            <button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle"
                type="button" data-toggle="dropdown" title="Generate Document">
                <i class="fa fa-file-pdf-o"></i>
            </button>
            <div class="dropdown-menu">
                <a href="'.route('bonafide.certificate.index', $student->id).'"
                    class="compulsory-data dropdown-item"
                    data-id='.$student->id.'>
                    <i class="fa fa-file-text text-success mr-2"></i>'.
                    trans('bonafide').' '.trans('certificate').
                    '</a>
                <div class="dropdown-divider"></div>
                <a href="'.route('leaving.certificate.index', $student->id).'"
                    class="optional-data dropdown-item"
                    data-id='.$student->id.'>
                    <i class="fa fa-file-text text-success mr-2"></i>'.
                    trans('leaving').' '.trans('certificate').
                    '</a>
            </div>
        </div>&nbsp;&nbsp;';
            }

            $tempRow = [];

            $tempRow['id'] = $student->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $student->user_id;
            $tempRow['full_name'] = $student->user->full_name;
            $tempRow['first_name'] = $student->user->first_name;
            $tempRow['last_name'] = $student->user->last_name;
            $tempRow['gender'] = $student->user->gender;
            $tempRow['email'] = $student->user->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime(
                is_object($student->user->dob)
                    ? $student->user->dob->toDateString()
                    : $student->user->dob
            ));
            $tempRow['mobile'] = $student->user->mobile;
            $tempRow['image'] = $student->user->image;
            $tempRow['image_link'] = $student->user->image;

            // From StudentSession
            $tempRow['class_section_id'] = $sessionRow->class_section_id ?? '';
            $tempRow['class_section_name'] =
                ($sessionRow->class_section->class->name ?? '').'-'.
                ($sessionRow->class_section->section->name ?? '');

            $tempRow['stream_name'] =
                $sessionRow->class_section->class->streams->name ?? '';

            // From Student
            $tempRow['category_id'] = $student->category_id;
            $tempRow['category_name'] = $student->category->name ?? '';
            $tempRow['admission_no'] = $student->admission_no;
            $tempRow['roll_number'] = $student->roll_number;
            $tempRow['caste'] = $student->caste;
            $tempRow['religion'] = $student->religion;
            $tempRow['admission_date'] = date(
                $data['date_formate'],
                strtotime($student->admission_date->toDateString())
            );
            $tempRow['blood_group'] = $student->blood_group;
            $tempRow['height'] = $student->height;
            $tempRow['weight'] = $student->weight;
            $tempRow['current_address'] = $student->user->current_address;
            $tempRow['permanent_address'] = $student->user->permanent_address;
            $tempRow['is_new_admission'] = $student->is_new_admission;
            $tempRow['dynamic_data_field'] =
                json_decode($student->dynamic_fields ?? '[]', true);

            // Father
            $tempRow['father_id'] = $student->father->id ?? '';
            $tempRow['father_email'] = $student->father->email ?? '';
            $tempRow['father_full_name'] = $student->father->full_name ?? '-';
            $tempRow['father_first_name'] = $student->father->first_name ?? '';
            $tempRow['father_last_name'] = $student->father->last_name ?? '';
            $tempRow['father_mobile'] = $student->father->mobile ?? '-';
            $tempRow['father_dob'] = $student->father->dob ?? '';
            $tempRow['father_occupation'] = $student->father->occupation ?? '';
            $tempRow['father_image'] = $student->father->image ?? '';
            $tempRow['father_image_link'] = $student->father->image ?? '';

            // Mother
            $tempRow['mother_id'] = $student->mother->id ?? '';
            $tempRow['mother_email'] = $student->mother->email ?? '';
            $tempRow['mother_full_name'] = $student->mother->full_name ?? '-';
            $tempRow['mother_first_name'] = $student->mother->first_name ?? '';
            $tempRow['mother_last_name'] = $student->mother->last_name ?? '';
            $tempRow['mother_mobile'] = $student->mother->mobile ?? '';
            $tempRow['mother_dob'] = $student->mother->dob ?? '';
            $tempRow['mother_occupation'] = $student->mother->occupation ?? '';
            $tempRow['mother_image'] = $student->mother->image ?? '';
            $tempRow['mother_image_link'] = $student->mother->image ?? '';

            // Guardian
            $tempRow['guardian_id'] = $student->guardian->id ?? '';
            $tempRow['guardian_email'] = $student->guardian->email ?? '';
            $tempRow['guardian_full_name'] = $student->guardian->full_name ?? '-';
            $tempRow['guardian_first_name'] = $student->guardian->first_name ?? '';
            $tempRow['guardian_last_name'] = $student->guardian->last_name ?? '';
            $tempRow['guardian_mobile'] = $student->guardian->mobile ?? '-';
            $tempRow['guardian_gender'] = $student->guardian->gender ?? '';
            $tempRow['guardian_dob'] = $student->guardian->dob ?? '';
            $tempRow['guardian_occupation'] = $student->guardian->occupation ?? '';
            $tempRow['guardian_image'] = $student->guardian->image ?? '';
            $tempRow['guardian_image_link'] = $student->guardian->image ?? '';

            $tempRow['operate'] = $operate;

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function getClassSectionsBySessionYear(Request $request)
    {
        $session_year_id = $request->session_year_id;
        $user = Auth::user();

        if ($user->hasRole('Teacher')) {
            // Only show class sections where the teacher is assigned as class teacher for this session year
            $class_section_ids = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id')
                ->toArray();

            $class_sections = ClassSection::with('class.medium', 'section', 'class.streams')
                ->whereIn('id', $class_section_ids)
                ->get();
        } else {
            $class_sections = ClassSection::with('class.medium', 'section', 'class.streams')->get();
        }

        $result = [];
        foreach ($class_sections as $section) {
            $result[] = [
                'id' => $section->id,
                'name' => $section->class->name.' '.$section->section->name.' '.$section->class->medium->name.' '.($section->class->streams->name ?? ''),
            ];
        }

        return response()->json($result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('student-delete')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            DB::transaction(function () use ($id) {

                $student = Students::where('user_id', $id)->firstOrFail();
                $user = User::findOrFail($id);

                // Collect parent IDs before deleting the student
                $parentIds = collect([$student->father_id, $student->mother_id, $student->guardian_id])
                    ->filter()
                    ->unique()
                    ->values();

                // Delete user image
                if ($user->image && Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }

                // Delete parent images
                if ($student->father_image && Storage::disk('public')->exists($student->father_image)) {
                    Storage::disk('public')->delete($student->father_image);
                }

                if ($student->mother_image && Storage::disk('public')->exists($student->mother_image)) {
                    Storage::disk('public')->delete($student->mother_image);
                }

                /**
                 * IMPORTANT:
                 * Delete student first → triggers model cascade
                 */
                $student->delete();

                /**
                 * Then delete user
                 */
                $user->delete();

                /**
                 * Clean up orphaned parents.
                 * If no other student references this parent (as father, mother, or guardian),
                 * delete the parent and their associated user.
                 */
                foreach ($parentIds as $parentId) {
                    $hasOtherChildren = Students::where(function ($query) use ($parentId) {
                        $query->where('father_id', $parentId)
                            ->orWhere('mother_id', $parentId)
                            ->orWhere('guardian_id', $parentId);
                    })->exists();

                    if (! $hasOtherChildren) {
                        $parent = Parents::find($parentId);
                        if ($parent) {
                            // Delete parent's image
                            if ($parent->getRawOriginal('image') && Storage::disk('public')->exists($parent->getRawOriginal('image'))) {
                                Storage::disk('public')->delete($parent->getRawOriginal('image'));
                            }

                            // Delete parent's user
                            if ($parent->user_id) {
                                $parentUser = User::find($parent->user_id);
                                if ($parentUser) {
                                    if ($parentUser->image && Storage::disk('public')->exists($parentUser->image)) {
                                        Storage::disk('public')->delete($parentUser->image);
                                    }
                                    $parentUser->delete();
                                }
                            }

                            $parent->delete();
                        }
                    }
                }
            });

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

    public function reset_password()
    {
        if (! Auth::user()->can('reset-password-list')) {
            $response = [
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

        $sql = User::where('reset_request', 1);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhereRaw("concat(first_name, ' ', last_name) LIKE '%$search%'");
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
            $operate = '<button class="btn btn-xs btn-gradient-primary btn-action btn-rounded btn-icon reset_password" data-id='.$row->id.' title="Reset-Password"><i class="fa fa-edit"></i></button>&nbsp;&nbsp;';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->first_name.' '.$row->last_name;
            $tempRow['dob'] = $row->dob;
            $tempRow['email'] = $row->email;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function change_password(Request $request)
    {
        if (! Auth::user()->can('student-change-password')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        try {
            $dob = date('dmY', strtotime($request->dob));
            $user = User::find($request->id);
            $user->reset_request = 0;
            $user->password = Hash::make($dob);
            $user->save();

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

    public function assignClass()
    {
        //        if (!Auth::user()->can('student-list')) {
        //            $response = array(
        //                'message' => trans('no_permission_message')
        //            );
        //            return redirect(route('home'))->withErrors($response);
        //        }
        $class_section = ClassSection::with('class', 'section')->get();
        $class = ClassSchool::with('medium')->get();
        $category = Category::where('status', 1)->get();

        return view('students.assign-class', compact('class_section', 'class', 'category'));
    }

    public function newStudentList(Request $request)
    {
        $sort = 'id';
        $order = 'DESC';
        $class_id = $request->class_id;

        // Return empty result if no class is selected
        if (empty($class_id)) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $get_class_section_id = ClassSection::where('class_id', $class_id)->pluck('id');

        // Return empty result if no class sections found for the selected class
        if ($get_class_section_id->isEmpty()) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $currentSessionId = getSettings('session_year')['session_year'];

        $sql = StudentSessions::where('session_year_id', $currentSessionId)
            ->whereIn('class_section_id', $get_class_section_id)
            ->with([
                'student.user:id,first_name,last_name,image',
                'class_section.class',
                'class_section.section',
                'class_section.class.medium',
            ]);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($q) use ($search) {
                $q->whereHas('student', function ($query) use ($search) {
                    $query->where('admission_no', 'LIKE', "%$search%")
                        ->orWhere('roll_number', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%");
                        });
                });
            });
        }

        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->get();

        $rows = [];
        $no = 1;
        $data = getSettings('date_formate');
        foreach ($res as $sessionRow) {
            $student = $sessionRow->student;
            if (! $student) {
                continue;
            }
            $tempRow = [];
            $assign_student = '<input type="checkbox" class="assign_student"  name="assign_student" value='.$student->id.'>';
            $tempRow['chk'] = $assign_student;
            $tempRow['id'] = $student->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $student->user_id;
            $tempRow['first_name'] = $student->user->first_name;
            $tempRow['last_name'] = $student->user->last_name;
            $tempRow['image'] = $student->user->image;
            $tempRow['class_section_id'] = $sessionRow->class_section_id;
            $tempRow['class_section_name'] = $sessionRow->class_section->class->name.'-'.$sessionRow->class_section->section->name.' '.$sessionRow->class_section->class->medium->name;
            $tempRow['admission_no'] = $student->admission_no;
            $tempRow['roll_number'] = $student->roll_number;
            $tempRow['admission_date'] = date($data['date_formate'], strtotime(is_object($student->admission_date) ? $student->admission_date->toDateString() : $student->admission_date));
            $rows[] = $tempRow;
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function assignClass_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'selected_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {
            $selected_students = explode(',', $request->selected_id);
            $class_section_id = $request->class_section_id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            foreach ($selected_students as $student_id) {

                $student = Students::find($student_id);

                if (! $student) {
                    continue; // skip invalid IDs safely
                }

                // Update student
                $student->update([
                    'class_section_id' => $class_section_id,
                    'is_new_admission' => 0,
                ]);

                // Update if exists, else create
                StudentSessions::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'session_year_id' => $session_year_id,
                    ],
                    [
                        'class_section_id' => $class_section_id,
                        'status' => 1,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    public function indexStudentRollNumber()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $user = Auth::user();
        $class_section = collect();

        if ($user->hasRole('Teacher') && $user->teacher) {
            $teacher = $user->teacher;
            // Get all class sections this teacher teaches
            $class_section_ids = $teacher->classSections()->pluck('class_sections.id');
            $class_section = ClassSection::with('class', 'section')->whereIn('id', $class_section_ids)->get();
        } else {
            $class_section = ClassSection::with('class', 'section')->get();
        }

        // Find the first class section that has students
        $defaultClassSection = null;
        foreach ($class_section as $cs) {
            if ($cs->students()->count() > 0) {
                $defaultClassSection = $cs;
                break;
            }
        }

        return view('students.assign_roll_no', compact('class_section', 'defaultClassSection'));
    }

    public function listStudentRollNumber(Request $request, $class_section_id = null)
    {
        if (! Auth::user()->can('student-create')) {
            return redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            if (! Auth::user()->can('student-list')) {
                return response()->json([
                    'message' => trans('no_permission_message'),
                ]);
            }

            $class_section_id = $class_section_id ?? $request->class_section_id;
            $session_year_id = getSettings('session_year')['session_year'];
            $sql = StudentSessions::with(['student.user'])
                ->where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id);

            /* -------------------- SEARCH -------------------- */
            if ($request->filled('search')) {

                $search = $request->search;

                $sql->where(function ($query) use ($search) {

                    $query->whereHas('student.user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('dob', 'LIKE', "%{$search}%");
                    })

                        ->orWhereHas('student', function ($q) use ($search) {
                            $q->where('admission_no', 'LIKE', "%{$search}%")
                                ->orWhere('roll_number', 'LIKE', "%{$search}%");
                        });
                });
            }

            $res = $sql->get();

            /* -------------------- SORTING -------------------- */
            if ($request->sort_by == 'first_name') {
                $res = $res->sortBy(fn ($item) => optional($item->student->user)->first_name);
            }

            if ($request->sort_by == 'last_name') {
                $res = $res->sortBy(fn ($item) => optional($item->student->user)->last_name);
            }

            $res = $res->values(); // reset indexes after sort

            $total = $res->count();

            /* -------------------- RESPONSE BUILD -------------------- */

            $bulkData = [];
            $bulkData['total'] = $total;

            $rows = [];
            $no = 1;
            $roll = 1;
            $index = 0;

            $data = getSettings('date_formate');

            foreach ($res as $row) {

                $student = $row->student;
                $user = $student->user;

                $tempRow = [];

                $tempRow['no'] = $no++;
                $tempRow['student_id'] = $student->id;
                $tempRow['old_roll_number'] = $student->roll_number;

                $tempRow['new_roll_number'] =
                    "<input type='hidden' name='roll_number_data[".$index."][student_id]' value='".$student->id."'>
                 <input type='hidden' name='roll_number_data[".$index."][roll_number]' value='".$roll."'>".$roll;

                $tempRow['user_id'] = $row->id;
                $tempRow['first_name'] = $user->first_name;
                $tempRow['last_name'] = $user->last_name;

                $tempRow['dob'] = $user->dob
                    ? date($data['date_formate'], strtotime($user->dob))
                    : null;

                $tempRow['image'] = $user->image;
                $tempRow['admission_no'] = $student->admission_no;

                $tempRow['admission_date'] = $student->admission_date
                    ? $student->admission_date->format($data['date_formate'])
                    : null;

                $rows[] = $tempRow;

                $index++;
                $roll++;
            }

            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Exception $e) {

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    public function storeStudentRollNumber(Request $request)
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'roll_number_data.*.roll_number' => 'required',
            ],
            [
                'roll_number_data.*.roll_number.required' => trans('please_fill_all_roll_numbers_data'),
            ]
        );
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        $i = 1;
        if (! is_null($request->roll_number_data)) {
            foreach ($request->roll_number_data as $data) {
                $student = Students::find($data['student_id']);

                // validation required when the edit of roll number is enabled

                // $class_roll_number_data = Students::where(['class_section_id' => $student->class_section_id,'roll_number' => $data['roll_number']])->whereNot('id',$data['student_id'])->count();
                // if(isset($class_roll_number_data) && !empty($class_roll_number_data)){
                //     $response = array(
                //         'error' => true,
                //         'message' => trans('roll_number_already_exists_of_number').' - '.$i
                //     );
                //     return response()->json($response);
                // }

                $student->roll_number = $data['roll_number'];
                $student->save();
                $i++;
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } else {
            $response = [
                'error' => true,
                'message' => trans('no_data_found'),
            ];
        }

        return response()->json($response);
    }

    public function generateIdCardIndex()
    {
        if (! Auth::user()->can('student-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $class_section = [];
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        $is_teacher = Auth::user()->hasRole('Teacher');

        if (Auth::user()->hasRole('Super Admin')) {
            $class_section = ClassSection::with('class', 'section', 'class.medium', 'class.streams')->get();
        }

        // For teachers, class sections will be loaded via AJAX based on selected session year
        return view('students.generate_id', compact('class_section', 'session_years', 'is_teacher'));
    }

    public function idCardSettingIndex()
    {
        if (! Auth::user()->can('student-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $settings = getSettings();
        $settings['student_id_card_fields'] = explode(',', $settings['student_id_card_fields'] ?? '');

        return view('students.id_card_settings', compact('settings'));
    }

    public function updateIdCardSetting(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'header_color' => 'required',
            'footer_color' => 'required',
            'header_footer_text_color' => 'required',
            'layout_type' => 'required',
            'profile_image_style' => 'required',
            'card_width' => 'required',
            'card_height' => 'required',
            'student_id_card_fields' => 'required',
            'background_image' => 'nullable|image|max:2048',
            'signature' => 'nullable',
        ], [
            'student_id_card_fields.required' => 'Please Select at least one field.',
        ]);

        $settings = [
            'header_color',
            'footer_color',
            'header_footer_text_color',
            'layout_type',
            'profile_image_style',
            'card_width',
            'card_height',
            'student_id_card_fields',
        ];
        try {
            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {

                    // removing the double unnecessary double quotes in school name
                    if ($row == 'student_id_card_fields') {
                        $data = [
                            'message' => implode(',', $request->student_id_card_fields),
                        ];
                    } else {
                        $data = [
                            'message' => $request->$row,
                        ];
                    }
                    Settings::where('type', $row)->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = $row == 'student_id_card_fields' ? implode(',', $request->student_id_card_fields) : $request->$row;
                    $setting->save();
                }

                if ($request->hasFile('background_image')) {
                    if (Settings::where('type', 'background_image')->exists()) {
                        $get_id = Settings::select('message')->where('type', 'background_image')->pluck('message')->first();
                        if (Storage::disk('public')->exists($get_id)) {
                            Storage::disk('public')->delete($get_id);
                        }
                        $data = [
                            'message' => $request->file('background_image')->store('Idcard', 'public'),
                        ];
                        Settings::where('type', 'background_image')->update($data);
                    } else {
                        $setting = new Settings;
                        $setting->type = 'background_image';
                        $setting->message = $request->file('background_image')->store('Idcard', 'public');
                        $setting->save();
                    }
                }

                if ($request->hasFile('signature')) {
                    if (Settings::where('type', 'signature')->exists()) {
                        $get_id = Settings::select('message')->where('type', 'signature')->pluck('message')->first();
                        if (Storage::disk('public')->exists($get_id)) {
                            Storage::disk('public')->delete($get_id);
                        }
                        $data = [
                            'message' => $request->file('signature')->store('Idcard', 'public'),
                        ];
                        Settings::where('type', 'signature')->update($data);
                    } else {
                        $setting = new Settings;
                        $setting->type = 'signature';
                        $setting->message = $request->file('signature')->store('Idcard', 'public');
                        $setting->save();
                    }
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
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

    public function deleteImage(Request $request)
    {
        try {
            $setting = Settings::where('type', $request->type)->first();

            if (Storage::disk('public')->exists($setting->getRawOriginal('message'))) {
                Storage::disk('public')->delete($setting->getRawOriginal('message'));
            }
            $setting->delete();

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
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

    public function generateIdCard(Request $request)
    {
        $ids = explode(',', $request->user_id);
        $settings = getSettings();

        if (! isset($settings['student_id_card_fields'])) {
            return redirect()->route('id_card_setting.index')->with('error', trans('settings_not_found'));
        }

        $settings['student_id_card_fields'] = explode(',', $settings['student_id_card_fields']);

        $data = explode('storage/', $settings['signature'] ?? '');
        $settings['signature'] = end($data);

        $data = explode('storage/', $settings['background_image'] ?? '');
        $settings['background_image'] = end($data);

        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();

        $height = $settings['card_height'] * 2.8346456693;
        $width = $settings['card_width'] * 2.8346456693;
        // $customPaper = array(0,0,360,200);
        $customPaper = [0, 0, $width, $height];
        $students = Students::select('admission_no', 'roll_number', 'blood_group', 'user_id', 'class_section_id', 'guardian_id', 'father_id')->with('user:id,first_name,last_name,gender,image,dob,permanent_address', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name,mobile', 'guardian:id,first_name,last_name,mobile')->whereIn('id', $ids)->get();

        $settings['card_height'] = ($settings['card_height'] * 3.7795275591).'px';

        $pdf = PDF::loadView('students.id_card_template', compact('students', 'sessionYear', 'settings'));

        $pdf->setPaper($customPaper);

        return $pdf->stream('id_card.pdf');
    }

    public function bonafideCertificateIndex($id)
    {
        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $student = Students::where('id', $id)->first();

        return view('students.bonafide_certificate', compact('student'));
    }

    public function generateBonafideCertificate(Request $request)
    {

        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $reason = $request->reason;
        $valid_upto = $request->valid_upto;
        $id = $request->id;
        $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        $settings = getSettings();
        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();
        $student = Students::select('roll_number', 'admission_no', 'user_id', 'class_section_id', 'guardian_id', 'father_id')->with('user:id,first_name,last_name,dob', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name', 'guardian:id,first_name,last_name')->where('id', $id)->first();

        $student_name = $student->user->first_name.' '.$student->user->last_name;
        if ($student->father) {
            $guardian_name = $student->father->first_name.' '.$student->father->last_name;
        } else {
            $guardian_name = $student->guardian->first_name.' '.$student->guardian->last_name;
        }
        $gr_no = $student->admission_no;
        $dob = date('d-m-Y', strtotime($student->user->dob));
        $roll_number = $student->roll_number;
        $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

        $pdf = PDF::loadView('students.bonafide_template', compact('student_name', 'guardian_name', 'gr_no', 'dob', 'roll_number', 'class_section', 'sessionYear', 'settings', 'reason', 'valid_upto', 'date'));

        return $pdf->stream('bonafide_certificate.pdf');
    }

    public function leavingCertificateIndex($id)
    {
        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $student = Students::where('id', $id)->first();

        return view('students.leaving_certificate', compact('student'));
    }

    public function generateLeavingCertificate(Request $request)
    {

        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $reason = $request->reason;
        $promoted_to = $request->promoted_to;
        $general_conduct = $request->general_conduct;
        $remarks = $request->remark;
        $father_name = null;
        $mother_name = null;
        $guardian_name = null;

        $id = $request->id;
        $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        $settings = getSettings();
        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();
        $student = Students::select('roll_number', 'admission_no', 'admission_date', 'user_id', 'class_section_id', 'guardian_id', 'father_id', 'mother_id')->with('user:id,first_name,last_name,dob', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name', 'guardian:id,first_name,last_name')->where('id', $id)->first();

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

        $pdf = PDF::loadView('students.leaving_template', compact('student_name', 'guardian_name', 'gr_no', 'dob', 'roll_number', 'class_section', 'sessionYear', 'settings', 'reason', 'promoted_to', 'date', 'general_conduct', 'remarks', 'admission_date', 'father_name', 'mother_name'));

        return $pdf->stream('leaving_certificate.pdf');
    }

    public function resultIndex()
    {
        if (! Auth::user()->can('generate-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $is_teacher = Auth::user()->hasRole('Teacher');
        $classes = [];

        if (! $is_teacher) {
            $classes = ClassSection::with('class', 'section', 'class.medium', 'streams')->get();
        }

        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return view('students.generate_result', compact('classes', 'session_years', 'is_teacher'));
    }

    public function generateResult(Request $request, $id)
    {
        if (! Auth::user()->can('generate-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $father_name = null;
        $mother_name = null;
        $guardian_name = null;
        $examarray = [];
        $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        $settings = getSettings();
        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();

        $student = StudentSessions::with(
            'student.user:id,first_name,last_name,dob',
            'class_section.class:id,name,medium_id,stream_id',
            'class_section.class.medium:id,name',
            'class_section.class.streams:id,name',
            'class_section.section:id,name',
            'student.father:id,first_name,last_name',
            'student.guardian:id,first_name,last_name'
        )
            ->where('student_id', $id)
            ->where('session_year_id', $settings['session_year'])
            ->first();

        if (! $student) {
            return redirect()->back()->withErrors(trans('no_data_found'));
        }

        $student_name = $student->student->user->first_name.' '.$student->student->user->last_name;

        if ($student->student->father) {
            $father_name = $student->student->father->first_name.' '.$student->student->father->last_name;
        }

        if ($student->student->guardian) {
            $guardian_name = $student->student->guardian->first_name.' '.$student->student->guardian->last_name;
        }
        $admission_date = $student->student->admission_date;
        $gr_no = $student->student->admission_no;
        $dob = date('d-m-Y', strtotime($student->student->user->dob));
        $roll_number = $student->student->roll_number;
        $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

        $class_id = $student->class_section->class->id;

        $student_subject = $student->student->subjects(true);
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
                        ->where('student_id', $student->student_id)
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
        $totalMarks = 0;
        $obtainmarks = 0;

        foreach ($subjects as $subject) {
            $examObtainedMarks = null;
            $examTotalMarks = null;
            $subjectGrade = null;
            $subjectType = $subject->type;

            foreach ($examarray as $exam_data) {
                if ($exam_data['timetable']) {
                    foreach ($exam_data['timetable'] as $timetable) {
                        if ($timetable['subject_id'] == $subject->id) {
                            $exam_marks = $timetable['exam_marks'];
                            if ($exam_marks) {
                                $ObtainedMarks = $exam_marks['obtained_marks'];
                                $totalMarks += $timetable['total_marks'];

                                $examObtainedMarks += $ObtainedMarks;
                                $examTotalMarks += $timetable['total_marks'];

                                $subjectMarks[$subject->name.' ('.$subjectType.')'][$exam_data['name']] = $ObtainedMarks.'/'.$timetable['total_marks'];

                                if ($examTotalMarks > 0) {
                                    $percent = round(($examObtainedMarks / $examTotalMarks) * 100, 2);
                                    $grade_percent = round($percent);
                                    $subjectGrade = Grade::where('starting_range', '<=', $grade_percent)
                                        ->where('ending_range', '>=', $grade_percent)
                                        ->pluck('grade')
                                        ->first();
                                } else {
                                    $subjectGrade = null;
                                }
                            }
                        }
                    }
                }
            }

            $subjectMarks[$subject->name.' ('.$subjectType.')']['total_obtained'] = $examObtainedMarks;
            $subjectMarks[$subject->name.' ('.$subjectType.')']['total_marks'] = $examTotalMarks;
            $subjectMarks[$subject->name.' ('.$subjectType.')']['grade'] = $subjectGrade;

            $obtainmarks += $examObtainedMarks;
        }

        if ($totalMarks > 0) {
            $percentage = round(($obtainmarks / $totalMarks) * 100, 2);
            $grade_percentage = round($percentage);
            $grade = Grade::where('starting_range', '<=', $grade_percentage)
                ->where('ending_range', '>=', $grade_percentage)
                ->pluck('grade')
                ->first();
            $result = ($grade_percentage >= 40) ? 'Passed' : 'Failed';
        } else {
            $percentage = null;
            $grade = null;
            $result = null;
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
            'totalMarks' => $totalMarks,
            'obtainmarks' => $obtainmarks,
            'percentage' => $percentage,
            'grade' => $grade,
            'result' => $result,
        ];

        $pdf = PDF::loadView('students.result_template', compact('data', 'settings', 'exams', 'subjects'));

        return $pdf->stream('result.pdf');
    }

    public function generateExamResult(Request $request, $id)
    {
        if (! Auth::user()->can('view-exam-result')) {
            return redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]);
        }

        $settings = getSettings();
        $date = date('d-m-Y');

        $exam_id = $request->query('exam_id');
        $semester_id = $request->query('semester_id');
        $session_year_id = $request->query('session_year_id', $settings['session_year'] ?? null);

        $validator = Validator::make([
            'exam_id' => $exam_id,
            'session_year_id' => $session_year_id,
            'semester_id' => $semester_id,
        ], [
            'exam_id' => 'required|exists:exams,id',
            'session_year_id' => 'required|exists:session_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ], [
            'exam_id.required' => 'Exam not selected.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        $sessionYear = SessionYear::where('id', $session_year_id)
            ->value('name');

        // Student
        $student = StudentSessions::with(
            'student.user:id,first_name,last_name,dob',
            'class_section.class.medium',
            'class_section.class.streams',
            'class_section.section',
            'student.father',
            'student.guardian'
        )
            ->where('student_id', $id)
            ->where('session_year_id', $session_year_id)
            ->first();

        if (! $student) {
            return redirect()->back()->withErrors(trans('no_data_found'));
        }

        // Basic Info
        $student_name = $student->student->user->first_name.' '.$student->student->user->last_name;
        $father_name = optional($student->student->father)->first_name.' '.optional($student->student->father)->last_name;
        $guardian_name = optional($student->student->guardian)->first_name.' '.optional($student->student->guardian)->last_name;

        $dob = date('d-m-Y', strtotime($student->student->user->dob));
        $gr_no = $student->student->admission_no;
        $roll_number = $student->student->roll_number;

        $class_section = $student->class_section->class->name.' '.
            $student->class_section->section->name.' '.
            $student->class_section->class->medium->name.' '.
            ($student->class_section->class->streams->name ?? '');

        $class_id = $student->class_section->class->id;

        // Student Subjects
        $student_subject = $student->student->subjects();

        $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

        $elective_subjects = $student_subject['elective_subject'] ?? [];

        if ($elective_subjects) {
            $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
        }

        $student_subject_ids = array_merge($core_subjects, $elective_subjects);

        // Get only subjects available in this exam timetable
        $exam_subject_ids = ExamTimetable::where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->whereIn('subject_id', $student_subject_ids)
            ->pluck('subject_id')
            ->toArray();

        // Final subjects = only subjects present in exam timetable
        $subjects = Subject::whereIn('id', $exam_subject_ids)->get();

        // Single Exam with timetable
        $exam = Exam::with(['timetable' => function ($q) use ($class_id, $exam_subject_ids) {
            $q->where('class_id', $class_id)
                ->whereIn('subject_id', $exam_subject_ids);
        }])
            ->where('id', $exam_id)
            ->where('session_year_id', $session_year_id)
            ->when($semester_id, function ($query) use ($semester_id) {
                $query->where('semester_id', $semester_id);
            })
            ->where('publish', 1)
            ->first();

        if (! $exam) {
            return redirect()->back()->withErrors('Exam not found');
        }

        $subjectMarks = [];
        $totalMarks = 0;
        $obtainmarks = 0;

        foreach ($subjects as $subject) {

            $timetable = $exam->timetable->where('subject_id', $subject->id)->first();

            $obtained = 0;
            $total = 0;
            $grade = null;

            if ($timetable) {

                $exam_marks = ExamMarks::where('exam_timetable_id', $timetable->id)
                    ->where('student_id', $student->student_id)
                    ->where('session_year_id', $session_year_id)
                    ->first();

                if ($exam_marks) {
                    $obtained = $exam_marks->obtained_marks;
                    $total = $timetable->total_marks;

                    $totalMarks += $total;
                    $obtainmarks += $obtained;

                    // Grade
                    if ($total > 0) {
                        $percent = round(($obtained / $total) * 100);
                        $grade = Grade::where('starting_range', '<=', $percent)
                            ->where('ending_range', '>=', $percent)
                            ->value('grade');
                    }
                }
            }

            $subjectMarks[$subject->name.' ('.$subject->type.')'] = [
                'obtained' => $obtained,
                'total' => $total,
                'grade' => $grade,
            ];
        }

        // Overall Result
        $percentage = $totalMarks > 0
            ? round(($obtainmarks / $totalMarks) * 100, 2)
            : 0;

        $grade = Grade::where('starting_range', '<=', round($percentage))
            ->where('ending_range', '>=', round($percentage))
            ->value('grade');

        $result = ($percentage >= 40) ? 'Passed' : 'Failed';

        // Final Data
        $data = [
            'student_name' => $student_name,
            'guardian_name' => $guardian_name,
            'gr_no' => $gr_no,
            'dob' => $dob,
            'roll_number' => $roll_number,
            'class_section' => $class_section,
            'sessionYear' => $sessionYear,
            'date' => $date,
            'father_name' => $father_name,
            'exam_name' => $exam->name, // added
            'subjects' => $subjectMarks,
            'totalMarks' => $totalMarks,
            'obtainmarks' => $obtainmarks,
            'percentage' => $percentage,
            'grade' => $grade,
            'result' => $result,
        ];

        $pdf = PDF::loadView(
            'students.exam_result_template',
            compact('data', 'settings', 'exam', 'subjects')
        );

        return $pdf->stream('result.pdf');
    }

    public function studentList(Request $request)
    {
        if (! Auth::user()->can('student-list')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

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

            $operate = '';
            if (Auth::user()->can('generate-result')) {
                $operate = '<a href="'.route('generate.result', $student->id).'" 
                class="btn btn-xs btn-gradient-success btn-rounded btn-icon" 
                data-id="'.$student->id.'" title="Generate Result">
                <i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
            }

            $rows[] = [
                'id' => $student->id,
                'no' => $no++,
                'user_id' => $student->user_id,
                'student_name' => $student->user->first_name.' '.$student->user->last_name,
                'dob' => optional($student->user->dob)->format($dateFormat['date_formate']),
                'admission_no' => $student->admission_no,
                'class_section_id' => $session->class_section_id,
                'class_section_name' => ($session->class_section->class->name ?? '').' '.
                    ($session->class_section->section->name ?? '').' '.
                    ($session->class_section->class->medium->name ?? '').' '.
                    ($session->class_section->class->streams->name ?? ''),
                'roll_number' => $student->roll_number,
                'operate' => $operate,
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function onlineRegistrationIndex()
    {
        if (! Auth::user()->can('online-registration-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $classSchools = ClassSchool::with('medium', 'streams')->get();
        $category = Category::where('status', 1)->get();
        $formFields = FormField::where('for', 4)->orderBy('rank', 'ASC')->get();

        return view('students.online_registration', compact('classSchools', 'category', 'formFields'));
    }

    public function onlineRegistrationList()
    {
        if (! Auth::user()->can('online-registration-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $sql = Students::with('user', 'class', 'category', 'father', 'mother', 'guardian')->where('application_type', 'online')->ofTeacher()
            // Search query
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_id', 'LIKE', "%$search%")
                        ->orWhere('class_id', 'LIKE', "%$search%")
                        ->orWhere('category_id', 'LIKE', "%$search%")
                        ->orWhere('admission_no', 'LIKE', "%$search%")
                        ->orWhere('roll_number', 'LIKE', "%$search%")
                        ->orWhere('caste', 'LIKE', "%$search%")
                        ->orWhere('religion', 'LIKE', "%$search%")
                        ->orWhere('admission_date', 'LIKE', date('Y-m-d', strtotime("%$search%")))
                        ->orWhere('blood_group', 'LIKE', "%$search%")
                        ->orWhere('height', 'LIKE', "%$search%")
                        ->orWhere('weight', 'LIKE', "%$search%")
                        ->orWhere('is_new_admission', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('father', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('mobile', 'LIKE', "%$search%")
                                ->orWhere('occupation', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('mother', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('mobile', 'LIKE', "%$search%")
                                ->orWhere('occupation', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        });
                });
            })
            // Class filter data
            ->when(request('class_id') != null, function ($query) {
                $classId = request('class_id');
                $query->where(function ($query) use ($classId) {
                    $query->where('class_id', $classId);
                });
            })
            // Filter by user status (0 = pending, 2 = rejected)
            ->whereHas('user', function ($query) {
                $query->whereIn('status', [0, 2]);
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        // dd($res->toArray());
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        $data = getSettings('date_formate');
        foreach ($res as $row) {
            $operate = '';
            $userStatus = $row->user->status ?? 0;

            // Only show edit button for pending (status=0) students, not rejected (status=2)
            if (Auth::user()->can('student-edit') && $userStatus == 0) {
                $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' data-url='.url('students').' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            }

            if (Auth::user()->can('student-delete')) {
                $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletepermanentdata" data-id='.$row->id.' data-user_id='.$row->user_id.' data-url='.url('permanent-delete', $row->id).' title="Permanent Delete"><i class="fa fa-exclamation-triangle"></i></a>&nbsp;&nbsp;';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['first_name'] = $row->user->first_name;
            $tempRow['last_name'] = $row->user->last_name;
            $tempRow['gender'] = $row->user->gender;
            $tempRow['email'] = $row->user->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime(is_object($row->user->dob) ? $row->user->dob->toDateString() : $row->user->dob));
            $tempRow['mobile'] = $row->user->mobile;
            $tempRow['image'] = $row->user->image;
            $tempRow['image_link'] = $row->user->image;
            $tempRow['class_id'] = $row->class_id;
            $tempRow['class_name'] = $row->class->name.'-'.$row->class->medium->name.' '.($row->class->streams->name ?? '');
            $tempRow['category_id'] = $row->category_id;
            $tempRow['category_name'] = $row->category->name;
            $tempRow['admission_no'] = $row->admission_no;
            $tempRow['caste'] = $row->caste;
            $tempRow['religion'] = $row->religion;
            $tempRow['admission_date'] = date($data['date_formate'], strtotime(is_object($row->admission_date) ? $row->admission_date->toDateString() : $row->admission_date));
            $tempRow['blood_group'] = $row->blood_group;
            $tempRow['height'] = $row->height;
            $tempRow['weight'] = $row->weight;
            $tempRow['current_address'] = $row->user->current_address;
            $tempRow['permanent_address'] = $row->user->permanent_address;
            $tempRow['is_new_admission'] = $row->is_new_admission;
            $tempRow['user_status'] = $userStatus;
            $tempRow['dynamic_data_field'] = json_decode($row->dynamic_fields ?? '[]', true);

            // Father Data
            $tempRow['father_id'] = ! empty($row->father) ? $row->father->id : '';
            $tempRow['father_email'] = ! empty($row->father) ? $row->father->email : '';
            $tempRow['father_first_name'] = ! empty($row->father) ? $row->father->first_name : '-';
            $tempRow['father_last_name'] = ! empty($row->father) ? $row->father->last_name : '';
            $tempRow['father_mobile'] = ! empty($row->father) ? $row->father->mobile : '-';
            $tempRow['father_dob'] = ! empty($row->father) ? $row->father->dob : '';
            $tempRow['father_occupation'] = ! empty($row->father) ? $row->father->occupation : '';
            $tempRow['father_image'] = ! empty($row->father) ? $row->father->image : '';
            $tempRow['father_image_link'] = ! empty($row->father) ? $row->father->image : '';

            // Mother Data
            $tempRow['mother_id'] = ! empty($row->mother) ? $row->mother->id : '';
            $tempRow['mother_email'] = ! empty($row->mother) ? $row->mother->email : '';
            $tempRow['mother_first_name'] = ! empty($row->mother) ? $row->mother->first_name : '-';
            $tempRow['mother_last_name'] = ! empty($row->mother) ? $row->mother->last_name : '';
            $tempRow['mother_mobile'] = ! empty($row->mother) ? $row->mother->mobile : '';
            $tempRow['mother_dob'] = ! empty($row->mother) ? $row->mother->dob : '';
            $tempRow['mother_occupation'] = ! empty($row->mother) ? $row->mother->occupation : '';
            $tempRow['mother_image'] = ! empty($row->mother) ? $row->mother->image : '';
            $tempRow['mother_image_link'] = ! empty($row->mother) ? $row->mother->image : '';

            // Guardian Data
            $tempRow['guardian_id'] = ! empty($row->guardian) ? $row->guardian->id : '';
            $tempRow['guardian_email'] = ! empty($row->guardian) ? $row->guardian->email : '';
            $tempRow['guardian_first_name'] = ! empty($row->guardian) ? $row->guardian->first_name : '-';
            $tempRow['guardian_last_name'] = ! empty($row->guardian) ? $row->guardian->last_name : '';
            $tempRow['guardian_mobile'] = ! empty($row->guardian) ? $row->guardian->mobile : '-';
            $tempRow['guardian_gender'] = ! empty($row->guardian) ? $row->guardian->gender : '';
            $tempRow['guardian_dob'] = ! empty($row->guardian) ? $row->guardian->dob : '';
            $tempRow['guardian_occupation'] = ! empty($row->guardian) ? $row->guardian->occupation : '';
            $tempRow['guardian_image'] = ! empty($row->guardian) ? $row->guardian->image : '';
            $tempRow['guardian_image_link'] = ! empty($row->guardian) ? $row->guardian->image : '';

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function updateStatus(Request $request)
    {
        try {
            $user = User::with('student')->where('id', $request->edit_id)->first();

            // Prevent re-processing of already rejected students
            if ($user->status == 2) {
                return response()->json([
                    'error' => true,
                    'message' => trans('student_already_rejected'),
                ]);
            }

            $class = ClassSchool::with('medium', 'streams')->where('id', $request->class_id)->first();
            $class_name = $class->name.' - '.$class->medium->name.' '.($class->streams->name ?? '');

            $child_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($user->dob)));
            $father_id = $user->student->father_id ?? null;
            $mother_id = $user->student->mother_id ?? null;
            $guardian_id = $user->student->guardian_id ?? null;
            $parent_id = [$father_id, $mother_id, $guardian_id];

            $parents = Parents::with('user')->whereIn('id', $parent_id)->get();
            $admin_mail = env('MAIL_FROM_ADDRESS');

            $settings = getSettings();
            $school_name = $settings['school_name'];
            $school_email = $settings['school_email'];
            $school_contact = $settings['school_phone'];

            if ($request->status == 1) {
                $user->student->class_section_id = $request->class_section_id;
                $user->status = $request->status;
                $user->student->update();
                $user->update();

                // -------------------------------------------------
                // Ensure student session entry exists for current session
                // -------------------------------------------------
                $currentSessionId = getSettings('session_year')['session_year'];
                StudentSessions::create([
                    'student_id' => $user->student->id,
                    'session_year_id' => $currentSessionId,
                    'previous_session_year_id' => null,
                    'class_section_id' => $request->class_section_id,
                    'status' => 1,
                    'result' => 1,
                ]);

                if ($request->class_section_id) {
                    $classSection = ClassSection::where('id', $request->class_section_id)->with('class.medium', 'class.streams', 'section')->first();
                    $class_section_name = $classSection->class->name.' - '.$classSection->section->name.' '.$classSection->class->medium->name.'  '.($classSection->class->streams->name ?? '');
                }
                // Send User Credentials via Email

                foreach ($parents as $parent) {
                    $parent->user->status = 1;
                    $parent->user->update();
                    $parent_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($parent->dob)));

                    $parent_data = [
                        'subject' => 'Welcome to '.$school_name,
                        'email' => $parent->email,
                        'name' => ' '.$parent->first_name.' '.$parent->last_name,
                        'username' => ' '.$parent->email,
                        'password' => ' '.$parent_plaintext_password,
                        'child_name' => ' '.$user->first_name.' '.$user->last_name,
                        'child_grnumber' => ' '.$user->email,
                        'child_password' => ' '.$child_plaintext_password,
                        'class_name' => $class_section_name,
                        'type' => 'application_accept',
                        'school_name' => $school_name,
                        'school_email' => $school_email,
                        'school_contact' => $school_contact,
                    ];
                    MailService::sendWithFallback('students.email', $parent_data, function ($message) use ($parent_data) {
                        $message->to($parent_data['email'])->subject($parent_data['subject']);
                    });
                }

                $response = [
                    'error' => false,
                    'message' => trans('user_activate_successfully'),
                ];
            } else {
                // Set student status to rejected (2)
                $user->status = 2;
                $user->update();

                foreach ($parents as $parent) {
                    $parent->user->status = 2;
                    $parent->user->update();

                    $data = [
                        'subject' => 'Response To Online Registration',
                        'email' => $parent->email,
                        'name' => $parent->first_name.' '.$parent->last_name,
                        'child_name' => ' '.$user->first_name.' '.$user->last_name,
                        'class_name' => $class_name,
                        'type' => 'application_reject',
                        'school_name' => $school_name,
                        'school_email' => $school_email,
                        'school_contact' => $school_contact,
                    ];

                    MailService::sendWithFallback('students.email', $data, function ($message) use ($data, $admin_mail, $school_name) {
                        $message->to($data['email'])->subject($data['subject']);
                        $message->from($admin_mail, $school_name);
                    });
                }

                $response = [
                    'error' => false,
                    'message' => trans('email_sent_successfully'),
                ];
            }
        } catch (\Throwable $e) {
            // dd($e->getMessage());
            if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                $response = [
                    'error' => false,
                    'message' => 'Message send successfully. But Email not sent.',
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => trans('error_occurred'),
                    'data' => $e,
                ];
            }
        }

        return response()->json($response);
    }

    public function permanentDelete($id)
    {
        try {
            $student = Students::with('user')->where('id', $id)->first();

            if ($student) {

                if ($student->user) {
                    $student->user->forceDelete();
                }
                $student->forceDelete();
            }

            $response = [
                'error' => false,
                'message' => trans('user_delete_successfully'),
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

    public function getClassSectionByClass($class_id)
    {
        try {
            $classSection = ClassSection::where('class_id', $class_id)->with('class.medium', 'class.streams', 'section')->get();

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $classSection,
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

    public function getStudentResultDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required',
            'class_section_id' => 'required',
            'session_year_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $student_id = $request->student_id;
            $class_section_id = $request->class_section_id;
            $sessionYearId = $request->session_year_id;

            // Fetch student with relations (eager loaded)
            $studentDetails = Students::with(['user', 'class_section.class', 'class_section.section'])
                ->findOrFail($student_id);

            $name = trim($studentDetails->user->first_name.' '.$studentDetails->user->last_name);

            // Get Avatar
            $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=10B981&color=fff';
            $imagePath = $studentDetails->user->image;
            $validImage = $imagePath ? Storage::exists($imagePath) : false;
            $imageUrl = $validImage ? Storage::url($imagePath) : $avatarUrl;

            // 1. Get exam results with exam relation
            $examDetails = ExamResult::with('exam')
                ->where('student_id', $student_id)
                ->where('class_section_id', $class_section_id)
                ->where('session_year_id', $sessionYearId)
                ->get();

            if ($examDetails->isEmpty()) {
                return response()->json([
                    'student' => [
                        'name' => $name,
                        'roll_number' => $studentDetails->roll_number,
                        'admission_no' => $studentDetails->admission_no,
                        'class_section' => $studentDetails->class_section->class->name.' '.$studentDetails->class_section->section->name,
                        'image' => $imageUrl,
                    ],
                    'summary' => [],
                    'exam_history' => [],
                ]);
            }

            // 2. Extract exam IDs
            $examIds = $examDetails->pluck('exam_id')->filter()->unique()->values();

            // 3. Fetch timetables for these exams
            $examTimetables = ExamTimetable::whereIn('exam_id', $examIds)
                ->get(['id', 'exam_id', 'date', 'total_marks', 'subject_id'])
                ->keyBy('id');

            $timetableIds = $examTimetables->keys()->all();

            // 4. Fetch marks with all necessary relations
            $examMarks = ExamMarks::with([
                'timetable.subject',
                'subject',
            ])
                ->whereIn('exam_timetable_id', $timetableIds)
                ->where('student_id', $student_id)
                ->get();

            // Group marks by exam_id using timetable->exam_id
            $marksGroupedByExam = [];
            foreach ($examMarks as $mark) {
                $timetable = $mark->timetable;
                if (! $timetable || is_null($timetable->exam_id)) {
                    continue;
                }
                $marksGroupedByExam[$timetable->exam_id][] = $mark;
            }

            // -------------------------
            // BUILD EXAM HISTORY
            // -------------------------
            $examHistory = [];
            $summaryTotalObtained = 0;
            $summaryTotalMax = 0;
            $percentages = [];

            foreach ($examDetails as $examResult) {
                $examId = $examResult->exam_id;
                $subjectMarks = collect($marksGroupedByExam[$examId] ?? []);

                $subjectList = [];
                $examTotalObtained = 0;
                $examTotalMarks = 0;
                $examDateCarbons = [];

                foreach ($subjectMarks as $mark) {
                    $timetable = $mark->timetable;
                    $subject = $mark->subject ?? $timetable?->subject; // Safe fallback, both eager loaded

                    $total = $timetable?->total_marks ?? 0;
                    $obt = $mark->obtained_marks ?? 0;
                    $percent = $total > 0 ? round(($obt / $total) * 100) : 0;

                    $subjectList[] = [
                        'name' => $subject?->name ?? 'Unknown Subject',
                        'obtained_marks' => $obt,
                        'total_marks' => $total,
                        'percentage' => $percent,
                        'grade' => $mark->grade ?? null,
                    ];

                    $examTotalObtained += $obt;
                    $examTotalMarks += $total;

                    if ($timetable?->date) {
                        try {
                            $examDateCarbons[] = \Carbon\Carbon::parse($timetable->date);
                        } catch (\Throwable $e) {
                            // Ignore invalid dates
                            $response = [
                                'error' => true,
                                'message' => trans('error_occurred'),
                            ];
                        }
                    }
                }

                $examPercent = $examResult->percentage; // Keep using stored value for consistency

                $percentages[] = $examPercent;
                $summaryTotalObtained += $examTotalObtained;
                $summaryTotalMax += $examTotalMarks;

                // Earliest exam date
                $examDate = null;
                if ($examDateCarbons) {
                    $earliest = collect($examDateCarbons)->sort()->first();
                    $examDate = $earliest?->format('d M Y');
                }

                $examHistory[] = [
                    'name' => $examResult->exam?->name ?? 'Unknown Exam',
                    'date' => $examDate,
                    'percentage' => $examPercent,
                    'obtained_marks' => $examTotalObtained,
                    'total_marks' => $examTotalMarks,
                    'grade' => $examResult->grade,
                    'subjects' => $subjectList,
                ];
            }

            // Sort exam history by date descending (null dates last)
            $examHistory = collect($examHistory)->sortByDesc(function ($item) {
                if (empty($item['date'])) {
                    return PHP_INT_MAX;
                }
                try {
                    return Carbon::createFromFormat('d M Y', $item['date'])->timestamp;
                } catch (\Throwable $e) {
                    return PHP_INT_MAX - 1;
                }
            })->values()->all();

            // -------------------------
            // BUILD SUMMARY
            // -------------------------
            $summary = [
                'total_exams' => count($examHistory),
                'average' => count($percentages) ? round(array_sum($percentages) / count($percentages), 2) : 0,
                'highest' => $percentages ? max($percentages) : 0,
                'lowest' => $percentages ? min($percentages) : 0,
                'overall_grade' => $examDetails->sortByDesc('percentage')->first()->grade ?? '-',
                'total_obtained' => $summaryTotalObtained,
                'total_max' => $summaryTotalMax,
                'total_percentage' => $summaryTotalMax > 0
                    ? round(($summaryTotalObtained / $summaryTotalMax) * 100)
                    : 0,
            ];

            // -------------------------
            // FINAL RESPONSE
            // -------------------------
            $response = [
                'student' => [
                    'name' => $name,
                    'roll_number' => $studentDetails->roll_number,
                    'admission_no' => $studentDetails->admission_no,
                    'class_section' => $studentDetails->class_section->class->name.' '.$studentDetails->class_section->section->name,
                    'image' => $imageUrl,
                ],
                'summary' => $summary,
                'exam_history' => $examHistory,
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => config('app.debug') ? $e->getMessage() : trans('error_contact_admin'),
            ];
        }

        return response()->json($response);
    }
}
