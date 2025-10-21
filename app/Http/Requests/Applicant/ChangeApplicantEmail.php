<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;

class ChangeApplicantEmail extends FormRequest
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
        'existing_email' => [
            'required',
            'email',
            'exists:users,email,deleted_at,NULL',
        ],
        'new_email' => [
            'required',
            'email',
            'different:existing_email',
            'unique:users,email,NULL,id,deleted_at,NULL',
        ],
    ];
}

}
