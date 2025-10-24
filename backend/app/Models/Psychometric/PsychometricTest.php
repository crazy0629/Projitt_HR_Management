<?php

namespace App\Models\Psychometric;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PsychometricTest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'instructions',
        'time_limit_minutes',
        'allowed_attempts',
        'randomize_questions',
        'is_published',
        'scoring_model',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'randomize_questions' => 'boolean',
        'is_published' => 'boolean',
        'time_limit_minutes' => 'integer',
        'allowed_attempts' => 'integer',
        'scoring_model' => 'array',
        'metadata' => 'array',
    ];

    public function dimensions(): HasMany
    {
        return $this->hasMany(PsychometricDimension::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PsychometricQuestion::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PsychometricAssignment::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(PsychometricResult::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
