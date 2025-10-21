<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCookiesVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // allow all authenticated requests
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
            'session_id'        => 'required|string|max:255',
            'page_url'          => 'required|string',
            'page_title'        => 'nullable|string|max:500',
            'referrer'          => 'nullable|string',
            'user_agent'        => 'nullable|string',
            'screen_resolution' => 'nullable|string|max:20',
            'viewport_size'     => 'nullable|string|max:20',
            'language'          => 'nullable|string|max:10',
            'timezone'          => 'nullable|string|max:50',
            'page_type'         => 'nullable|string|max:50',
            'is_first_visit'    => 'boolean',
            'ip_address'        => 'nullable|ip|max:45',
        ];
    }
}
