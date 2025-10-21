<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrentStage extends FormRequest
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
            'applicant_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->whereNull('deleted_at'),
            ],
            'job_id' => [
                'required',
                'integer',
                Rule::exists('jobs', 'id')
                    ->whereNull('deleted_at'), // remove if jobs table doesn't use soft deletes
            ],
            'current_job_stage_id' => [
                'required',
                'integer',
                Rule::exists('job_stages', 'id')
                    ->where(function ($query) {
                        $query->where('job_id', $this->input('job_id'))
                              ->whereNull('deleted_at'); // remove if job_stages table doesn't use soft deletes
                    }),
            ],
        ];
    }
}
