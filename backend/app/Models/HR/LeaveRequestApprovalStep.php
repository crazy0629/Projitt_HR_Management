<?php

namespace App\Models\HR;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'workflow_step_id',
        'level',
        'name',
        'status',
        'approver_id',
        'approver_role',
        'delegated_to',
        'delegated_by',
        'delegated_at',
        'delegation_note',
        'due_at',
        'decided_at',
        'escalated_at',
        'escalate_to_role',
        'escalate_to_user_id',
        'escalation_count',
        'metadata',
    ];

    protected $casts = [
        'delegated_at' => 'datetime',
        'due_at' => 'datetime',
        'decided_at' => 'datetime',
        'escalated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(LeaveWorkflowStep::class, 'workflow_step_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    public function delegatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalate_to_user_id');
    }
}
