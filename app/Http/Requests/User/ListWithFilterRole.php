<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ListWithFilterRole extends FormRequest
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
        $validationRules = array();
        if ($this->query('pagination')){ $validationRules['per_page'] = 'required'; $validationRules['page'] = 'required'; }

        $validationRules['name'] = 'nullable|max:20';

        return $validationRules;
    }
}
