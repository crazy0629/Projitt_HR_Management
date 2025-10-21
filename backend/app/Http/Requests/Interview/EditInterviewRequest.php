<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditInterviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ✅ allow request (adjust authorization if needed)
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
            'id' => [
                'required',
                'integer',
                Rule::exists('interviews', 'id')->whereNull('deleted_at'),
            ],

            'schedule_type'       => 'required|in:request_availability,propose_time',
            'mode'                => 'required|in:google_meet,zoom,projitt_video_conference,microsoft_team',

            'interviewers_ids'    => 'required|array',
            'interviewers_ids.*'  => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],

            'job_id'              => [
                'required',
                'integer',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],

            'applicant_id'        => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],

            'message'             => 'nullable|string',

            // Required only if schedule_type = propose_time
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
