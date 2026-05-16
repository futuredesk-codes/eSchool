<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionYearRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('session-year-create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',

            'start_date' => 'required|date_format:d-m-Y',
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',

            'fees_due_date' => 'required|date_format:d-m-Y',
            'fees_due_charges' => 'required|numeric|min:0|max:100',

            'free_app_use_date' => 'nullable|date_format:d-m-Y',

            // Fee installment flag
            'fees_installment' => 'nullable|in:0,1',

            // Transfer flags
            'transfer_class_subject'         => 'nullable|in:0,1',
            'transfer_class_teacher_subject' => 'nullable|in:0,1',
            'transfer_class_timetable'       => 'nullable|in:0,1',
            'transfer_exam_grades'           => 'nullable|in:0,1',
            'transfer_class_fee_type'        => 'nullable|in:0,1',
            'transfer_leave_settings'        => 'nullable|in:0,1',
            'transfer_semester'              => 'nullable|in:0,1',

            // Source session year: required only when any transfer option is checked
            'source_session_year_id' => [
                function (string $attr, $value, $fail) {
                    $anyTransfer = in_array('1', [
                        $this->transfer_class_subject         ?? '0',
                        $this->transfer_class_teacher_subject ?? '0',
                        $this->transfer_class_timetable       ?? '0',
                        $this->transfer_exam_grades           ?? '0',
                        $this->transfer_class_fee_type        ?? '0',
                        $this->transfer_leave_settings        ?? '0',
                        $this->transfer_semester              ?? '0',
                    ]);

                    if ($anyTransfer && empty($value)) {
                        $fail(trans('please_select_a_source_session_year_to_import_data_from'));
                    }
                },
                'nullable',
                'exists:session_years,id',
            ],

            // Semester data
            'semester_data'              => 'nullable|array',
            'semester_data.*.name'       => 'required_with:semester_data|string|max:255',
            'semester_data.*.start_date' => 'required_with:semester_data|date_format:d-m-Y',
            'semester_data.*.end_date'   => 'required_with:semester_data|date_format:d-m-Y',
        ];

        if ($this->fees_installment == '1') {
            $rules['installment_data'] = 'required|array|min:1';
            $rules['installment_data.*.name'] = 'required|string|max:255';
            $rules['installment_data.*.due_date'] = 'required|date_format:d-m-Y';
            $rules['installment_data.*.due_charges'] = 'required|numeric|min:0|max:100';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => trans('name_is_required'),
            'start_date.required' => trans('start_date_is_required'),
            'end_date.after_or_equal' => trans('end_date_must_be_after_start_date'),
            'fees_due_charges.max' => trans('fees_due_charges_cannot_exceed_100'),
            'installment_data.required' => trans('at_least_one_installment_required'),
        ];
    }
}
