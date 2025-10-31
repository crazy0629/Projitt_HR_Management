<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkCalendarHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_calendar_id',
        'name',
        'holiday_date',
        'is_recurring',
        'description',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
    ];

    public function calendar()
    {
        return $this->belongsTo(WorkCalendar::class, 'work_calendar_id');
    }
}
