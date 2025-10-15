<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
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
            'first_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\',\-\s]+$/',
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\',\-\s]+$/',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\',\-\s]+$/',
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:8|confirmed',
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
