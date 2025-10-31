<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkCalendarRequest extends FormRequest
{
    private const WEEK_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $calendar = $this->route('workCalendar') ?? $this->route('work_calendar');
        $calendarId = is_object($calendar) ? $calendar->getKey() : $calendar;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('work_calendars', 'name')->ignore($calendarId),
            ],
            'timezone' => ['sometimes', 'timezone'],
            'description' => ['nullable', 'string'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'working_days' => ['sometimes', 'array', 'min:1'],
            'working_days.*' => ['string', Rule::in(self::WEEK_DAYS)],
            'daily_start_time' => ['nullable', 'date_format:H:i'],
            'daily_end_time' => ['nullable', 'date_format:H:i', 'after:daily_start_time'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
