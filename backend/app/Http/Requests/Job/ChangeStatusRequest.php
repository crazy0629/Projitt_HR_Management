<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
           'ids' => ['required', 'array'],
            'ids.*' => [
                'required',
                Rule::exists('jobs', 'id')->whereNull('deleted_at'),
            ],
            'status' => 'required|in:draft,open,closed,hold',
        ];
    }
}
