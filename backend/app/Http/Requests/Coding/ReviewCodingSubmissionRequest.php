<?php

namespace App\Http\Requests\Coding;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCodingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score_adjustment' => 'nullable|numeric|min:-1000|max:1000',
            'comment' => 'nullable|string',
            'rubric_scores' => 'nullable|array',
        ];
    }
}
