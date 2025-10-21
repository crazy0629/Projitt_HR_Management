<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCookiesTrackingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // allow all requests (you can add auth logic later if needed)
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
            'session_id'       => 'required|string|max:255',
            'consent_status'   => 'required|in:accepted,rejected',
            'consent_timestamp'=> 'required|date',
            'user_agent'       => 'nullable|string',
            'ip_address'       => 'nullable|ip|max:45',
        ];
    }
}
