<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditJobApplicantContactInfoRequest extends FormRequest
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
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'middle_name'   => 'nullable|string|max:100',

            'address'       => 'required|string|max:200',
            'city'          => 'required|string|max:100',
            'state'         => 'required|string|max:100',
            'zip_code'      => 'required|string|max:20',
            'country'       => 'required|string|max:100',

            'contact_code'  => 'required|string|max:10',
            'contact_no'    => 'required|string|max:20',

            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->where(fn($query) =>
                    $query->whereNull('deleted_at')
                          ->where('status', 'open')
                ),
            ],

            'applicant_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($query) =>
                    $query->whereNull('deleted_at')
                ),
            ],
        ];
    }
}
