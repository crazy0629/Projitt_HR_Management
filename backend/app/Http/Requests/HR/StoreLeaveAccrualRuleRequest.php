<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveAccrualRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'frequency' => ['required', 'in:daily,weekly,monthly,quarterly,annually'],
            'amount' => ['required', 'numeric', 'min:0'],
            'max_balance' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_limit' => ['nullable', 'numeric', 'min:0'],
            'onboarding_waiting_period_days' => ['nullable', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'eligibility_criteria' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
