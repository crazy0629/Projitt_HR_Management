<?php

namespace App\Models\HR;

use App\Models\HR\LeaveApprovalLog;
use App\Models\HR\LeaveRequestApprovalStep;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approver_id',
        'decided_at',
        'canceled_by',
        'cancellation_reason',
        'metadata',
        'created_by',
        'updated_by',
        'current_step_level',
        'workflow_completed_at',
        'escalation_count',
        'latest_escalated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'decided_at' => 'datetime',
        'metadata' => 'array',
        'workflow_completed_at' => 'datetime',
        'latest_escalated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function approvalSteps()
    {
        return $this->hasMany(LeaveRequestApprovalStep::class, 'leave_request_id')->orderBy('level');
    }

    public function approvalLogs()
    {
        return $this->hasMany(LeaveApprovalLog::class, 'leave_request_id')->latest();
    }

    public function currentApprovalStep()
    {
        return $this->hasOne(LeaveRequestApprovalStep::class, 'leave_request_id')->where('status', 'pending')->orderBy('level');
    }
}
