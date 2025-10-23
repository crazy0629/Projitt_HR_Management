<?php

namespace App\Http\Requests\Coding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCodingAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('languages') && is_string($this->input('languages'))) {
            $this->merge([
                'languages' => array_filter(array_map('trim', explode(',', (string) $this->input('languages')))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'languages' => 'nullable|array|min:1',
            'languages.*' => 'string|max:50',
            'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
            'time_limit_minutes' => 'sometimes|integer|min:1|max:1440',
            'max_score' => 'sometimes|integer|min:1|max:1000',
            'rubric' => 'nullable|array',
            'metadata' => 'nullable|array',
            'test_cases' => 'nullable|array|min:1',
            'test_cases.*.id' => 'nullable|integer|exists:coding_test_cases,id',
            'test_cases.*.name' => 'nullable|string|max:255',
            'test_cases.*.input' => 'required_with:test_cases|string',
            'test_cases.*.expected_output' => 'required_with:test_cases|string',
            'test_cases.*.weight' => 'nullable|integer|min:1|max:100',
            'test_cases.*.is_hidden' => 'nullable|boolean',
            'test_cases.*.timeout_seconds' => 'nullable|integer|min:1|max:30',
            'test_cases.*._action' => 'nullable|in:create,update,delete',
        ];
    }
}
