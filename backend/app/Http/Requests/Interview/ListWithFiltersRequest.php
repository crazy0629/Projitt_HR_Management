<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class ListWithFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'schedule_type' => 'nullable|in:request_availability,propose_time',
            'mode' => 'nullable|in:google_meet,zoom,projitt_video_conference,microsoft_team',
            'status' => 'nullable|in:review,screen,test,rejected,selected,hired',
            'job_id' => 'nullable|integer|exists:jobs,id,deleted_at,NULL',
            'applicant_id' => 'nullable|integer|exists:users,id,deleted_at,NULL',
            'date' => 'nullable|date',
        ];

        if ($this->query('pagination')) {
            $rules['per_page'] = 'required|integer|min:1';
            $rules['page'] = 'required|integer|min:1';
        }

        return $rules;
    }
}
