<?php

namespace App\Http\Requests\Psychometric;

use App\Models\Psychometric\PsychometricTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePsychometricTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $test = $this->route('test');
        if ($test instanceof PsychometricTest) {
            $test = $test->id;
        }

        return [
            'title' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('psychometric_tests', 'slug')->ignore($test)],
            'category' => 'sometimes|string|max:120',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'allowed_attempts' => 'nullable|integer|min:1|max:10',
            'randomize_questions' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'scoring_model' => 'nullable|array',
            'metadata' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'dimensions.*.id' => 'nullable|integer|exists:psychometric_dimensions,id',
            'dimensions.*.key' => 'nullable|string|max:100',
            'dimensions.*.name' => 'nullable|string|max:255',
            'dimensions.*.description' => 'nullable|string',
            'dimensions.*.weight' => 'nullable|numeric|min:0|max:10',
            'dimensions.*.metadata' => 'nullable|array',
            'dimensions.*._action' => 'nullable|in:create,update,delete',
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:psychometric_questions,id',
            'questions.*.reference_code' => 'nullable|string|max:120',
            'questions.*.question_text' => 'nullable|string',
            'questions.*.question_type' => 'nullable|in:likert,multiple_choice,multi_select,numeric,open_text',
            'questions.*.dimension_key' => 'nullable|string|max:100',
            'questions.*.dimension_id' => 'nullable|integer|exists:psychometric_dimensions,id',
            'questions.*.weight' => 'nullable|numeric|min:0|max:100',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.randomize_options' => 'nullable|boolean',
            'questions.*.base_order' => 'nullable|integer|min:0|max:1000',
            'questions.*.metadata' => 'nullable|array',
            'questions.*._action' => 'nullable|in:create,update,delete',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable|integer|exists:psychometric_question_options,id',
            'questions.*.options.*.label' => 'nullable|string|max:255',
            'questions.*.options.*.value' => 'nullable|string|max:255',
            'questions.*.options.*.score' => 'nullable|numeric|min:-100|max:100',
            'questions.*.options.*.weight' => 'nullable|numeric|min:0|max:100',
            'questions.*.options.*.position' => 'nullable|integer|min:0|max:1000',
            'questions.*.options.*.metadata' => 'nullable|array',
            'questions.*.options.*._action' => 'nullable|in:create,update,delete',
        ];
    }
}
