<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditTeamRequest extends FormRequest
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
        $id = $this->input('id');

        return [
            // take id from payload; must exist and not be soft-deleted
            'id'          => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->whereNull('deleted_at'),
            ],

            // editable fields
            'name'        => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('teams', 'name')
                    ->ignore($id)                 // ignore current team
                    ->whereNull('deleted_at'),    // enforce uniqueness among non-deleted rows
            ],
            'description' => ['nullable', 'string'],

            // optional user assignments
            'user_ids'    => ['nullable', 'array'],
            'user_ids.*'  => ['integer', 'exists:users,id'],
        ];
    }
}
