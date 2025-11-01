<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in_at' => ['nullable', 'date'],
            'source' => ['nullable', 'string', Rule::in(['self', 'manual', 'biometric', 'api'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
