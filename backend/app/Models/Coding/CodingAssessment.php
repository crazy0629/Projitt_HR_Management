<?php

namespace App\Models\Coding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User\User;

class CodingAssessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'languages',
        'difficulty',
        'time_limit_minutes',
        'max_score',
        'rubric',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'languages' => 'array',
        'rubric' => 'array',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(CodingTestCase::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CodingAssessmentAssignment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CodingSubmission::class);
    }
}
