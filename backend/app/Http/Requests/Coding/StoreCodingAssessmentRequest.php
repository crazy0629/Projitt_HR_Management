<?php

namespace App\Http\Requests\Coding;

use Illuminate\Foundation\Http\FormRequest;

class StoreCodingAssessmentRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'languages' => 'nullable|array|min:1',
            'languages.*' => 'string|max:50',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'time_limit_minutes' => 'required|integer|min:1|max:1440',
            'max_score' => 'required|integer|min:1|max:1000',
            'rubric' => 'nullable|array',
            'metadata' => 'nullable|array',
            'test_cases' => 'required_without:generate_with_ai|array|min:1',
            'test_cases.*.name' => 'nullable|string|max:255',
            'test_cases.*.input' => 'required_without:generate_with_ai|string',
            'test_cases.*.expected_output' => 'required_without:generate_with_ai|string',
            'test_cases.*.weight' => 'nullable|integer|min:1|max:100',
            'test_cases.*.is_hidden' => 'nullable|boolean',
            'test_cases.*.timeout_seconds' => 'nullable|integer|min:1|max:30',
            'generate_with_ai' => 'nullable|boolean',
            'generation_prompt' => 'required_if:generate_with_ai,true|string|max:2000',
        ];
    }
}
