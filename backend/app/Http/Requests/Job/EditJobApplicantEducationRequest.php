<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditJobApplicantEducationRequest extends FormRequest
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
            'id' => [
                'required',
                Rule::exists('job_applicant_educations', 'id')->whereNull('deleted_at'),
            ],
            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],
            'applicant_id' => [
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'school' => ['required', 'string', 'max:200'],
            'degree_id' => [
                'required',
                Rule::exists('masters', 'id')->whereNull('deleted_at'),
            ],
            'field_of_study' => ['nullable', 'string', 'max:200'],
        ];
    }
}
