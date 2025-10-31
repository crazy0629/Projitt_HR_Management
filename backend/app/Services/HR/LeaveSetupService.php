<?php

namespace App\Services\HR;

use App\Models\HR\LeaveAccrualRule;
use App\Models\HR\LeaveType;
use App\Models\HR\WorkCalendar;
use App\Models\HR\WorkCalendarHoliday;
use App\Models\Talent\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveSetupService
{
    /**
     * @return Collection<int, LeaveType>|LengthAwarePaginator<LeaveType>
     */
    public function listLeaveTypes(bool $paginate = true, int $perPage = 15)
    {
        $query = LeaveType::query()
            ->with('accrualRules')
            ->orderBy('name');

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function createLeaveType(array $data): LeaveType
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            $leaveType = LeaveType::create(array_merge($data, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            $this->logAudit('LeaveType', $leaveType->getKey(), 'created', [
                'attributes' => $leaveType->toArray(),
            ]);

            return $leaveType->fresh('accrualRules');
        });
    }

    public function updateLeaveType(LeaveType $leaveType, array $data): LeaveType
    {
        return DB::transaction(function () use ($leaveType, $data) {
            $leaveType->fill($data);

            if (! $leaveType->isDirty()) {
                return $leaveType->fresh('accrualRules');
            }

            $changes = $leaveType->getDirty();
            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $leaveType->getOriginal($field);
            }

            $leaveType->updated_by = Auth::id();
            $leaveType->save();

            $this->logAudit('LeaveType', $leaveType->getKey(), 'updated', [
                'before' => $before,
                'after' => $leaveType->only(array_keys($changes)),
            ]);

            return $leaveType->fresh('accrualRules');
        });
    }

    /**
     * @return Collection<int, LeaveAccrualRule>|LengthAwarePaginator<LeaveAccrualRule>
     */
    public function listAccrualRules(bool $paginate = true, int $perPage = 15)
    {
        $query = LeaveAccrualRule::query()
            ->with('leaveType')
            ->orderByDesc('effective_from');

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function createAccrualRule(array $data): LeaveAccrualRule
    {
        return DB::transaction(function () use ($data) {
            $this->assertAccrualRuleDoesNotOverlap(
                $data['leave_type_id'],
                $data['frequency'],
                $data['effective_from'],
                $data['effective_to'] ?? null
            );

            $userId = Auth::id();

            $rule = LeaveAccrualRule::create(array_merge($data, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            $this->logAudit('LeaveAccrualRule', $rule->getKey(), 'created', [
                'attributes' => $rule->toArray(),
            ]);

            return $rule->fresh('leaveType');
        });
    }

    public function updateAccrualRule(LeaveAccrualRule $rule, array $data): LeaveAccrualRule
    {
        return DB::transaction(function () use ($rule, $data) {
            $payload = array_merge($rule->only(['leave_type_id', 'frequency', 'effective_from', 'effective_to']), $data);

            $this->assertAccrualRuleDoesNotOverlap(
                $payload['leave_type_id'],
                $payload['frequency'],
                $payload['effective_from'],
                $payload['effective_to'] ?? null,
                $rule->getKey()
            );

            $rule->fill($data);

            if (! $rule->isDirty()) {
                return $rule->fresh('leaveType');
            }

            $changes = $rule->getDirty();
            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $rule->getOriginal($field);
            }

            $rule->updated_by = Auth::id();
            $rule->save();

            $this->logAudit('LeaveAccrualRule', $rule->getKey(), 'updated', [
                'before' => $before,
                'after' => $rule->only(array_keys($changes)),
            ]);

            return $rule->fresh('leaveType');
        });
    }

    /**
     * @return Collection<int, WorkCalendar>|LengthAwarePaginator<WorkCalendar>
     */
    public function listWorkCalendars(bool $paginate = true, int $perPage = 15)
    {
        $query = WorkCalendar::query()
            ->with('holidays')
            ->orderByDesc('effective_from');

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function createWorkCalendar(array $data): WorkCalendar
    {
        return DB::transaction(function () use ($data) {
            $this->assertCalendarDoesNotOverlap(
                $data['effective_from'],
                $data['effective_to'] ?? null
            );

            $userId = Auth::id();

            $calendar = WorkCalendar::create(array_merge($data, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            $this->logAudit('WorkCalendar', $calendar->getKey(), 'created', [
                'attributes' => $calendar->toArray(),
            ]);

            return $calendar->fresh('holidays');
        });
    }

    public function updateWorkCalendar(WorkCalendar $calendar, array $data): WorkCalendar
    {
        return DB::transaction(function () use ($calendar, $data) {
            $payload = array_merge($calendar->only(['effective_from', 'effective_to']), $data);

            $this->assertCalendarDoesNotOverlap(
                $payload['effective_from'],
                $payload['effective_to'] ?? null,
                $calendar->getKey()
            );

            $calendar->fill($data);

            if (! $calendar->isDirty()) {
                return $calendar->fresh('holidays');
            }

            $changes = $calendar->getDirty();
            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $calendar->getOriginal($field);
            }

            $calendar->updated_by = Auth::id();
            $calendar->save();

            $this->logAudit('WorkCalendar', $calendar->getKey(), 'updated', [
                'before' => $before,
                'after' => $calendar->only(array_keys($changes)),
            ]);

            return $calendar->fresh('holidays');
        });
    }

    /**
     * @return Collection<int, WorkCalendarHoliday>|LengthAwarePaginator<WorkCalendarHoliday>
     */
    public function listHolidays(bool $paginate = true, int $perPage = 15)
    {
        $query = WorkCalendarHoliday::query()
            ->with('calendar')
            ->orderByDesc('holiday_date');

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function createHoliday(array $data): WorkCalendarHoliday
    {
        return DB::transaction(function () use ($data) {
            $this->assertHolidayIsUnique(
                $data['work_calendar_id'] ?? null,
                $data['holiday_date'],
                $data['name']
            );

            $userId = Auth::id();

            $holiday = WorkCalendarHoliday::create(array_merge($data, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            $this->logAudit('WorkCalendarHoliday', $holiday->getKey(), 'created', [
                'attributes' => $holiday->toArray(),
            ]);

            return $holiday->fresh('calendar');
        });
    }

    public function updateHoliday(WorkCalendarHoliday $holiday, array $data): WorkCalendarHoliday
    {
        return DB::transaction(function () use ($holiday, $data) {
            $payload = array_merge($holiday->only(['work_calendar_id', 'holiday_date', 'name']), $data);

            $this->assertHolidayIsUnique(
                $payload['work_calendar_id'] ?? null,
                $payload['holiday_date'],
                $payload['name'],
                $holiday->getKey()
            );

            $holiday->fill($data);

            if (! $holiday->isDirty()) {
                return $holiday->fresh('calendar');
            }

            $changes = $holiday->getDirty();
            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $holiday->getOriginal($field);
            }

            $holiday->updated_by = Auth::id();
            $holiday->save();

            $this->logAudit('WorkCalendarHoliday', $holiday->getKey(), 'updated', [
                'before' => $before,
                'after' => $holiday->only(array_keys($changes)),
            ]);

            return $holiday->fresh('calendar');
        });
    }

    private function assertAccrualRuleDoesNotOverlap(
        int $leaveTypeId,
        string $frequency,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $ignoreId = null
    ): void {
        $query = LeaveAccrualRule::query()
            ->where('leave_type_id', $leaveTypeId)
            ->where('frequency', $frequency);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $fromDate = Carbon::parse($effectiveFrom)->startOfDay();
        $toDate = $effectiveTo ? Carbon::parse($effectiveTo)->endOfDay() : $fromDate->copy()->endOfDay();

        $query->where(function ($q) use ($fromDate, $toDate) {
            $q->where(function ($inner) use ($fromDate) {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $fromDate->toDateString());
            })
            ->whereDate('effective_from', '<=', $toDate->toDateString());
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => ['Accrual rule overlaps with an existing configuration for this leave type.'],
            ]);
        }
    }

    private function assertCalendarDoesNotOverlap(string $effectiveFrom, ?string $effectiveTo, ?int $ignoreId = null): void
    {
        $query = WorkCalendar::query();

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $fromDate = Carbon::parse($effectiveFrom)->startOfDay();
        $toDate = $effectiveTo ? Carbon::parse($effectiveTo)->endOfDay() : $fromDate->copy()->endOfDay();

        $query->where(function ($q) use ($fromDate, $toDate) {
            $q->where(function ($inner) use ($fromDate) {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $fromDate->toDateString());
            })
            ->whereDate('effective_from', '<=', $toDate->toDateString());
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => ['Work calendar overlaps with an existing calendar effective period.'],
            ]);
        }
    }

    private function assertHolidayIsUnique(?int $calendarId, string $holidayDate, string $name, ?int $ignoreId = null): void
    {
        $query = WorkCalendarHoliday::query()
            ->whereDate('holiday_date', Carbon::parse($holidayDate)->toDateString())
            ->where('name', $name);

        if ($calendarId) {
            $query->where('work_calendar_id', $calendarId);
        } else {
            $query->whereNull('work_calendar_id');
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'holiday_date' => ['A holiday with the same name already exists for the selected calendar and date.'],
            ]);
        }
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
