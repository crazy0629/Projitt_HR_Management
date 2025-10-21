<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMasterRequest extends FormRequest
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
            'name' => [
                'required',
                'max:500',
                'regex:/^[a-zA-Z0-9 ,\-\(\)]+$/',
                Rule::unique('masters')
                    ->where(function ($query) {
                        return $query->where('type_id', $this->type_id)
                                     ->whereNull('deleted_at');
                    }),
            ],
            'type_id' => 'required|integer|between:1,20',
        ];
    }
}
