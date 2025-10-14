<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWithFiltersQuestionRequest extends FormRequest
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
        $rules = [];

        if ($this->boolean('pagination')) {
            $rules['per_page'] = ['required', 'integer', 'min:1'];
            $rules['page'] = ['required', 'integer', 'min:1'];
        }

        $rules['name'] = ['nullable', 'string', 'max:1000'];

        $rules['answer_type'] = [
            'nullable',
            Rule::in(['short', 'long_detail', 'dropdown', 'checkbox', 'file_upload']),
        ];

        return $rules;
    }
}
