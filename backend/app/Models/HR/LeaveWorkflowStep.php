<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_type_id',
        'level',
        'name',
        'approver_role',
        'approver_id',
        'escalate_after_hours',
        'escalate_to_role',
        'escalate_to_user_id',
        'requires_all',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'requires_all' => 'boolean',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
