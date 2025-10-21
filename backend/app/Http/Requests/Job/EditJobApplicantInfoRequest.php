<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditJobApplicantInfoRequest extends FormRequest
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
                Rule::exists('jobs', 'id')->where(fn ($query) => $query->whereNull('deleted_at')
                    ->where('status', 'open')
                ),
            ],

            'applicant_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereNull('deleted_at')
                ),
            ],

            'skill_ids' => ['required', 'array'],
            'skill_ids.*' => [
                'required',
                Rule::exists('masters', 'id')->whereNull('deleted_at'),
            ],

            'linkedin_link' => ['required', 'url'],
            'portfolio_link' => ['required', 'url'],

            'other_links' => ['nullable', 'array'],
            'other_links.*.title' => ['required', 'string'],
            'other_links.*.link' => ['required', 'url'],
        ];
    }
}
