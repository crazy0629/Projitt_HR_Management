<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating an interview.
     */
    public function rules(): array
    {
        return [
            'schedule_type' => 'required|in:request_availability,propose_time',
            'mode' => 'required|in:google_meet,zoom,projitt_video_conference,microsoft_team',

            'interviewers_ids' => 'required|array',
            'interviewers_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],

            'job_id' => [
                'required',
                'integer',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],

            'applicant_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],

            'message' => 'nullable|string',

            // Separate date & time (always required per your migration)
            'date'                => 'required_if:schedule_type,propose_time|date',
            'time'                => 'required_if:schedule_type,propose_time|date_format:H:i',

            // ✅ restrict status values
            'status'              => [
                'nullable',
                Rule::in(['review', 'screen', 'test', 'rejected', 'selected', 'hired', 'cancel']),
            ],

        ];
    }
}
