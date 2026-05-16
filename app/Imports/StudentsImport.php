<?php

declare(strict_types=1);

namespace App\Imports;

use Throwable;
use App\Models\User;
use App\Models\Parents;
use App\Models\Category;
use App\Models\Students;
use App\Models\SessionYear;
use App\Models\ClassSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StudentsImport extends DefaultValueBinder implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation {
    public $class_section_id;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function  __construct($class_section_id) {
        $this->class_section_id = $class_section_id;
    }

    public function prepareForValidation(array $row, int $index): array {
        // convert empty strings to null (so 'nullable' works)
        foreach ($row as $k => $v) {
            if (is_string($v)) {
                // Remove leading or trailing single quotes
                $row[$k] = trim($v, "'");
            }
            if ($v === '') $row[$k] = null;
        }

        // optional: force father_mobile to null if parents != 'yes'
        if ($row['parents'] == 'no') {
            $row['father_mobile'] = null;
        }

        return $row;
    }

    public function rules(): array {
        return [
            '*.first_name' => 'required',
            '*.last_name' => 'required',
            '*.mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
            '*.gender' => 'required',
            '*.dob' => 'required',
            '*.category' => 'required',
            '*.caste' => 'nullable',
            '*.religion' => 'nullable',
            '*.admission_date' => 'required',
            '*.blood_group' => 'required',
            '*.current_address' => 'required',
            '*.permanent_address' => 'required',
            '*.parents' => 'required_without:guardian',

            '*.father_email' => 'required_if:parents,yes',
            '*.father_first_name' => 'required_if:parents,yes',
            '*.father_last_name' => 'required_if:parents,yes',
            '*.father_mobile' => 'required_if:parents,yes|nullable|numeric|regex:/^[0-9]{7,16}$/',
            '*.father_dob' => 'required_if:parents,yes',
            '*.father_occupation' => 'required_if:parents,yes',

            '*.mother_email' => 'required_if:parents,yes',
            '*.mother_first_name' => 'required_if:parents,yes',
            '*.mother_last_name' => 'required_if:parents,yes',
            '*.mother_mobile' => 'required_if:parents,yes|nullable|numeric|regex:/^[0-9]{7,16}$/',
            '*.mother_dob' => 'required_if:parents,yes',
            '*.mother_occupation' => 'required_if:parents,yes',

            '*.guardian' => 'required_without:parents',
            '*.guardian_email' => 'required_if:guardian,yes',
            '*.guardian_first_name' => 'required_if:guardian,yes',
            '*.guardian_last_name' => 'required_if:guardian,yes',
            '*.guardian_mobile' => 'required_if:guardian,yes|nullable|numeric|regex:/^[0-9]{7,16}$/',
            '*.guardian_dob' => 'required_if:guardian,yes',
            '*.guardian_occupation' => 'required_if:guardian,yes',
        ];
    }

    // Date conversion is now handled in the collection method

    public function collection(Collection $rows) {
        // Convert Excel date integers to proper date strings
        $rows = $rows->map(function ($row) {
            $date_columns = ['dob', 'father_dob', 'mother_dob', 'guardian_dob', 'admission_date'];

            foreach ($date_columns as $column) {
                if (isset($row[$column]) && is_numeric($row[$column])) {
                    // Convert Excel serial date to DateTime object then format
                    try {
                        $row[$column] = Date::excelToDateTimeObject($row[$column])->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If conversion fails, keep original value
                        $row[$column] = $row[$column];
                    }
                }
            }

            return $row;
        });

        try {
            $father_user = new User();
            $mother_user = new User();
            $parentRole = Role::where('name', 'Parent')->first();
            $studentRole = Role::where('name', 'Student')->first();
            DB::beginTransaction();
            foreach ($rows as $row) {
                if ($row['parents'] == "yes") {
                    if (isset($row['father_email']) && isset($row['mother_email'])) {
                        $father_email_check = Parents::select('email')->where('email', $row['father_email'])->count();
                        $father_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($row['father_dob'])));
                        if ($father_email_check == 0) {
                            $father_user = new User();
                            $father_user->first_name = $row['father_first_name'];
                            $father_user->last_name = $row['father_last_name'];
                            $father_user->email = $row['father_email'];
                            $father_user->password = Hash::make($father_plaintext_password);
                            $father_user->mobile = $row['father_mobile'];
                            $father_user->image = 'dummy_logo.jpg';
                            $father_user->dob = date('Y-m-d', strtotime($row['father_dob']));
                            $father_user->gender = 'Male';
                            $father_user->save();
                            $father_user->assignRole($parentRole);

                            $father_parent = new Parents();
                            $father_parent->user_id = $father_user->id;
                            $father_parent->first_name = $row['father_first_name'];
                            $father_parent->last_name = $row['father_last_name'];
                            $father_parent->image = 'dummy_logo.jpg';
                            $father_parent->occupation = $row['father_occupation'];
                            $father_parent->mobile = $row['father_mobile'];
                            $father_parent->email = $row['father_email'];
                            $father_parent->dob = date('Y-m-d', strtotime($row['father_dob']));
                            $father_parent->gender = 'Male';
                            $father_parent->dynamic_fields = "";
                            $father_parent->save();
                            $father_parent_id = $father_parent->id;
                            $father_email = $row['father_email'];
                            $father_name = $row['father_first_name'];
                        } else {
                            $father = Parents::select('id', 'first_name')->where('email', $row['father_email'])->first();
                            $father_parent_id = $father->id;
                            $father_name = $father->first_name;
                            $father_email = $row['father_email'];
                        }
                        $mother_email_check = Parents::select('email')->where('email', $row['mother_email'])->count();
                        $mother_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($row['mother_dob'])));
                        if ($mother_email_check == 0) {
                            $mother_user = new User();
                            $mother_user->image = 'dummy_logo.jpg';
                            $mother_user->password = Hash::make($mother_plaintext_password);
                            $mother_user->first_name = $row['mother_first_name'];
                            $mother_user->last_name = $row['mother_last_name'];
                            $mother_user->email = $row['mother_email'];
                            $mother_user->mobile = $row['mother_mobile'];
                            $mother_user->dob = date('Y-m-d', strtotime($row['mother_dob']));
                            $mother_user->gender = 'Female';
                            $mother_user->save();
                            $mother_user->assignRole($parentRole);

                            $mother_parent = new Parents();
                            $mother_parent->user_id = $mother_user->id;
                            $mother_parent->first_name = $row['mother_first_name'];
                            $mother_parent->last_name = $row['mother_last_name'];
                            $mother_parent->image = 'dummy_logo.jpg';
                            $mother_parent->occupation = $row['mother_occupation'];
                            $mother_parent->mobile = $row['mother_mobile'];
                            $mother_parent->email = $row['mother_email'];
                            $mother_parent->dob = date('Y-m-d', strtotime($row['mother_dob']));
                            $mother_parent->gender = 'Female';
                            $mother_parent->dynamic_fields = "";
                            $mother_parent->save();
                            $mother_parent_id = $mother_parent->id;
                            $mother_email = $row['mother_email'];
                            $mother_name = $row['mother_first_name'];
                        } else {
                            $mother = Parents::select('id', 'first_name')->where('email', $row['mother_email'])->first();
                            $mother_parent_id = $mother->id;
                            $mother_name = $mother->first_name;
                            $mother_email = $row['mother_email'];
                        }
                    }
                } else {
                    $father_parent_id = null;
                    $mother_parent_id = null;
                }
                if ($row['guardian'] == "yes") {
                    if (isset($row['guardian_email'])) {
                        $guardian_email_check = Parents::select('email')->where('email', $row['guardian_email'])->count();
                        $guardian_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($row['guardian_dob'])));
                        if ($guardian_email_check == 0) {
                            $guardian_user = new User();
                            $guardian_user->image = 'dummy_logo.jpg';
                            $guardian_user->password = Hash::make($guardian_plaintext_password);
                            $guardian_user->first_name = $row['guardian_first_name'];
                            $guardian_user->last_name = $row['guardian_last_name'];
                            $guardian_user->email = $row['guardian_email'];
                            $guardian_user->mobile = $row['guardian_mobile'];
                            $guardian_user->dob = date('Y-m-d', strtotime($row['guardian_dob']));
                            $guardian_user->gender = $row['guardian_gender'];
                            $guardian_user->save();
                            $guardian_user->assignRole($parentRole);

                            $guardian_parent = new Parents();
                            $guardian_parent->user_id = $guardian_user->id;
                            $guardian_parent->first_name = $row['guardian_first_name'];
                            $guardian_parent->last_name = $row['guardian_last_name'];
                            $guardian_parent->image = 'dummy_logo.jpg';
                            $guardian_parent->occupation = $row['guardian_occupation'];
                            $guardian_parent->mobile = $row['guardian_mobile'];
                            $guardian_parent->email = $row['guardian_email'];
                            $guardian_parent->dob = date('Y-m-d', strtotime($row['guardian_dob']));
                            $guardian_parent->gender = $row['guardian_gender'];
                            $guardian_parent->dynamic_fields = "";
                            $guardian_parent->save();
                            $guardian_parent_id = $guardian_parent->id;
                            $guardian_email = $row['guardian_email'];
                            $guardian_name = $row['guardian_first_name'];
                        } else {
                            $guardian = Parents::select('id', 'first_name')->where('email', $row['guardian_email'])->first();
                            $guardian_parent_id = $guardian->id;
                            $guardian_name = $guardian->first_name;
                            $guardian_email = $row['guardian_email'];
                        }
                    }
                } else {
                    $guardian_parent_id = null;
                }

                $data = getSettings('session_year');
                if (empty($data)) {
                    throw new \RuntimeException(trans('please_set_session_year_first'));
                } else {
                    $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
                    $admission_no = $this->generateUniqueAdmissionNumber($session_year);
                }
                $category_id = Category::where('name', $row['category'])->pluck('id')->first();
                if ($category_id) {
                    $category_id = $category_id;
                } else {
                    $category = new Category();
                    $category->name = $row['category'];
                    $category->save();
                    $category_id = $category->id;
                }
                $user = new User();
                $child_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($row['dob'])));
                $user->password = Hash::make($child_plaintext_password);
                $user->first_name = $row['first_name'];
                $user->last_name = $row['last_name'];
                $user->email = $admission_no;
                $user->gender = $row['gender'];
                $user->mobile = $row['mobile'];
                $user->image = 'dummy_logo.jpg';
                $user->dob = date('Y-m-d', strtotime($row['dob']));
                $user->current_address = $row['current_address'];
                $user->permanent_address = $row['permanent_address'];
                $user->save();
                $user->assignRole($studentRole);

                $student = new Students();
                $student->user_id = $user->id;
                $student->class_section_id = $this->class_section_id;
                $student->category_id = $category_id;
                $student->admission_no = $admission_no;
                if (isset($row['caste']) && !empty($row['caste'])) {
                    $student->caste = $row['caste'];
                }
                if (isset($row['religion']) && !empty($row['religion'])) {
                    $student->religion = $row['religion'];
                }
                $student->admission_date = date('Y-m-d', strtotime($row['admission_date']));
                $student->blood_group = $row['blood_group'];
                $student->height = $row['height'];
                $student->weight = $row['wieght'];
                $student->father_id = $father_parent_id;
                $student->mother_id = $mother_parent_id;
                $student->guardian_id = $guardian_parent_id;
                $student->dynamic_fields = "";
                $student->save();

                //Send User Credentials via Email
                $settings = getSettings();
                $school_name = $settings['school_name'];
                $school_email = $settings['school_email'];
                $school_contact = $settings['school_phone'];


                if ($this->class_section_id) {
                    $classSection = ClassSection::where('id', $this->class_section_id)->with('class.medium', 'class.streams', 'section')->first();
                    $class_section_name = $classSection->class->name . ' - ' . $classSection->section->name . ' ' . $classSection->class->medium->name . '  ' . ($classSection->class->streams->name ?? '');
                }

                if ($row['parents'] == "yes") {
                    $father_data = [
                        'subject' => 'Welcome to ' . $school_name,
                        'email' => $father_email,
                        'name' => ' ' . $father_name,
                        'username' => ' ' . $father_email,
                        'password' => ' ' . $father_plaintext_password,
                        'child_name' => ' ' . $row['first_name'] . ' ' . $row['last_name'],
                        'child_grnumber' => ' ' . $admission_no,
                        'child_password' => ' ' . $child_plaintext_password,
                        'type' => 'application_accept',
                        'class_name' => $class_section_name,
                        'school_name' => $school_name,
                        'school_email' => $school_email,
                        'school_contact' => $school_contact
                    ];
                    Mail::send('students.email', $father_data, function ($message) use ($father_data) {
                        $message->to($father_data['email'])->subject($father_data['subject']);
                    });

                    $mother_data = [
                        'subject' => 'Welcome to ' .    $school_name,
                        'email' => $mother_email,
                        'name' => ' ' . $mother_name,
                        'username' => ' ' . $mother_email,
                        'password' => ' ' . $mother_plaintext_password,
                        'child_name' => ' ' . $row['first_name'] . ' ' . $row['last_name'],
                        'child_grnumber' => ' ' . $admission_no,
                        'child_password' => ' ' . $child_plaintext_password,
                        'type' => 'application_accept',
                        'class_name' => $class_section_name,
                        'school_name' => $school_name,
                        'school_email' => $school_email,
                        'school_contact' => $school_contact
                    ];
                    Mail::send('students.email', $mother_data, function ($message) use ($mother_data) {
                        $message->to($mother_data['email'])->subject($mother_data['subject']);
                    });
                } else {
                    $guardian_data = [
                        'subject' => 'Welcome to ' . $school_name,
                        'email' => $guardian_email,
                        'name' => ' ' . $guardian_name,
                        'username' => ' ' . $guardian_email,
                        'password' => ' ' . $guardian_plaintext_password,
                        'child_name' => ' ' . $row['first_name'] . ' ' . $row['last_name'],
                        'child_grnumber' => ' ' . $admission_no,
                        'child_password' => ' ' . $child_plaintext_password,
                        'type' => 'application_accept',
                        'class_name' => $class_section_name,
                        'school_name' => $school_name,
                        'school_email' => $school_email,
                        'school_contact' => $school_contact
                    ];
                    Mail::send('students.email', $guardian_data, function ($message) use ($guardian_data) {
                        $message->to($guardian_data['email'])->subject($guardian_data['subject']);
                    });
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique admission number recursively
     *
     * @param string $sessionYear
     * @param int $counter
     * @return string
     */
    private function generateUniqueAdmissionNumber($sessionYear, $counter = 1) {
        $admissionNumber = $sessionYear . $counter;

        // Check if admission number already exists
        $exists = Students::withTrashed()
            ->where('admission_no', $admissionNumber)
            ->exists();

        if ($exists) {
            // If exists, increment counter and try again recursively
            return $this->generateUniqueAdmissionNumber($sessionYear, $counter + 1);
        }

        return $admissionNumber;
    }
}
