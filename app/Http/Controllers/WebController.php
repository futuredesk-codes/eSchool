<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use App\Models\Faq;
use App\Models\User;
use App\Models\Event;
use App\Models\Media;
use App\Models\Slider;
use App\Models\Holiday;
use App\Models\Parents;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\Students;
use App\Models\ContactUs;
use App\Models\FormField;
use App\Models\MediaFile;
use App\Models\WebSetting;
use App\Models\ClassSchool;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\EducationalProgram;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WebController extends Controller
{
    public function index()
    {
        $eprograms = null;
        $images = null;
        $videos = null;
        $news = null;
        $faqs = null;


        $date = Carbon::now();
        $settings = getSettings();
        $sliders = Slider::whereIn('type', [2, 3])->get();
        $about = WebSetting::where('name', 'about_us')->where('status', 1)->first();
        $event = WebSetting::where('name', 'events')->where('status', 1)->first();
        $program = WebSetting::where('name', 'programs')->where('status', 1)->first();
        $photo = WebSetting::where('name', 'photos')->where('status', 1)->first();
        $video = WebSetting::where('name', 'videos')->where('status', 1)->first();
        $faq = WebSetting::where('name', 'faqs')->where('status', 1)->first();
        $app = WebSetting::where('name', 'app')->where('status', 1)->first();
        if ($program) {
            $eprograms = EducationalProgram::get();
        }

        if ($event) {
            $events = Event::with('multipleEvent')->where(function ($query) use ($date) {
                $query->where('start_date', '>=', $date)->orWhere('end_date', '>=', $date)
                    ->orWhereDate('start_date', '=', $date)->orWhere('end_date', '=', $date);
            })->get();

            $holiday = Holiday::where('date', '>=', $date)->get();

            $collections = $events->merge($holiday);
            $sortedCollection = $collections->sortby('date')->sortby('start_date');
            $news = $sortedCollection->take(6);
        }

        if ($photo) {
            $images = Media::where('type', 1)->get();
        }

        if ($video) {
            $videos = Media::where('type', 2)->get();
        }

        if ($faq) {
            $faqs = Faq::where('status', 1)->get();
        }


        return view('web.index', compact('settings', 'sliders', 'about', 'event', 'program', 'photo', 'video', 'faq', 'app', 'eprograms', 'news', 'images', 'videos', 'faqs'));
    }

    public function about()
    {
        $teachercount = 0;
        $studentcount = 0;
        $teachers = null;
        $settings = getSettings();
        $about = WebSetting::where('name', 'about_us')->where('status', 1)->first();
        $whoweare = WebSetting::where('name', 'who_we_are')->where('status', 1)->first();
        $teacher = WebSetting::where('name', 'teacher')->where('status', 1)->first();

        if ($teacher) {
            $subjectData = [];
            $teachers = Teacher::with('user:id,first_name,last_name,image')->get();
            $teachercount  = Teacher::count();
            $studentcount = Students::count();
        }


        return view('web.about-us', compact('settings', 'about', 'whoweare', 'teacher', 'teachers', 'teachercount', 'studentcount'));
    }

    public function contact_us()
    {
        $settings = getSettings();
        $question = WebSetting::where('name', 'question')->where('status', 1)->first();
        return view('web.contact-us', compact('settings', 'question'));
    }

    public function photo()
    {
        $settings = getSettings();
        $images = null;
        $photo = WebSetting::where('name', 'photos')->where('status', 1)->first();
        if ($photo) {
            $images = Media::where('type', 1)->get();
        }

        return view('web.photos', compact('settings', 'photo', 'images'));
    }

    public function video()
    {
        $settings = getSettings();
        $videos = null;

        $video = WebSetting::where('name', 'videos')->where('status', 1)->first();
        $videos = Media::where('type', 2)->get();

        return view('web.videos', compact('settings', 'video', 'videos'));
    }

    public function photo_details($id)
    {
        $settings = getSettings();

        $images = MediaFile::where('media_id', $id)->get();
        return view('web.photos-details', compact('settings', 'images'));
    }

    public function contact_us_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $date = Carbon::now();
            $data = new ContactUs();
            $data->first_name = $request->first_name;
            $data->last_name = $request->last_name;
            $data->email = $request->email;
            $data->phone = $request->phone;
            $data->message = $request->message;
            $data->date = $date;
            $data->save();
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

    public function registrationIndex()
    {
        $settings = getSettings();
        $registration = null;
        $classSchools = ClassSchool::with('medium', 'streams')->get();
        $studentFields = FormField::where('for', 4)->orderBy('rank', 'ASC')->get();
        $category = Category::where('status', 1)->get();
        $data = getSettings('session_year');
        $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
        $get_student = Students::withTrashed()->select('id')->latest('id')->pluck('id')->first();
        $admission_no = $session_year . ($get_student + 1);
        $admission_date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));
        $registration = WebSetting::where('name', 'registration')->where('status', 1)->first();

        if (is_null($registration)) {
            return redirect('error-page');
        } else {
            return view('web.registration', compact('settings', 'registration', 'classSchools', 'category', 'admission_no', 'studentFields', 'admission_date'));
        }
    }

    public function studentRegistration(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'mobile' => 'nullable|numeric|regex:/^[0-9]{7,16}$/',
                'image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                'dob' => 'required',
                'class_id' => 'required',
                'category_id' => 'required',
                // admission_no is generated server-side, not from user input
                'admission_date' => 'required',
                'current_address' => 'required',
                'permanent_address' => 'required',

                // NEW LOGIC
                'parent_guardian_type' => 'required|in:Parent,Guardian',

                // If Guardian is selected, validate guardian fields
                'guardian_first_name' => 'required_if:parent_guardian_type,Guardian',
                'guardian_last_name' => 'required_if:parent_guardian_type,Guardian',
                'guardian_email' => 'required_if:parent_guardian_type,Guardian|email',
                'guardian_mobile' => 'required_if:parent_guardian_type,Guardian|numeric',
                'guardian_image' => 'required_if:parent_guardian_type,Guardian|image',

                'g-recaptcha-response' => 'required_if:recaptcha_status,1|nullable'
            ],
            [
                'mobile.regex' => __('The mobile number must be a length of 7 to 15 digits.'),
                'g-recaptcha-response.required_if' => 'The Re-captcha field is required.',
            ]
        );

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            // Generate admission_no server-side to prevent client-side tampering
            $sessionYearData = getSettings('session_year');
            $session_year = SessionYear::select('name')->where('id', $sessionYearData['session_year'])->pluck('name')->first();
            $get_student = Students::withTrashed()->select('id')->latest('id')->pluck('id')->first();
            $admission_no = $session_year . ($get_student + 1);

            $parentRole = Role::where('name', 'Parent')->first();
            $studentRole = Role::where('name', 'Student')->first();

            //Add Father in User and Parent table data
            if ($request->parent_guardian_type === 'Parent') {
                if (!intval($request->father_email)) {
                    $request->validate([
                        'father_email' => 'required|email|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                        'father_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                if (!intval($request->mother_email)) {
                    $request->validate([
                        'mother_email' => 'required|email|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                        'mother_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }
                $fatherParent = Parents::with('user')->where('email', $request->father_email)->first();
                $motherParent = Parents::with('user')->where('email', $request->mother_email)->first();
                $father_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->father_dob)));

                if ($fatherParent) {
                    $father_parent_id = $fatherParent->id;
                } else {
                    $father_email = $request->father_email;
                    $father_image = $request->file('father_image');
                    // made file name with combination of current time
                    $file_name = time() . '-' . $father_image->getClientOriginalName();
                    //made file path to store in database
                    $file_path = 'parents/' . $file_name;
                    //resized image
                    resizeImage($father_image);
                    //stored image to storage/public/parents folder
                    $destinationPath = storage_path('app/public/parents');
                    $father_image->move($destinationPath, $file_name);

                    $father_user_array = array(
                        'image' => $file_path,
                        'password' => Hash::make($father_plaintext_password),
                        'first_name' => $request->father_first_name,
                        'last_name' => $request->father_last_name,
                        'gender' => 'Male',
                        'email' => $father_email,
                        'mobile'  => $request->father_mobile,
                        'dob' => date('Y-m-d', strtotime($request->father_dob)),
                        'status' => 0,
                    );

                    $father_user = User::create($father_user_array);
                    $father_user->assignRole($parentRole);

                    $father_parent_array = array(
                        'user_id' => $father_user->id,
                        'first_name' => $request->father_first_name,
                        'last_name' => $request->father_last_name,
                        'gender' => 'Male',
                        'email' => $father_email,
                        'mobile' => $request->father_mobile,
                        'image' => $father_user->getRawOriginal('image'),
                        'dob'  => date('Y-m-d', strtotime($request->father_dob)),
                        'occupation' => $request->father_occupation,
                    );

                    $father_parent = Parents::create($father_parent_array);
                    $father_parent_id = $father_parent->id;
                }

                //Add Mother in User and Parent table data
                $mother_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->mother_dob)));
                if ($motherParent) {
                    $mother_parent_id = $motherParent->id;
                } else {
                    $mother_email = $request->mother_email;

                    $mother_image = $request->file('mother_image');
                    // made file name with combination of current time
                    $file_name = time() . '-' . $mother_image->getClientOriginalName();
                    //made file path to store in database
                    $file_path = 'parents/' . $file_name;
                    //resized image
                    resizeImage($mother_image);
                    //stored image to storage/public/parents folder
                    $destinationPath = storage_path('app/public/parents');
                    $mother_image->move($destinationPath, $file_name);

                    $mother_user_array = array(
                        'image' => $file_path,
                        'password' => Hash::make($mother_plaintext_password),
                        'first_name' => $request->mother_first_name,
                        'last_name' => $request->mother_last_name,
                        'gender' => 'Female',
                        'email' => $mother_email,
                        'mobile'  => $request->mother_mobile,
                        'dob' => date('Y-m-d', strtotime($request->mother_dob)),
                        'status' => 0,
                    );

                    $mother_user = User::create($mother_user_array);
                    $mother_user->assignRole($parentRole);

                    $mother_parent_array = array(
                        'user_id' => $mother_user->id,
                        'first_name' => $request->mother_first_name,
                        'last_name' => $request->mother_last_name,
                        'gender' => 'Female',
                        'email' => $mother_email,
                        'mobile' => $request->mother_mobile,
                        'image' => $mother_user->getRawOriginal('image'),
                        'dob'  => date('Y-m-d', strtotime($request->mother_dob)),
                        'occupation' => $request->mother_occupation,
                    );

                    $mother_parent = Parents::create($mother_parent_array);
                    $mother_parent_id = $mother_parent->id;
                }
            } else {
                $father_parent_id = null;
                $mother_parent_id = null;
            }


            if ($request->parent_guardian_type === 'Guardian') {
                $request->validate([
                    'guardian_email' => 'required|email',
                    'guardian_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                ]);

                $guardianParent = Parents::with('user')->where('email', $request->guardian_email)->first();
                if ($guardianParent) {
                    $guardian_parent_id = $guardianParent->id;
                } else {
                    $guardian_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->guardian_dob)));

                    $guardian_image = $request->file('guardian_image');
                    $file_name = time() . '-' . $guardian_image->getClientOriginalName();
                    $file_path = 'parents/' . $file_name;

                    resizeImage($guardian_image);
                    $destinationPath = storage_path('app/public/parents');
                    $guardian_image->move($destinationPath, $file_name);

                    $guardian_user_array = [
                        'image' => $file_path,
                        'password' => Hash::make($guardian_plaintext_password),
                        'first_name' => $request->guardian_first_name,
                        'last_name' => $request->guardian_last_name,
                        'gender' => $request->guardian_gender,
                        'email' => $request->guardian_email,
                        'mobile' => $request->guardian_mobile,
                        'dob' => date('Y-m-d', strtotime($request->guardian_dob)),
                        'status' => 0,
                    ];

                    $guardian_user = User::create($guardian_user_array);
                    $guardian_user->assignRole($parentRole);

                    $guardian_parent_array = [
                        'user_id' => $guardian_user->id,
                        'first_name' => $request->guardian_first_name,
                        'last_name' => $request->guardian_last_name,
                        'gender' => $request->guardian_gender,
                        'email' => $request->guardian_email,
                        'mobile' => $request->guardian_mobile,
                        'image' => $guardian_user->getRawOriginal('image'),
                        'dob' => date('Y-m-d', strtotime($request->guardian_dob)),
                        'occupation' => $request->guardian_occupation,
                    ];

                    $guardian_parent = Parents::create($guardian_parent_array);
                    $guardian_parent_id = $guardian_parent->id;
                }
            } else {
                $guardian_parent_id = null;
            }
            //Create Student User First

            $child_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));

            $student_image = $request->file('image');
            // made file name with combination of current time
            $file_name = time() . '-' . $student_image->getClientOriginalName();
            //made file path to store in database
            $file_path = 'students/' . $file_name;
            //resized image
            resizeImage($student_image);
            //stored image to storage/public/students folder
            $destinationPath = storage_path('app/public/students');
            $student_image->move($destinationPath, $file_name);


            $user_data = array(
                'image' => $file_path,
                'password' => Hash::make($child_plaintext_password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $admission_no, // Server-generated GR Number used as email
                'gender' => $request->gender,
                'mobile' => $request->mobile,
                'dob' => date('Y-m-d', strtotime($request->dob)),
                'status' => 0,
                'current_address' => $request->current_address,
                'permanent_address' => $request->permanent_address,
            );

            // Create a new user
            $user = User::create($user_data);
            $user->assignRole($studentRole);


            // Student dynamic fields
            $studentFields = FormField::where('for', 4)->orderBy('rank', 'ASC')->get();
            $data = array();
            $status = 0;
            $dynamic_data = [];
            foreach ($studentFields as $form_field) {
                // INPUT TYPE CHECKBOX
                if ($form_field->type == 'checkbox') {
                    if ($status == 0) {
                        $data[] = $request->input('checkbox', []);
                        $status = 1;
                    }
                } else if ($form_field->type == 'file') {
                    // INPUT TYPE FILE
                    $get_file = '';
                    $field = str_replace(" ", "_", $form_field->name);
                    if ($dynamic_data && count($dynamic_data) > 0) {
                        foreach ($dynamic_data as $field_data) {
                            if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                $get_file = $field_data[$field];
                            }
                        }
                    }
                    $hidden_file_name = 'file-' . $field;

                    if ($request->hasFile($field)) {
                        if ($get_file) {
                            Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                        }
                        $data[] = [str_replace(" ", "_", $form_field->name) => $request->file($field)->store('student', 'public')];
                    } else {
                        if ($request->$hidden_file_name) {
                            $data[] = [str_replace(" ", "_", $form_field->name) => $request->$hidden_file_name];
                        }
                    }
                } else {
                    $field = str_replace(" ", "_", $form_field->name);
                    $data[] = [str_replace(" ", "_", $form_field->name) => $request->$field];
                }
            }

            $student_data_array = [
                'user_id' => $user->id,
                'class_id' => $request->class_id,
                'application_type' => 'online',
                'category_id' => $request->category_id,
                'admission_no' => $admission_no,
                'admission_date' => date('Y-m-d', strtotime($request->admission_date)),
                'father_id' => $father_parent_id,
                'mother_id' => $mother_parent_id,
                'guardian_id' => $guardian_parent_id,
                'dynamic_fields' => json_encode($data),
            ];

            Students::create($student_data_array);

            $response = [
                'error' => false,
                'message' => trans('user_registered_successfully')
            ];
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }

    public function errorPage()
    {
        return view('web.error-404');
    }
}
