<?php

namespace App\Http\Requests\Psychometric;

use Illuminate\Foundation\Http\FormRequest;

class AssignPsychometricTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('candidate_ids') && is_string($this->input('candidate_ids'))) {
            $ids = array_filter(array_map('trim', explode(',', (string) $this->input('candidate_ids'))));
            $this->merge(['candidate_ids' => array_map('intval', $ids)]);
        }
    }

    public function rules(): array
    {
        return [
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'integer|exists:users,id',
            'job_applicant_id' => 'nullable|integer|exists:job_applicants,id',
            'target_role' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'metadata' => 'nullable|array',
            'invitation_message' => 'nullable|string|max:2000',
        ];
    }
}
