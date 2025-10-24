<?php

namespace App\Models\Psychometric;

use App\Models\Job\JobApplicant;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PsychometricAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychometric_test_id',
        'candidate_id',
        'job_applicant_id',
        'talentable_type',
        'talentable_id',
        'status',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
        'expires_at',
        'time_limit_minutes',
        'duration_seconds',
        'attempts_used',
        'randomization_seed',
        'question_order',
        'target_role',
        'metadata',
        'result_snapshot',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'question_order' => 'array',
        'metadata' => 'array',
        'result_snapshot' => 'array',
        'time_limit_minutes' => 'integer',
        'duration_seconds' => 'integer',
        'attempts_used' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychometricTest::class, 'psychometric_test_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function jobApplicant(): BelongsTo
    {
        return $this->belongsTo(JobApplicant::class, 'job_applicant_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function talentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PsychometricResponse::class, 'assignment_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(PsychometricResult::class, 'assignment_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PsychometricAuditLog::class, 'assignment_id');
    }
}
