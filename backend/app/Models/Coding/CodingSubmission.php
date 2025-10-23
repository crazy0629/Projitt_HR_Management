<?php

namespace App\Models\Coding;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodingSubmission extends Model
{
    protected $fillable = [
        'assignment_id',
        'coding_assessment_id',
        'candidate_id',
        'language',
        'source_code',
        'status',
        'passed_count',
        'failed_count',
        'total_count',
        'score',
        'max_score',
        'execution_time_ms',
        'memory_kb',
        'sandbox_job_id',
        'stdout',
        'stderr',
        'error_type',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CodingAssessment::class, 'coding_assessment_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CodingAssessmentAssignment::class, 'assignment_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(CodingSubmissionTestResult::class, 'submission_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CodingSubmissionReview::class, 'submission_id');
    }
}
