<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'timezone',
        'description',
        'effective_from',
        'effective_to',
        'working_days',
        'daily_start_time',
        'daily_end_time',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'working_days' => 'array',
        'metadata' => 'array',
    ];

    public function holidays()
    {
        return $this->hasMany(WorkCalendarHoliday::class);
    }
}
