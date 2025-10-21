<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditMasterRequest extends FormRequest
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
        $id = $this->route('id'); // or $this->id if coming from body

        return [
            'id' => 'required|exists:masters,id,deleted_at,NULL',

            'name' => [
                'required',
                'max:500',
                'regex:/^[a-zA-Z0-9 ,\-\(\)]+$/',
                Rule::unique('masters')
                    ->where(function ($query) {
                        return $query->where('type_id', $this->type_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
        ];
    }
}
