<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fields expected:
     * - employee_id (employees)
     * - alice_work_id (masters.type = work_location)
     * - department_id (masters.type = department)
     * - job_title_id (masters.type = job_title)
     * - manager_id (users) [optional]
     * - contract_start_date (date)
     */
    public function rules(): array
    {
        return [
            // Target employee
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],

            // Where will Alice work? (masters table)
            'alice_work_id' => [
                'required',
                'integer', ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],
            ],

            // Department (masters table)
            'department_id' => [
                'required',
                'integer', ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],
            ],

            // Job Title (masters table)
            'job_title_id' => [
                'required',
                'integer', ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],
            ],

            // Manager (optional)
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],

            // Contract Start Date
            'contract_start_date' => [
                'required',
                'date',
                // 'after_or_equal:today', // uncomment if you want only today/future
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'         => 'employee',
            'alice_work_id'       => 'work address',
            'department_id'       => 'department',
            'job_title_id'        => 'job title',
            'manager_id'          => 'manager',
            'contract_start_date' => 'contract start date',
        ];
    }

    public function messages(): array
    {
        return [
            'alice_work_id.exists' => 'The selected work address is invalid.',
            'department_id.exists' => 'The selected department is invalid.',
            'job_title_id.exists'  => 'The selected job title is invalid.',
        ];
    }
}
