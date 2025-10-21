<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateJobRequest extends FormRequest
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
            'title'                 => ['required', 'string', 'max:200'],
            'no_of_job_opening'     => ['required', 'integer', 'min:1'],

            'skill_ids'             => ['required', 'array'],
            'skill_ids.*'           => ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],

            'department_id'         => ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],
            'employment_type_id'    => ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],
            'location_type_id'      => ['required', Rule::exists('masters', 'id')->whereNull('deleted_at')],
            'country_id'            => ['required', Rule::exists('countries', 'id')->whereNull('deleted_at')],

            'state'                 => ['required', 'string', 'max:200'],
            'salary_from'           => ['required', 'numeric', 'min:0'],
            'salary_to'             => ['required', 'numeric', 'gte:salary_from'],

            'deadline'              => ['required', 'date', 'after_or_equal:today'],
            'description' => ['required', 'string', 'max:2000'],
            'media_ids' => ['required', 'array'],
            'media_ids.*' => [
                'integer',
                Rule::exists('media', 'id')->whereNull('deleted_at'),
            ],
            'question_ids' => ['required', 'array'],
            'question_ids.*' => [
                'integer',
                Rule::exists('questions', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
