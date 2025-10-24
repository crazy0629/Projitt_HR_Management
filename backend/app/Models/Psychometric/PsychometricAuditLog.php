<?php

namespace App\Models\Psychometric;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychometricAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychometric_test_id',
        'assignment_id',
        'candidate_id',
        'actor_id',
        'action',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychometricTest::class, 'psychometric_test_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PsychometricAssignment::class, 'assignment_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
