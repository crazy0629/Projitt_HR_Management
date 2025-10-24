<?php

namespace App\Http\Requests\Psychometric;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePsychometricTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('psychometric_tests', 'slug')],
            'category' => 'required|string|max:120',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'allowed_attempts' => 'nullable|integer|min:1|max:10',
            'randomize_questions' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'scoring_model' => 'nullable|array',
            'metadata' => 'nullable|array',
            'dimensions' => 'nullable|array|min:1',
            'dimensions.*.key' => 'required_with:dimensions|string|max:100',
            'dimensions.*.name' => 'required_with:dimensions|string|max:255',
            'dimensions.*.description' => 'nullable|string',
            'dimensions.*.weight' => 'nullable|numeric|min:0|max:10',
            'dimensions.*.metadata' => 'nullable|array',
            'questions' => 'required|array|min:1',
            'questions.*.reference_code' => 'nullable|string|max:120',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:likert,multiple_choice,multi_select,numeric,open_text',
            'questions.*.dimension_key' => 'nullable|string|max:100',
            'questions.*.weight' => 'nullable|numeric|min:0|max:100',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.randomize_options' => 'nullable|boolean',
            'questions.*.base_order' => 'nullable|integer|min:0|max:1000',
            'questions.*.metadata' => 'nullable|array',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.label' => 'required_with:questions.*.options|string|max:255',
            'questions.*.options.*.value' => 'nullable|string|max:255',
            'questions.*.options.*.score' => 'nullable|numeric|min:-100|max:100',
            'questions.*.options.*.weight' => 'nullable|numeric|min:0|max:100',
            'questions.*.options.*.position' => 'nullable|integer|min:0|max:1000',
            'questions.*.options.*.metadata' => 'nullable|array',
        ];
    }
}
