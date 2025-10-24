<?php

namespace App\Models\Psychometric;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychometricQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'label',
        'value',
        'score',
        'weight',
        'position',
        'metadata',
    ];

    protected $casts = [
        'score' => 'float',
        'weight' => 'float',
        'metadata' => 'array',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(PsychometricQuestion::class, 'question_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PsychometricResponse::class, 'option_id');
    }
}
