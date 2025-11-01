<?php

namespace App\Services\HR;

use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveType;
use App\Models\Talent\AuditLog;
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
            ->with(['employee', 'leaveType', 'approver'])
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

            $this->logAudit('LeaveRequest', $leaveRequest->getKey(), 'created', [
                'attributes' => $leaveRequest->toArray(),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver']);
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
                return $leaveRequest->fresh(['employee', 'leaveType', 'approver']);
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

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver']);
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

            $before = $leaveRequest->only(['status', 'approver_id', 'decided_at', 'canceled_by', 'cancellation_reason']);

            $leaveRequest->status = $normalizedStatus;
            $leaveRequest->updated_by = Auth::id();
            $leaveRequest->save();

            $this->logAudit('LeaveRequest', $leaveRequest->getKey(), 'status_changed', [
                'before' => $before,
                'after' => $leaveRequest->only(['status', 'approver_id', 'decided_at', 'canceled_by', 'cancellation_reason']),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver']);
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
