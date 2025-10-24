<?php

namespace App\Models\Psychometric;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychometricDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychometric_test_id',
        'key',
        'name',
        'description',
        'weight',
        'metadata',
    ];

    protected $casts = [
        'weight' => 'float',
        'metadata' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychometricTest::class, 'psychometric_test_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PsychometricQuestion::class, 'dimension_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(PsychometricResult::class, 'dimension_id');
    }
}
