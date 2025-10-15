<?php

namespace App\Models\Interview;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interview extends Model
{
    use SoftDeletes;

    protected $table = 'interviews';

    protected $fillable = [
        'schedule_type',
        'mode',
        'interviewers_ids',
        'job_id',
        'applicant_id',
        'message',
        'date_time',
        'status',
        'date',
        'time',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'interviewers_ids' => 'array',
        'date_time' => 'datetime',
        'date' => 'date',
        // keep 'time' as string; cast to datetime with a format if you prefer
    ];

    /* -------------------------
     | Filters
     * ------------------------*/
    public static function filterData($request)
    {
        $filtered = self::query();

        if ($request->filled('schedule_type')) {
            $filtered->where('schedule_type', $request->input('schedule_type'));
        }

        if ($request->filled('mode')) {
            $filtered->where('mode', $request->input('mode'));
        }

        if ($request->filled('status')) {
            $filtered->where('status', $request->input('status'));
        }

        if ($request->filled('job_id')) {
            $filtered->where('job_id', $request->input('job_id'));
        }

        if ($request->filled('applicant_id')) {
            $filtered->where('applicant_id', $request->input('applicant_id'));
        }

        if ($request->filled('date')) {
            $filtered->whereDate('date', $request->input('date'));
        }

        if ($request->filled('date_time')) {
            $filtered->whereDate('date_time', $request->input('date_time'));
        }

        return $filtered;
    }
}
