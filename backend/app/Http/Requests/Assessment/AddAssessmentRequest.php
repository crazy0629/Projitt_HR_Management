<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('type_id')) {
            $this->merge([
                'type_id' => (int) $this->input('type_id'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $questionTable = $this->input('type_id') === 2 ? 'coding_questions' : 'questions';

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_duration' => 'required|numeric|min:1',
            'type_id' => 'required|in:1,2',
            'points' => 'required|numeric|min:0',

            'questions' => 'nullable|array|min:1',
            'questions.*.question_id' => [
                'required_with:questions',
                Rule::exists($questionTable, 'id'),
            ],
            'questions.*.point' => 'required_with:questions|numeric|min:0',
        ];
    }
}
