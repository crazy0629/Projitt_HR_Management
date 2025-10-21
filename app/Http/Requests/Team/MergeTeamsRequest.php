<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeTeamsRequest extends FormRequest
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
            'team_ids'   => ['required', 'array', 'size:2'], // exactly 2 teams must be provided
            'team_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('teams', 'id')->whereNull('deleted_at'),
            ],

            'new_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')->whereNull('deleted_at'),
            ],
        ];
    }
}
