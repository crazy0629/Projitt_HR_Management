<?php

namespace App\Models\Coding;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CodingAssessmentAssignment extends Model
{
    protected $fillable = [
        'coding_assessment_id',
        'candidate_id',
        'talentable_type',
        'talentable_id',
        'status',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'completed_at',
        'invitation_message',
        'metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CodingAssessment::class, 'coding_assessment_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function talentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CodingSubmission::class, 'assignment_id');
    }
}
