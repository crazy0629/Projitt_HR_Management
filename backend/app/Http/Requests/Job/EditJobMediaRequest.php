<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditJobMediaRequest extends FormRequest
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

            'media_ids' => ['required', 'array'],
            'media_ids.*' => [
                'integer',
                Rule::exists('media', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
