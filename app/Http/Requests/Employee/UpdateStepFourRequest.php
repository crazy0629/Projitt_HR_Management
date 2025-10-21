<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStepFourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fields:
     * - employee_id (employees, not soft-deleted)
     * - onbaording_checklist_ids: JSON array of master IDs where type_id = 7
     * - training_learnging_path: string
     * - benifit_ids: JSON array of master IDs where type_id = 8
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],

            // Onboarding checklist (masters.type_id = 7)
            'onbaording_checklist_ids' => ['nullable', 'array'],
            'onbaording_checklist_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('masters', 'id')->where(function ($q) {
                    $q->where('type_id', 7)->whereNull('deleted_at');
                }),
            ],

            // Training / learning path
            'training_learnging_path' => ['nullable', 'string', 'max:255'],

            // Benefits (masters.type_id = 8)
            'benifit_ids' => ['nullable', 'array'],
            'benifit_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('masters', 'id')->where(function ($q) {
                    $q->where('type_id', 8)->whereNull('deleted_at');
                }),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'                => 'employee',
            'onbaording_checklist_ids'   => 'onboarding checklist',
            'onbaording_checklist_ids.*' => 'onboarding checklist item',
            'training_learnging_path'    => 'training & learning path',
            'benifit_ids'                => 'benefits',
            'benifit_ids.*'              => 'benefit',
        ];
    }

    public function messages(): array
    {
        return [
            'onbaording_checklist_ids.*.distinct' => 'Duplicate checklist items are not allowed.',
            'benifit_ids.*.distinct'              => 'Duplicate benefits are not allowed.',
        ];
    }
}
