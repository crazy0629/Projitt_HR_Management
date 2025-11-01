<?php

namespace Tests\Feature;

use App\Models\HR\AttendanceRecord;
use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveType;
use App\Models\Role\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in_and_out(): void
    {
        $role = $this->createRole('Employee');
        $employee = $this->createUser('employee@example.com', $role->id);

        Sanctum::actingAs($employee);

        $checkInTime = Carbon::parse('2025-07-01 09:05:00');

        $this->postJson('/hr/attendance/check-in', [
            'check_in_at' => $checkInTime->toIso8601String(),
        ])->assertOk()
            ->assertJsonPath('data.attendance_date', '2025-07-01');

        $checkOutTime = Carbon::parse('2025-07-01 17:15:00');

        $response = $this->postJson('/hr/attendance/check-out', [
            'check_out_at' => $checkOutTime->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total_minutes', $checkInTime->diffInMinutes($checkOutTime))
            ->assertJsonPath('data.is_missing', false)
            ->assertJsonPath('data.is_late', false);
    }

    public function test_duplicate_check_in_is_blocked(): void
    {
        $role = $this->createRole('Employee');
        $employee = $this->createUser('employee2@example.com', $role->id);

        Sanctum::actingAs($employee);

        $this->postJson('/hr/attendance/check-in', [
            'check_in_at' => '2025-07-02T09:00:00Z',
        ])->assertOk();

        $this->postJson('/hr/attendance/check-in', [
            'check_in_at' => '2025-07-02T09:10:00Z',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['check_in_at']);
    }

    public function test_ingest_flags_late_and_missing_logs(): void
    {
        $managerRole = $this->createRole('HR Manager');
        $manager = $this->createUser('manager@example.com', $managerRole->id);
        $employee = $this->createUser('employee3@example.com', $managerRole->id);

        Sanctum::actingAs($manager);

        $payload = [
            'employee_id' => $employee->id,
            'attendance_date' => '2025-07-03',
            'check_in_at' => '2025-07-03T10:00:00Z',
            'source' => 'manual',
        ];

        $response = $this->postJson('/hr/attendance/logs', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.is_late', true)
            ->assertJsonPath('data.is_missing', true)
            ->assertJsonPath('data.check_in_at', '2025-07-03T10:00:00.000000Z');
    }

    public function test_attendance_reconciles_with_leave(): void
    {
        $managerRole = $this->createRole('HR Manager');
        $manager = $this->createUser('manager2@example.com', $managerRole->id);
        $employeeRole = $this->createRole('Employee');
        $employee = $this->createUser('employee4@example.com', $employeeRole->id);

        $leaveType = LeaveType::query()->create([
            'name' => 'Paid Leave',
            'code' => 'PAID',
            'description' => 'Paid leave',
            'default_allocation_days' => 10,
            'max_balance' => 10,
            'accrual_method' => 'none',
            'is_paid' => true,
            'requires_approval' => true,
        ]);

        LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-07-04',
            'end_date' => '2025-07-04',
            'total_days' => 1,
            'status' => 'approved',
            'approver_id' => $manager->id,
            'decided_at' => now(),
        ]);

        Sanctum::actingAs($manager);

        $response = $this->postJson('/hr/attendance/logs', [
            'employee_id' => $employee->id,
            'attendance_date' => '2025-07-04',
            'source' => 'manual',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_leave_day', true)
            ->assertJsonPath('data.is_missing', false)
            ->assertJsonPath('data.leave_request_id', LeaveRequest::first()->id);

        $this->assertTrue(AttendanceRecord::query()->where('employee_id', $employee->id)->where('attendance_date', '2025-07-04')->where('is_leave_day', true)->exists());
    }

    private function createUser(string $email, ?int $roleId = null): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'department' => 'HR',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
        ]);
    }

    private function createRole(string $name): Role
    {
        return Role::query()->create([
            'name' => $name,
            'description' => $name.' role',
            'guard_name' => 'web',
            'created_by' => null,
            'updated_by' => null,
        ]);
    }
}
