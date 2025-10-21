<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditJobApplicantCertificateRequest extends FormRequest
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
                Rule::exists('job_applicant_certificates', 'id')->whereNull('deleted_at'),
            ],
            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],
            'applicant_id' => [
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:200'],
            'number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date'],
        ];
    }
}
