<?php

namespace App\Http\Requests\JobStage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeJobStageOrderRequest extends FormRequest
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
            // 1) job_id must exist in jobs where deleted_at is null
            'job_id' => [
                'required',
                'integer',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],

            // 3) order array + each item shape
            'order' => ['required', 'array', 'min:1'],

            // stage id must exist in job_stages for the SAME (job_id, applicant_id) row (and not soft-deleted)
            'order.*.id' => [
                'required',
                'integer',
                Rule::exists('job_stages', 'id')->where(function ($q) {
                    $q->where('job_id', $this->input('job_id'))
                      ->whereNull('deleted_at');
                }),
            ],

            // 4) order number required, positive int, and unique within the payload
            'order.*.order' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order.*.id'    => 'stage id',
            'order.*.order' => 'order number',
        ];
    }

    public function messages(): array
    {
        return [
            'order.*.order.distinct' => 'Each stage must have a unique order number.',
        ];
    }
}
