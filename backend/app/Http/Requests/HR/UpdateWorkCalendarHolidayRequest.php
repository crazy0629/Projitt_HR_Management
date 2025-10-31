<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkCalendarHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_calendar_id' => ['sometimes', 'nullable', 'exists:work_calendars,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'holiday_date' => ['sometimes', 'date'],
            'is_recurring' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
