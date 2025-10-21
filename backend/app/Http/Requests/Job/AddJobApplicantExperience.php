<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddJobApplicantExperience extends FormRequest
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
            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],
            'applicant_id' => [
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'job_title' => ['required', 'string', 'max:150'],
            'company' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'is_currently_working' => ['required', 'boolean'],
            'role_description' => ['nullable', 'string'],
        ];
    }
}
