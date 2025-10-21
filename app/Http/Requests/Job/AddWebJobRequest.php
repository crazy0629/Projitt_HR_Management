<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddWebJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // allow for now, you can enforce policies later
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'full_name'            => 'required|string|max:150',
        'email'                => 'required|email|max:150',
        'linkdin_profile_link' => 'required|url|max:255',

        'job_id' => [
            'nullable',
            'integer',
            'exists:jobs,id',
        ],

        // validate media_id exists AND not soft deleted
        'resume_media_id' => [
            'nullable',
            'integer',
            Rule::exists('media', 'id')->whereNull('deleted_at'),
        ],
        'cover_media_id' => [
            'nullable',
            'integer',
            Rule::exists('media', 'id')->whereNull('deleted_at'),
        ],
    ];
}

}
