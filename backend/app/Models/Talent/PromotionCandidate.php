<?php

namespace App\Models\Talent;

use App\Models\Role\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PromotionCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'current_role_id',
        'proposed_role_id',
        'justification',
        'comp_adjustment',
        'workflow_id',
        'status',
        'created_by',
        'updated_by',
        'submitted_at',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'comp_adjustment' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'submitted_at',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function currentRole()
    {
        return $this->belongsTo(Role::class, 'current_role_id');
    }

    public function proposedRole()
    {
        return $this->belongsTo(Role::class, 'proposed_role_id');
    }

    public function workflow()
    {
        return $this->belongsTo(PromotionWorkflow::class, 'workflow_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvals()
    {
        return $this->hasMany(PromotionApproval::class, 'promotion_id')->orderBy('step_order');
    }

    public function currentApproval()
    {
        return $this->hasOne(PromotionApproval::class, 'promotion_id')
            ->where('decision', 'pending')
            ->orderBy('step_order');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeInReview($query)
    {
        return $query->where('status', 'in_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByManager($query, $managerId)
    {
        return $query->whereHas('employee', function ($q) use ($managerId) {
            $q->where('manager_id', $managerId);
        });
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function ($q) use ($department) {
            $q->where('department', $department);
        });
    }

    // State Management Methods
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isSubmitted()
    {
        return $this->status === 'submitted';
    }

    public function isInReview()
    {
        return $this->status === 'in_review';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isWithdrawn()
    {
        return $this->status === 'withdrawn';
    }

    public function canEdit()
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    public function canSubmit()
    {
        return $this->status === 'draft';
    }

    public function canWithdraw()
    {
        return in_array($this->status, ['submitted', 'in_review']);
    }

    // Workflow Management
    public function submit()
    {
        if (! $this->canSubmit()) {
            throw new \Exception('Promotion cannot be submitted in current status: '.$this->status);
        }

        $this->status = 'submitted';
        $this->submitted_at = now();
        $this->save();

        // Create approval steps
        $this->createApprovalSteps();

        // Move to in_review if approvals were created
        if ($this->approvals()->count() > 0) {
            $this->status = 'in_review';
            $this->save();
        }

        $this->logAudit('submitted');

        return $this;
    }

    public function withdraw($reason = null)
    {
        if (! $this->canWithdraw()) {
            throw new \Exception('Promotion cannot be withdrawn in current status: '.$this->status);
        }

        $this->status = 'withdrawn';
        $this->rejection_reason = $reason;
        $this->save();

        $this->logAudit('withdrawn', ['reason' => $reason]);

        return $this;
    }

    public function approve($approvalId, $note = null)
    {
        $approval = $this->approvals()->findOrFail($approvalId);

        if ($approval->decision !== 'pending') {
            throw new \Exception('This approval step has already been processed');
        }

        $approval->decision = 'approved';
        $approval->decision_note = $note;
        $approval->decided_at = now();
        $approval->save();

        // Check if all approvals are complete
        $pendingApprovals = $this->approvals()->where('decision', 'pending')->count();

        if ($pendingApprovals === 0) {
            $this->status = 'approved';
            $this->approved_at = now();
            $this->save();

            $this->processApprovedPromotion();
        }

        $this->logAudit('approval_updated', [
            'approval_id' => $approvalId,
            'decision' => 'approved',
            'note' => $note,
        ]);

        return $this;
    }

    public function reject($approvalId, $reason)
    {
        $approval = $this->approvals()->findOrFail($approvalId);

        if ($approval->decision !== 'pending') {
            throw new \Exception('This approval step has already been processed');
        }

        $approval->decision = 'rejected';
        $approval->decision_note = $reason;
        $approval->decided_at = now();
        $approval->save();

        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->save();

        $this->logAudit('rejected', [
            'approval_id' => $approvalId,
            'reason' => $reason,
        ]);

        return $this;
    }

    // Helper Methods
    public function getCompensationAdjustmentFormatted()
    {
        if (! $this->comp_adjustment) {
            return 'No adjustment';
        }

        $adjustment = $this->comp_adjustment;

        if ($adjustment['type'] === 'amount') {
            return '$'.number_format($adjustment['value']);
        } elseif ($adjustment['type'] === 'percentage') {
            return $adjustment['value'].'%';
        }

        return 'Custom adjustment';
    }

    public function getDaysInReview()
    {
        if (! $this->submitted_at) {
            return 0;
        }

        $endDate = $this->approved_at ?? $this->updated_at ?? now();

        return $this->submitted_at->diffInDays($endDate);
    }

    public function getProgressPercentage()
    {
        $totalSteps = $this->approvals()->count();

        if ($totalSteps === 0) {
            return $this->isApproved() ? 100 : 0;
        }

        $completedSteps = $this->approvals()->whereIn('decision', ['approved', 'rejected'])->count();

        return round(($completedSteps / $totalSteps) * 100, 1);
    }

    public function getNextApprover()
    {
        return $this->currentApproval?->approver;
    }

    // Private Methods
    private function createApprovalSteps()
    {
        if (! $this->workflow) {
            return;
        }

        $steps = $this->workflow->steps ?? [];

        foreach ($steps as $index => $step) {
            // Logic to determine approver based on step role
            $approverId = $this->determineApprover($step);

            if ($approverId) {
                PromotionApproval::create([
                    'promotion_id' => $this->id,
                    'step_order' => $index + 1,
                    'approver_id' => $approverId,
                    'decision' => 'pending',
                ]);
            }
        }
    }

    private function determineApprover($step)
    {
        if (app()->environment(['local', 'testing'])) {
            $superAdmin = User::whereHas('role', fn($q) =>
                $q->where('name', 'Super Admin')
            )->first();
            return $superAdmin?->id;
        }

        $role = $step['role'] ?? null;

        switch ($role) {
            case 'manager':
                return $this->employee->manager_id;

            case 'hrbp':
                // Find HRBP by role name via relationship
                return User::whereHas('role', function ($q) {
                        $q->where('name', 'hrbp');
                    })
                    // ->where('department', $this->employee->department)
                    ->first()?->id;

            case 'director':
                // Find Director via role relationship
                return User::whereHas('role', function ($q) {
                        $q->where('name', 'director');
                    })
                    // ->where('department', $this->employee->department)
                    ->first()?->id;

            case 'finance':
                // Find Finance approver via role relationship
                return User::whereHas('role', function ($q) {
                        $q->where('name', 'finance');
                    })
                    ->first()?->id;

            default:
                return User::whereHas('role', fn($q) => $q->where('name', 'Super Admin'))->first()?->id;
        }
    }

    private function processApprovedPromotion()
    {
        try {
            // Update employee role if proposed role is specified
            if ($this->proposed_role_id && $this->employee) {
                $this->employee->role_id = $this->proposed_role_id;
                $this->employee->save();
            }

            // Process compensation adjustment
            if ($this->comp_adjustment) {
                $this->processCompensationChange();
            }

            // Close any active PIPs for this employee
            Pip::where('employee_id', $this->employee_id)
                ->where('status', 'active')
                ->update(['status' => 'completed', 'completion_notes' => 'Promotion approved']);

            // Remove from succession planning if they got the role they were being considered for
            SuccessionCandidate::where('employee_id', $this->employee_id)
                ->where('target_role_id', $this->proposed_role_id)
                ->delete();

            $this->logAudit('promotion_processed');

        } catch (\Exception $e) {
            Log::error('Failed to process approved promotion', [
                'promotion_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function processCompensationChange()
    {
        // This would integrate with payroll/HRIS system
        // For now, just log the change
        Log::info('Compensation change requested', [
            'employee_id' => $this->employee_id,
            'promotion_id' => $this->id,
            'adjustment' => $this->comp_adjustment,
        ]);
    }

    private function logAudit($action, $payload = [])
    {
        AuditLog::create([
            'actor_id' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
            'entity_type' => 'PromotionCandidate',
            'entity_id' => $this->id,
            'action' => $action,
            'payload_json' => array_merge($payload, [
                'status' => $this->status,
                'employee_id' => $this->employee_id,
            ]),
            'created_at' => now(),
        ]);
    }

    // Static Methods
    public static function createForEmployee($employeeId, $data)
    {
        return self::create([
            'employee_id' => $employeeId,
            'current_role_id' => $data['current_role_id'] ?? null,
            'proposed_role_id' => $data['proposed_role_id'] ?? null,
            'justification' => $data['justification'],
            'comp_adjustment' => $data['comp_adjustment'] ?? null,
            'workflow_id' => $data['workflow_id'] ?? null,
            'status' => 'draft',
            'created_by' => Auth::guard('sanctum')->id() ?? auth()->id() ?? 1,
        ]);
    }
}
