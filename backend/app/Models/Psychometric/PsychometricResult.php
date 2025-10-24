<?php

namespace App\Models\Psychometric;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychometricResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'psychometric_test_id',
        'candidate_id',
        'dimension_id',
        'dimension_key',
        'raw_score',
        'weighted_score',
        'percentile',
        'band',
        'metadata',
    ];

    protected $casts = [
        'raw_score' => 'float',
        'weighted_score' => 'float',
        'percentile' => 'float',
        'metadata' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PsychometricAssignment::class, 'assignment_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychometricTest::class, 'psychometric_test_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(PsychometricDimension::class, 'dimension_id');
    }
}
