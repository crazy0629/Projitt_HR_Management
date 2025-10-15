<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteJobApplicantEducationRequest extends FormRequest
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
            'ids' => ['required', 'array'],
            'ids.*' => [
                'required',
                Rule::exists('job_applicant_educations', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
