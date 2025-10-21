<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditJobQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],

            'question_ids' => ['required', 'array'],
            'question_ids.*' => [
                'integer',
                Rule::exists('questions', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
