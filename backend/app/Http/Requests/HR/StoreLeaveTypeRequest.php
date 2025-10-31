<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:leave_types,name'],
            'code' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:leave_types,code'],
            'description' => ['nullable', 'string'],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'default_allocation_days' => ['nullable', 'numeric', 'min:0'],
            'max_balance' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_limit' => ['nullable', 'numeric', 'min:0'],
            'accrual_method' => ['required', 'in:none,monthly,annual'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
