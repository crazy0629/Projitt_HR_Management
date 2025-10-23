<?php

namespace App\Http\Requests\Coding;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCodingSolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => 'required|string|max:50',
            'source_code' => 'required|string',
            'metadata' => 'nullable|array',
        ];
    }
}
