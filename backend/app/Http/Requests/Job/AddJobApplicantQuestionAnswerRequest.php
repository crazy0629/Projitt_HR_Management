<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Job\Job;

class AddJobApplicantQuestionAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],
            'applicant_id' => [
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'question_id' => [
                'required',
                Rule::exists('questions', 'id')->whereNull('deleted_at'),
            ],
            'answer' => ['required'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $jobId = $this->input('job_id');
            $questionId = $this->input('question_id');

            $job = Job::select('question_ids')->find($jobId);

            if (!$job) {
                $validator->errors()->add('job_id', 'Invalid job ID.');
                return;
            }

            $questionIds = is_array($job->question_ids)
                ? $job->question_ids
                : json_decode($job->question_ids, true);

            if (!is_array($questionIds) || !in_array($questionId, $questionIds)) {
                $validator->errors()->add('question_id', 'The selected question does not belong to the specified job.');
            }
        });
    }
}
