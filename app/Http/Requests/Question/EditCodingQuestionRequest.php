<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class EditCodingQuestionRequest extends FormRequest
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
            'id' => 'required|exists:coding_questions,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'nullable|array|max:10',
            'language.*' => 'string|in:PHP,Java,Python,JavaScript,C#,C++,Ruby,Go,TypeScript,Swift',
            'total_point' => 'sometimes|required|numeric|min:0',
            'time_limit' => 'sometimes|required|numeric|min:1',
        ];
    }
}
