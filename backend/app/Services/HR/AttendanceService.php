<?php

namespace App\Services\HR;

use App\Models\HR\AttendanceRecord;
use App\Models\HR\LeaveRequest;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * @return Collection<int, AttendanceRecord>|LengthAwarePaginator<AttendanceRecord>
     */
    public function listAttendance(bool $paginate = true, int $perPage = 15, array $filters = [])
    {
        $query = AttendanceRecord::query()
            ->with(['employee', 'leaveRequest'])
            ->orderByDesc('attendance_date');

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('attendance_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('attendance_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['is_missing'])) {
            $query->where('is_missing', (bool) $filters['is_missing']);
        }

        if (! empty($filters['is_late'])) {
            $query->where('is_late', (bool) $filters['is_late']);
        }

        return $paginate ? $query->paginate($perPage) : $query->get();
    }

    public function checkIn(User $employee, array $data = []): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $data) {
            $checkInAt = $this->parseTimestamp($data['check_in_at'] ?? null);
            $attendanceDate = $checkInAt->toDateString();

            $record = $this->findRecordForDate($employee->getKey(), $attendanceDate, true) ?? new AttendanceRecord([
                'employee_id' => $employee->getKey(),
                'attendance_date' => $attendanceDate,
            ]);

            if ($record->exists && $record->check_in_at) {
                throw ValidationException::withMessages([
                    'check_in_at' => ['Check-in already recorded for this date.'],
                ]);
            }

            $record->fill([
                'check_in_at' => $checkInAt,
                'attendance_date' => $attendanceDate,
                'source' => $data['source'] ?? ($record->source ?? 'self'),
                'notes' => $data['notes'] ?? $record->notes,
            ]);

            $this->computeAttendanceMeta($record);

            if (! $record->created_by) {
                $record->created_by = $employee->getKey();
            }

            $record->updated_by = $employee->getKey();
            $record->save();

            $this->reconcileWithLeave($record);

            return $record->fresh(['leaveRequest']);
        });
    }

    public function checkOut(User $employee, array $data = []): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $data) {
            $checkOutAt = $this->parseTimestamp($data['check_out_at'] ?? null);
            $attendanceDate = $checkOutAt->toDateString();

            $record = $this->findRecordForDate($employee->getKey(), $attendanceDate, true);

            if (! $record) {
                throw ValidationException::withMessages([
                    'attendance_date' => ['No check-in found for this date.'],
                ]);
            }

            if (! $record->check_in_at) {
                throw ValidationException::withMessages([
                    'check_out_at' => ['Cannot check out before checking in.'],
                ]);
            }

            if ($record->check_out_at) {
                throw ValidationException::withMessages([
                    'check_out_at' => ['Check-out already recorded for this date.'],
                ]);
            }

            if ($checkOutAt->lt($record->check_in_at)) {
                throw ValidationException::withMessages([
                    'check_out_at' => ['Check-out time cannot be earlier than check-in time.'],
                ]);
            }

            $record->fill([
                'check_out_at' => $checkOutAt,
                'source' => $data['source'] ?? $record->source,
                'notes' => $data['notes'] ?? $record->notes,
            ]);

            $this->computeAttendanceMeta($record);
            $record->updated_by = $employee->getKey();
            $record->save();

            $this->reconcileWithLeave($record);

            return $record->fresh(['leaveRequest']);
        });
    }

    public function ingest(array $payload): AttendanceRecord
    {
        return DB::transaction(function () use ($payload) {
            $employeeId = $payload['employee_id'];
            $attendanceDate = Carbon::parse($payload['attendance_date'])->toDateString();

            $record = $this->findRecordForDate($employeeId, $attendanceDate, true) ?? new AttendanceRecord([
                'employee_id' => $employeeId,
                'attendance_date' => $attendanceDate,
            ]);

            if ($record->exists && $record->check_in_at && ! empty($payload['check_in_at'])) {
                throw ValidationException::withMessages([
                    'check_in_at' => ['Attendance for this date already exists.'],
                ]);
            }

            if ($record->exists && $record->check_out_at && ! empty($payload['check_out_at'])) {
                throw ValidationException::withMessages([
                    'check_out_at' => ['Attendance for this date already exists.'],
                ]);
            }

            $checkInAt = isset($payload['check_in_at']) ? $this->parseTimestamp($payload['check_in_at']) : $record->check_in_at;
            $checkOutAt = isset($payload['check_out_at']) ? $this->parseTimestamp($payload['check_out_at']) : $record->check_out_at;

            if ($checkInAt && $checkOutAt && $checkOutAt->lt($checkInAt)) {
                throw ValidationException::withMessages([
                    'check_out_at' => ['Check-out time cannot be earlier than check-in time.'],
                ]);
            }

            $record->fill([
                'employee_id' => $employeeId,
                'attendance_date' => $attendanceDate,
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'source' => $payload['source'] ?? ($record->source ?? 'manual'),
                'notes' => $payload['notes'] ?? $record->notes,
                'metadata' => $payload['metadata'] ?? $record->metadata,
            ]);

            $actorId = Auth::id();
            if (! $record->created_by) {
                $record->created_by = $actorId;
            }

            $record->updated_by = $actorId;

            $this->computeAttendanceMeta($record);
            $record->save();

            $this->reconcileWithLeave($record);

            return $record->fresh(['leaveRequest']);
        });
    }

    private function computeAttendanceMeta(AttendanceRecord $record): void
    {
        if ($record->check_in_at && $record->check_out_at) {
            $record->total_minutes = (int) $record->check_in_at->diffInMinutes($record->check_out_at);
        } else {
            $record->total_minutes = null;
        }

        $record->is_late = $this->isLate($record->check_in_at);

        $record->is_missing = $this->isMissing($record);
    }

    private function isLate(?Carbon $checkInAt): bool
    {
        if (! $checkInAt) {
            return false;
        }

        $startTime = $this->resolveWorkdayStart($checkInAt);
        $graceMinutes = config('hr.attendance.late_grace_minutes', 15);

        return $checkInAt->gt($startTime->copy()->addMinutes($graceMinutes));
    }

    private function resolveWorkdayStart(Carbon $reference): Carbon
    {
        $start = config('hr.attendance.workday_start', '09:00');

        [$hour, $minute] = array_map('intval', explode(':', $start));

        return $reference->copy()->setTime($hour, $minute, 0);
    }

    private function isMissing(AttendanceRecord $record): bool
    {
        if ($record->is_leave_day) {
            return false;
        }

        return ! ($record->check_in_at && $record->check_out_at);
    }

    private function reconcileWithLeave(AttendanceRecord $record): void
    {
        $leave = LeaveRequest::query()
            ->where('employee_id', $record->employee_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $record->attendance_date)
            ->whereDate('end_date', '>=', $record->attendance_date)
            ->orderByDesc('decided_at')
            ->first();

        if ($leave) {
            $record->is_leave_day = true;
            $record->leave_request_id = $leave->getKey();
            $record->is_missing = false;
        } else {
            $record->is_leave_day = false;
            $record->leave_request_id = null;
            $record->is_missing = $this->isMissing($record);
        }

        if ($record->isDirty(['is_leave_day', 'leave_request_id', 'is_missing'])) {
            $record->save();
        }
    }

    private function parseTimestamp(?string $value): Carbon
    {
        return $value ? Carbon::parse($value) : now();
    }

    private function findRecordForDate(int $employeeId, string $attendanceDate, bool $lock = false): ?AttendanceRecord
    {
        $query = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $attendanceDate);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
