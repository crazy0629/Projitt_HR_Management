<?php

namespace App\Models\Coding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingSubmissionTestResult extends Model
{
    protected $fillable = [
        'submission_id',
        'test_case_id',
        'status',
        'error_type',
        'score_earned',
        'execution_time_ms',
        'memory_kb',
        'stdout',
        'stderr',
        'error_message',
    ];

    protected $casts = [
        'score_earned' => 'float',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(CodingSubmission::class, 'submission_id');
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(CodingTestCase::class, 'test_case_id');
    }
}
