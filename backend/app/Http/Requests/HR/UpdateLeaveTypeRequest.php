<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveType = $this->route('leaveType') ?? $this->route('leave_type');
        $leaveTypeId = is_object($leaveType) ? $leaveType->getKey() : $leaveType;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('leave_types', 'name')->ignore($leaveTypeId),
            ],
            'code' => [
                'sometimes',
                'string',
                'max:60',
                'alpha_dash',
                Rule::unique('leave_types', 'code')->ignore($leaveTypeId),
            ],
            'description' => ['nullable', 'string'],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'default_allocation_days' => ['nullable', 'numeric', 'min:0'],
            'max_balance' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_limit' => ['nullable', 'numeric', 'min:0'],
            'accrual_method' => ['sometimes', 'in:none,monthly,annual'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
