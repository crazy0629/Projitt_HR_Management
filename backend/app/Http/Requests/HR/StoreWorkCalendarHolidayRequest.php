<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCalendarHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_calendar_id' => ['nullable', 'exists:work_calendars,id'],
            'name' => ['required', 'string', 'max:150'],
            'holiday_date' => ['required', 'date'],
            'is_recurring' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
