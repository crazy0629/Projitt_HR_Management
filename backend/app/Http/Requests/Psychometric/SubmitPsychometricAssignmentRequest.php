<?php

namespace App\Http\Requests\Psychometric;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPsychometricAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responses' => 'required|array|min:1',
            'responses.*.question_id' => 'required|integer|exists:psychometric_questions,id',
            'responses.*.option_id' => 'nullable|integer|exists:psychometric_question_options,id',
            'responses.*.selected_option_ids' => 'nullable|array',
            'responses.*.selected_option_ids.*' => 'integer|exists:psychometric_question_options,id',
            'responses.*.numeric_response' => 'nullable|numeric',
            'responses.*.text_response' => 'nullable|string',
            'responses.*.time_spent_seconds' => 'nullable|integer|min:0|max:3600',
            'responses.*.metadata' => 'nullable|array',
            'metadata' => 'nullable|array',
            'force_submit' => 'nullable|boolean',
        ];
    }
}
