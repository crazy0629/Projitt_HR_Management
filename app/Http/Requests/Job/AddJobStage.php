<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class AddJobStage extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust if you use policies
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'order'       => 'nullable|integer|min:0',
            'type_id'     => 'nullable|exists:masters,id',
            'job_id'      => 'nullable|exists:jobs,id',
            'sub_type_id' => 'nullable|exists:masters,id',
        ];
    }
}
