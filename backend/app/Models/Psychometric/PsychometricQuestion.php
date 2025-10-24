<?php

namespace App\Models\Psychometric;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychometricQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychometric_test_id',
        'dimension_id',
        'reference_code',
        'question_text',
        'question_type',
        'weight',
        'is_required',
        'randomize_options',
        'base_order',
        'metadata',
    ];

    protected $casts = [
        'weight' => 'float',
        'is_required' => 'boolean',
        'randomize_options' => 'boolean',
        'metadata' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychometricTest::class, 'psychometric_test_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(PsychometricDimension::class, 'dimension_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PsychometricQuestionOption::class, 'question_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PsychometricResponse::class, 'question_id');
    }
}
