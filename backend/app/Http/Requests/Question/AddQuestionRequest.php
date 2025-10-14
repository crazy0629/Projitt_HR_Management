<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddQuestionRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_name' => 'required|string|max:1000',
            'answer_type' => [
                'required',
                Rule::in(['short', 'long_detail', 'dropdown', 'checkbox', 'file_upload']),
            ],
            'options' => [
                'required_if:answer_type,dropdown,checkbox',
                'array',
            ],
            'options.*' => 'string|max:255',
            'is_required' => 'nullable|boolean',

            'tags'   => ['required', 'array'],
            'tags.*' => ['required', 'string', 'max:100'],
        ];
    }
}
