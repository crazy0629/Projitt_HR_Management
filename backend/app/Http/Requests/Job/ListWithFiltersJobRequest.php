<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWithFiltersJobRequest extends FormRequest
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
        $rules = [
            'title'                 => ['nullable', 'string', 'max:200'],

            'department_ids'        => ['nullable', 'array'],
            'department_ids.*'      => ['integer', Rule::exists('masters', 'id')->whereNull('deleted_at')],

            'employment_type_ids'   => ['nullable', 'array'],
            'employment_type_ids.*' => ['integer', Rule::exists('masters', 'id')->whereNull('deleted_at')],

            'location_type_ids'     => ['nullable', 'array'],
            'location_type_ids.*'   => ['integer', Rule::exists('masters', 'id')->whereNull('deleted_at')],

            'country_ids'           => ['nullable', 'array'],
            'country_ids.*'         => ['integer', Rule::exists('countries', 'id')->whereNull('deleted_at')],

            'state'                 => ['nullable', 'string', 'max:200'],

            'salary_from'           => ['nullable', 'numeric', 'min:0'],
            'salary_to'             => ['nullable', 'numeric', 'gte:salary_from'],

            'deadline_before'       => ['nullable', 'date'],
            'deadline_after'        => ['nullable', 'date'],

            'skill_ids'             => ['nullable', 'array'],
            'skill_ids.*'           => ['integer', Rule::exists('masters', 'id')->whereNull('deleted_at')],

            'question_ids'          => ['nullable', 'array'],
            'question_ids.*'        => ['integer', Rule::exists('questions', 'id')->whereNull('deleted_at')],

            'pagination'            => ['nullable', 'boolean'],
        ];

        if ($this->boolean('pagination')) {
            $rules['per_page'] = ['required', 'integer', 'min:1'];
            $rules['page']     = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
