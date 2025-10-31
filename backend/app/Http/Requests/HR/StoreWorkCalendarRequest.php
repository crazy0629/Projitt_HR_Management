<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkCalendarRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:150', 'unique:work_calendars,name'],
            'timezone' => ['required', 'timezone'],
            'description' => ['nullable', 'string'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['string', Rule::in(self::WEEK_DAYS)],
            'daily_start_time' => ['nullable', 'date_format:H:i'],
            'daily_end_time' => ['nullable', 'date_format:H:i', 'after:daily_start_time'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
