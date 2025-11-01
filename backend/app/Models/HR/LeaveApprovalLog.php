<?php

namespace App\Models\HR;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApprovalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'workflow_step_id',
        'actor_id',
        'action',
        'comments',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(LeaveRequestApprovalStep::class, 'workflow_step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
