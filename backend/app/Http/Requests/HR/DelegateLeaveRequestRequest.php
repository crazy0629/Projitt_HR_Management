<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class DelegateLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delegate_to' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
