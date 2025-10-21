<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddNewEmployeeRequest extends FormRequest
{
    /**
     * Anyone hitting this endpoint (behind your auth/middleware) is allowed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize a few inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => is_string($this->first_name) ? trim($this->first_name) : $this->first_name,
            'last_name'  => is_string($this->last_name)  ? trim($this->last_name)  : $this->last_name,
            'email'      => is_string($this->email)      ? strtolower(trim($this->email)) : $this->email,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'     => ['required', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],

            'employee_type'  => [
                'required',
                Rule::in(['full_time', 'freelance', 'part_time', 'intern']),
            ],

            'email'          => [
                'required',
                'string',
                'email',
                'max:191',
                // unique among non-deleted employees (respects soft deletes)
                'unique:users,email,NULL,id,deleted_at,NULL',
            ],

            'country_id'     => ['required', 'integer', 'exists:countries,id'],
        ];
    }

    /**
     * Optional: nicer messages.
     */
    public function messages(): array
    {
        return [
            'employee_type.in'   => 'Employee type must be one of: full_time, freelance, part_time, intern.',
            'country_id.exists'  => 'Selected country is invalid.',
        ];
    }

    /**
     * Optional: human-friendly attribute names.
     */
    public function attributes(): array
    {
        return [
            'country_id' => 'country',
        ];
    }
}
