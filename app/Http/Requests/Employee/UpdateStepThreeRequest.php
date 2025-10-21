<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStepThreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],

            'earning_structure' => [
                'required',
                Rule::in(['salary_based', 'hourly_rate']),
            ],

            // now required for BOTH cases, 0–2 decimals, > 0
            'rate' => [
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'       => 'employee',
            'earning_structure' => 'earning structure',
            'rate'              => 'rate',
        ];
    }
}
