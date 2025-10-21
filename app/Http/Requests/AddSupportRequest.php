<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddSupportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow requests by default
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
            'full_name'                => 'required|string|max:150',
            'email'                    => 'required|email|max:150',
            'company_name'             => 'nullable|string|max:200',
            'question_category_id'     => 'nullable|integer',
            'question'                 => 'required|string',
            'preferred_response_method'=> ['required', 'integer', Rule::in([1, 2])],
            'media_id'                 => [
                'nullable',
                'integer',
                Rule::exists('media', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
