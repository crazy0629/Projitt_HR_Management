<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class DeleteJobStage extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:job_stages,id',
        ];
    }
}
