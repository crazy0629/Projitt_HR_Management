<?php

namespace App\Models\Psychometric;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychometricResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'psychometric_test_id',
        'question_id',
        'option_id',
        'numeric_response',
        'text_response',
        'metadata',
        'time_spent_seconds',
        'responded_at',
    ];

    protected $casts = [
        'numeric_response' => 'float',
        'metadata' => 'array',
        'time_spent_seconds' => 'integer',
        'responded_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PsychometricAssignment::class, 'assignment_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychometricTest::class, 'psychometric_test_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(PsychometricQuestion::class, 'question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(PsychometricQuestionOption::class, 'option_id');
    }
}
