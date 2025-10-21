<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class ListWithFiltersCodingQuestionRequest extends FormRequest
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
        $rules = [
            'title' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:30',
        ];

        if ($this->query('pagination')) {
            $rules['per_page'] = 'required|integer|min:1';
            $rules['page'] = 'required|integer|min:1';
        }

        return $rules;
    }
}
