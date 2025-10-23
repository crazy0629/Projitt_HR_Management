<?php

namespace App\Http\Requests\Coding;

use Illuminate\Foundation\Http\FormRequest;

class AssignCodingAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'integer|exists:users,id',
            'talentable_type' => 'nullable|string|max:255',
            'talentable_id' => 'nullable|integer',
            'expires_at' => 'nullable|date|after:now',
            'invitation_message' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
