<?php

namespace App\Services\HR;

use App\Models\HR\LeaveApprovalLog;
use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveRequestApprovalStep;
use App\Models\HR\LeaveWorkflowStep;
use App\Models\HR\LeaveType;
use App\Models\Talent\AuditLog;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    /**
     * @return Collection<int, LeaveRequest>|LengthAwarePaginator<LeaveRequest>
     */
    public function listLeaveRequests(bool $paginate = true, int $perPage = 15, array $filters = [])
    {
        $query = LeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver', 'approvalSteps'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('start_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('end_date', '<=', $filters['to_date']);
        }

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function createLeaveRequest(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $employeeId = $data['employee_id'] ?? Auth::id();

            if (! $employeeId) {
                throw ValidationException::withMessages([
                    'employee_id' => ['Employee context is required to submit a leave request.'],
                ]);
            }

            $payload = $this->preparePayload($employeeId, $data);

            $leaveRequest = LeaveRequest::create(array_merge($payload, [
                'status' => 'pending',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));

            $this->initializeWorkflow($leaveRequest);

            $this->logAudit('LeaveRequest', $leaveRequest->getKey(), 'created', [
                'attributes' => $leaveRequest->toArray(),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver', 'approvalSteps']);
        });
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $data) {
            $payload = $this->preparePayload(
                $data['employee_id'] ?? $leaveRequest->employee_id,
                $data,
                $leaveRequest->getKey(),
                $leaveRequest->leave_type_id,
                $leaveRequest->start_date->toDateString(),
                $leaveRequest->end_date->toDateString()
            );

            $leaveRequest->fill($payload);

            if (! $leaveRequest->isDirty()) {
                return $leaveRequest->fresh(['employee', 'leaveType', 'approver', 'approvalSteps']);
            }

            $changes = $leaveRequest->getDirty();
            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $leaveRequest->getOriginal($field);
            }

            $leaveRequest->updated_by = Auth::id();
            $leaveRequest->save();

            $this->logAudit('LeaveRequest', $leaveRequest->getKey(), 'updated', [
                'before' => $before,
                'after' => $leaveRequest->only(array_keys($changes)),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver', 'approvalSteps']);
        });
    }

    public function changeStatus(LeaveRequest $leaveRequest, string $status, array $attributes = []): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $status, $attributes) {
            $normalizedStatus = strtolower($status);

            if (! in_array($normalizedStatus, ['pending', 'approved', 'rejected', 'canceled'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Invalid status value provided.'],
                ]);
            }

            if (in_array($normalizedStatus, ['approved', 'rejected'], true)
                && $leaveRequest->approvalSteps()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'status' => ['Approval workflow still has pending steps. Complete workflow actions before closing the request.'],
                ]);
            }

            $currentStatus = $leaveRequest->status;
            $allowed = $this->allowedTransitions()[$currentStatus] ?? [];

            if ($currentStatus === $normalizedStatus) {
                return $leaveRequest;
            }

            if (! in_array($normalizedStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => ["Status cannot transition from {$currentStatus} to {$normalizedStatus}."]
                ]);
            }

            if ($normalizedStatus === 'approved') {
                $this->assertSufficientBalance(
                    $leaveRequest->employee_id,
                    $leaveRequest->leave_type_id,
                    $leaveRequest->total_days,
                    $leaveRequest->getKey()
                );
                $leaveRequest->approver_id = Auth::id();
                $leaveRequest->decided_at = now();
            } elseif (in_array($normalizedStatus, ['rejected', 'canceled'], true)) {
                $leaveRequest->approver_id = Auth::id();
                $leaveRequest->decided_at = now();
            }

            if ($normalizedStatus === 'canceled') {
                $leaveRequest->canceled_by = Auth::id();
                $leaveRequest->cancellation_reason = $attributes['cancellation_reason'] ?? $leaveRequest->cancellation_reason;
            }

            if (in_array($normalizedStatus, ['approved', 'rejected'], true)) {
                $leaveRequest->workflow_completed_at = now();
                $leaveRequest->current_step_level = null;
            }

            $before = $leaveRequest->only(['status', 'approver_id', 'decided_at', 'canceled_by', 'cancellation_reason']);

            $leaveRequest->status = $normalizedStatus;
            $leaveRequest->updated_by = Auth::id();
            $leaveRequest->save();

            $this->logAudit('LeaveRequest', $leaveRequest->getKey(), 'status_changed', [
                'before' => $before,
                'after' => $leaveRequest->only(['status', 'approver_id', 'decided_at', 'canceled_by', 'cancellation_reason']),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver', 'approvalSteps']);
        });
    }

    public function approve(LeaveRequest $leaveRequest, array $payload = []): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $payload) {
            $actor = $this->resolveActor();
            $step = $this->resolveCurrentStep($leaveRequest, true);

            $this->assertActorCanActOnStep($step, $actor);

            $metadata = $step->metadata ?? [];
            if (! empty($payload['comments'])) {
                $comments = is_array($metadata['comments'] ?? null)
                    ? $metadata['comments']
                    : array_filter([$metadata['comments'] ?? null]);

                $comments[] = [
                    'by' => $actor->id,
                    'comment' => $payload['comments'],
                    'at' => now()->toIso8601String(),
                ];

                $metadata['comments'] = array_values(array_filter($comments));
            }

            $step->status = 'approved';
            $step->decided_at = now();
            $step->approver_id = $actor->id;
            $step->metadata = $metadata;
            $step->save();

            $this->createApprovalLog($leaveRequest, $step, 'approved', $payload['comments'] ?? null);

            $nextStep = $this->advanceWorkflow($leaveRequest);

            if ($nextStep) {
                return $leaveRequest->fresh(['employee', 'leaveType', 'approver', 'approvalSteps']);
            }

            return $this->changeStatus($leaveRequest->fresh(), 'approved');
        });
    }

    public function reject(LeaveRequest $leaveRequest, array $payload): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $payload) {
            $actor = $this->resolveActor();
            $step = $this->resolveCurrentStep($leaveRequest, true);

            $this->assertActorCanActOnStep($step, $actor);

            $reason = $payload['reason'] ?? null;
            if (! $reason) {
                throw ValidationException::withMessages([
                    'reason' => ['Rejection reason is required.'],
                ]);
            }

            $metadata = $step->metadata ?? [];
            $metadata['rejection_reason'] = $reason;
            if (! empty($payload['comments'])) {
                $comments = is_array($metadata['comments'] ?? null)
                    ? $metadata['comments']
                    : array_filter([$metadata['comments'] ?? null]);

                $comments[] = [
                    'by' => $actor->id,
                    'comment' => $payload['comments'],
                    'at' => now()->toIso8601String(),
                ];

                $metadata['comments'] = array_values(array_filter($comments));
            }

            $step->status = 'rejected';
            $step->decided_at = now();
            $step->approver_id = $actor->id;
            $step->metadata = $metadata;
            $step->save();

            $this->createApprovalLog($leaveRequest, $step, 'rejected', $payload['comments'] ?? null, [
                'reason' => $reason,
            ]);

            $this->mergeRequestMetadata($leaveRequest, ['rejection_reason' => $reason]);

            return $this->changeStatus($leaveRequest->fresh(), 'rejected');
        });
    }

    public function delegate(LeaveRequest $leaveRequest, array $payload): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $payload) {
            $actor = $this->resolveActor();
            $step = $this->resolveCurrentStep($leaveRequest, true);

            $this->assertActorCanActOnStep($step, $actor);

            $delegateId = $payload['delegate_to'] ?? null;
            if (! $delegateId) {
                throw ValidationException::withMessages([
                    'delegate_to' => ['Delegation target is required.'],
                ]);
            }

            $delegate = User::find($delegateId);
            if (! $delegate) {
                throw ValidationException::withMessages([
                    'delegate_to' => ['Delegate must reference a valid user.'],
                ]);
            }

            $step->delegated_to = $delegate->id;
            $step->delegated_by = $actor->id;
            $step->delegated_at = now();
            $step->delegation_note = $payload['note'] ?? null;
            $step->due_at = $this->calculateDueAt($step);
            $step->status = 'pending';
            $step->save();

            $this->createApprovalLog($leaveRequest, $step, 'delegated', $payload['note'] ?? null, [
                'delegate_to' => $delegate->only(['id', 'first_name', 'last_name', 'email']),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver', 'approvalSteps']);
        });
    }

    public function processEscalations(?LeaveRequest $leaveRequest = null): int
    {
        return DB::transaction(function () use ($leaveRequest) {
            $query = LeaveRequestApprovalStep::query()
                ->where('status', 'pending')
                ->whereNotNull('due_at')
                ->where('due_at', '<=', now());

            if ($leaveRequest) {
                $query->where('leave_request_id', $leaveRequest->getKey());
            }

            $steps = $query->lockForUpdate()->get();

            foreach ($steps as $step) {
                $step->escalated_at = now();
                $step->escalation_count = ($step->escalation_count ?? 0) + 1;

                $metadata = $step->metadata ?? [];
                $escalations = is_array($metadata['escalations'] ?? null)
                    ? $metadata['escalations']
                    : array_filter([$metadata['escalations'] ?? null]);

                $escalations[] = [
                    'at' => now()->toIso8601String(),
                    'triggered_by' => Auth::id(),
                ];

                $metadata['escalations'] = array_values(array_filter($escalations));

                if ($step->escalate_to_user_id) {
                    $step->approver_id = $step->escalate_to_user_id;
                }

                if ($step->escalate_to_role) {
                    $step->approver_role = $step->escalate_to_role;
                }

                $step->metadata = $metadata;
                $step->due_at = $this->calculateDueAt($step);
                $step->save();

                $leave = $step->leaveRequest()->lockForUpdate()->first();
                if ($leave) {
                    $leave->escalation_count = ($leave->escalation_count ?? 0) + 1;
                    $leave->latest_escalated_at = now();
                    $leave->save();

                    $this->createApprovalLog($leave, $step, 'escalated', null, [
                        'escalated_to_role' => $step->approver_role,
                        'escalated_to_user_id' => $step->approver_id,
                    ]);
                }
            }

            return $steps->count();
        });
    }

    public function deleteLeaveRequest(LeaveRequest $leaveRequest): void
    {
        DB::transaction(function () use ($leaveRequest) {
            $payload = $leaveRequest->toArray();
            $leaveRequest->delete();

            $this->logAudit('LeaveRequest', $leaveRequest->getKey(), 'deleted', [
                'attributes' => $payload,
            ]);
        });
    }

    private function initializeWorkflow(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->approvalSteps()->exists()) {
            return;
        }

        $definitions = LeaveWorkflowStep::query()
            ->where(function ($query) use ($leaveRequest) {
                $query->whereNull('leave_type_id')
                    ->orWhere('leave_type_id', $leaveRequest->leave_type_id);
            })
            ->orderBy('level')
            ->get();

        if ($definitions->isEmpty()) {
            $step = LeaveRequestApprovalStep::create([
                'leave_request_id' => $leaveRequest->getKey(),
                'level' => 1,
                'name' => 'Manager Approval',
                'approver_role' => 'HR Manager',
                'status' => 'pending',
                'due_at' => $this->dueAtFromHours(config('hr.leave_workflow.default_due_hours', 48)),
            ]);

            $leaveRequest->current_step_level = $step->level;
            $leaveRequest->save();

            return;
        }

        foreach ($definitions as $definition) {
            LeaveRequestApprovalStep::create([
                'leave_request_id' => $leaveRequest->getKey(),
                'workflow_step_id' => $definition->getKey(),
                'level' => $definition->level,
                'name' => $definition->name,
                'approver_role' => $definition->approver_role,
                'approver_id' => $definition->approver_id,
                'status' => 'pending',
                'due_at' => $this->dueAtFromHours($definition->escalate_after_hours),
                'escalate_to_role' => $definition->escalate_to_role,
                'escalate_to_user_id' => $definition->escalate_to_user_id,
                'metadata' => $definition->metadata,
            ]);
        }

        $leaveRequest->current_step_level = $definitions->first()->level;
        $leaveRequest->save();
    }

    private function resolveActor(): User
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'actor' => ['Authenticated user context is required to perform this action.'],
            ]);
        }

        return $actor->loadMissing('role');
    }

    private function resolveCurrentStep(LeaveRequest $leaveRequest, bool $lock = false): LeaveRequestApprovalStep
    {
        $query = LeaveRequestApprovalStep::query()
            ->where('leave_request_id', $leaveRequest->getKey())
            ->where('status', 'pending')
            ->orderBy('level');

        if ($lock) {
            $query->lockForUpdate();
        }

        $step = $query->first();

        if (! $step) {
            throw ValidationException::withMessages([
                'workflow' => ['No active approval step is available for this request.'],
            ]);
        }

        return $step;
    }

    private function assertActorCanActOnStep(LeaveRequestApprovalStep $step, User $actor): void
    {
        if ($step->status !== 'pending') {
            throw ValidationException::withMessages([
                'workflow' => ['This approval step is no longer pending.'],
            ]);
        }

        $actorRole = optional($actor->role)->name;
        $actorRoleKey = $this->normalizeRoleValue($actorRole);
        $stepRoleKey = $this->normalizeRoleValue($step->approver_role);

        $matchesRole = $stepRoleKey
            && $actorRoleKey
            && (
                $actorRoleKey === $stepRoleKey
                || str_contains($actorRoleKey, $stepRoleKey)
                || str_contains($stepRoleKey, $actorRoleKey)
            );

        $matchesApprover = $step->approver_id && $step->approver_id === $actor->getKey();
        $matchesDelegate = $step->delegated_to && $step->delegated_to === $actor->getKey();

        $matchesSuperAdmin = $actorRoleKey === $this->normalizeRoleValue('Super Admin');

        if (! ($matchesRole || $matchesApprover || $matchesDelegate || $matchesSuperAdmin)) {
            throw ValidationException::withMessages([
                'workflow' => ['You are not authorized to act on this approval step.'],
            ]);
        }
    }

    private function advanceWorkflow(LeaveRequest $leaveRequest): ?LeaveRequestApprovalStep
    {
        $next = LeaveRequestApprovalStep::query()
            ->where('leave_request_id', $leaveRequest->getKey())
            ->where('status', 'pending')
            ->orderBy('level')
            ->first();

        $leaveRequest->current_step_level = $next?->level;
        $leaveRequest->save();

        return $next;
    }

    private function calculateDueAt(LeaveRequestApprovalStep $step): ?Carbon
    {
        $hours = optional($step->workflowStep)->escalate_after_hours;

        if ($hours) {
            return now()->addHours($hours);
        }

        $defaultHours = config('hr.leave_workflow.default_due_hours', 48);

        return $defaultHours ? now()->addHours($defaultHours) : null;
    }

    private function dueAtFromHours(?int $hours): ?Carbon
    {
        if (! $hours) {
            $default = config('hr.leave_workflow.default_due_hours', 48);

            return $default > 0 ? now()->addHours($default) : null;
        }

        return now()->addHours($hours);
    }

    private function normalizeRoleValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_replace('/[^a-z0-9]/', '', strtolower($value));
    }

    private function mergeRequestMetadata(LeaveRequest $leaveRequest, array $data): void
    {
        $metadata = $leaveRequest->metadata ?? [];
        $leaveRequest->metadata = array_merge($metadata, $data);
        $leaveRequest->save();
    }

    private function createApprovalLog(
        LeaveRequest $leaveRequest,
        ?LeaveRequestApprovalStep $step,
        string $action,
        ?string $comments = null,
        array $metadata = []
    ): void {
        LeaveApprovalLog::create([
            'leave_request_id' => $leaveRequest->getKey(),
            'workflow_step_id' => $step?->getKey(),
            'actor_id' => Auth::id(),
            'action' => $action,
            'comments' => $comments,
            'metadata' => $metadata,
        ]);
    }

    private function preparePayload(
        int $employeeId,
        array $data,
        ?int $ignoreRequestId = null,
        ?int $currentLeaveTypeId = null,
        ?string $currentStartDate = null,
        ?string $currentEndDate = null
    ): array
    {
        $leaveTypeId = $data['leave_type_id'] ?? $currentLeaveTypeId;

        if (! $leaveTypeId) {
            throw ValidationException::withMessages([
                'leave_type_id' => ['Leave type selection is required.'],
            ]);
        }

        $startDate = $data['start_date'] ?? $currentStartDate;
        $endDate = $data['end_date'] ?? $currentEndDate;

        if (! $startDate || ! $endDate) {
            throw ValidationException::withMessages([
                'start_date' => ['Start and end dates are required.'],
            ]);
        }

        $this->assertValidDates($startDate, $endDate);

        $totalDays = $this->calculateTotalDays($startDate, $endDate);

        $this->assertSufficientBalance($employeeId, $leaveTypeId, $totalDays, $ignoreRequestId);

        return [
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ];
    }

    private function assertValidDates(string $startDate, string $endDate): void
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => ['End date must be on or after the start date.'],
            ]);
        }
    }

    private function calculateTotalDays(string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return $start->diffInDays($end) + 1;
    }

    private function assertSufficientBalance(int $employeeId, int $leaveTypeId, float $requestedDays, ?int $ignoreRequestId = null): void
    {
        $leaveType = LeaveType::findOrFail($leaveTypeId);

        $allocation = $leaveType->max_balance ?? $leaveType->default_allocation_days;

        if ($allocation === null) {
            return;
        }

        $query = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereIn('status', ['pending', 'approved']);

        if ($ignoreRequestId) {
            $query->where('id', '!=', $ignoreRequestId);
        }

        $usedDays = (float) $query->sum('total_days');

        $remaining = $allocation - $usedDays;

        if ($requestedDays > $remaining) {
            throw ValidationException::withMessages([
                'total_days' => ['Requested leave exceeds available balance.'],
            ]);
        }
    }

    private function allowedTransitions(): array
    {
        return [
            'pending' => ['approved', 'rejected', 'canceled'],
            'approved' => ['canceled'],
            'rejected' => [],
            'canceled' => [],
        ];
    }

    private function logAudit(string $entityType, int $entityId, string $action, array $payload = []): void
    {
        AuditLog::create([
            'actor_id' => Auth::id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'payload_json' => $payload,
            'created_at' => now(),
        ]);
    }
}
