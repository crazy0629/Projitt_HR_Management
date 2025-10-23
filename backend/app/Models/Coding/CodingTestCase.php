<?php

namespace App\Models\Coding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingTestCase extends Model
{
    protected $fillable = [
        'coding_assessment_id',
        'name',
        'input',
        'expected_output',
        'weight',
        'is_hidden',
        'timeout_seconds',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CodingAssessment::class, 'coding_assessment_id');
    }
}
