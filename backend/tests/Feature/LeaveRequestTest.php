<?php

namespace Tests\Feature;

use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveType;
use App\Models\Talent\AuditLog;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_and_hr_can_approve_leave_request(): void
    {
        $employee = $this->createUser('employee@example.com');
        $approver = $this->createUser('approver@example.com');

        $leaveType = LeaveType::query()->create([
            'name' => 'Sick Leave',
            'code' => 'SICK',
            'description' => 'Sick leave allocation',
            'default_allocation_days' => 10,
            'max_balance' => 10,
            'accrual_method' => 'none',
            'is_paid' => true,
            'requires_approval' => true,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-12',
            'reason' => 'Recovering from flu',
        ]);

        $response->assertCreated();
        $submissionId = $response->json('data.id');

        $this->assertNotNull($submissionId);
        $this->assertSame(3, $response->json('data.total_days'));
        $this->assertSame('pending', $response->json('data.status'));

        Sanctum::actingAs($approver);

        $statusResponse = $this->postJson("/hr/leave-requests/{$submissionId}/status", [
            'status' => 'approved',
        ]);

        $statusResponse->assertOk();
        $statusResponse->assertJsonPath('data.status', 'approved');
        $this->assertEquals($approver->id, $statusResponse->json('data.approver_id'));

        $this->assertCount(2, AuditLog::all());
    }

    public function test_leave_request_validates_balance_and_dates(): void
    {
        $employee = $this->createUser('employee@example.com');

        $leaveType = LeaveType::query()->create([
            'name' => 'Annual Leave',
            'code' => 'ANNUAL',
            'description' => 'Annual leave allocation',
            'default_allocation_days' => 5,
            'max_balance' => 5,
            'accrual_method' => 'monthly',
            'is_paid' => true,
            'requires_approval' => true,
        ]);

        Sanctum::actingAs($employee);

        $first = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-03',
            'reason' => 'Family trip',
        ]);
        $first->assertCreated();

        $exceeds = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-03-01',
            'end_date' => '2025-03-05',
            'reason' => 'Long vacation',
        ]);
        $exceeds->assertStatus(422);
        $exceeds->assertJsonValidationErrors(['total_days']);

        $invalidDates = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-04-10',
            'end_date' => '2025-04-08',
            'reason' => 'Invalid range',
        ]);
        $invalidDates->assertStatus(422);
        $invalidDates->assertJsonValidationErrors(['end_date']);
    }

    public function test_leave_request_update_and_cancellation_flow(): void
    {
        $employee = $this->createUser('employee@example.com');

        $leaveType = LeaveType::query()->create([
            'name' => 'Personal Leave',
            'code' => 'PERSONAL',
            'description' => 'Personal errands',
            'default_allocation_days' => 7,
            'max_balance' => 7,
            'accrual_method' => 'none',
            'is_paid' => true,
            'requires_approval' => true,
        ]);

        Sanctum::actingAs($employee);

        $store = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-05-01',
            'end_date' => '2025-05-03',
            'reason' => 'Personal matters',
        ]);
        $store->assertCreated();

        $leaveRequestId = $store->json('data.id');

        $update = $this->putJson("/hr/leave-requests/{$leaveRequestId}", [
            'end_date' => '2025-05-02',
            'reason' => 'Shorter trip',
        ]);
        $update->assertOk();
        $update->assertJsonPath('data.total_days', 2);
        $update->assertJsonPath('data.reason', 'Shorter trip');

        $cancel = $this->postJson("/hr/leave-requests/{$leaveRequestId}/status", [
            'status' => 'canceled',
            'cancellation_reason' => 'Plans changed',
        ]);
        $cancel->assertOk();
        $cancel->assertJsonPath('data.status', 'canceled');
        $this->assertSame('Plans changed', $cancel->json('data.cancellation_reason'));

        $this->assertSame(3, AuditLog::query()->count());

        $this->deleteJson("/hr/leave-requests/{$leaveRequestId}")->assertOk();

        $this->assertSoftDeleted(LeaveRequest::class, ['id' => $leaveRequestId]);
        $this->assertGreaterThanOrEqual(4, AuditLog::query()->count());
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'department' => 'HR',
            'password' => Hash::make('password'),
        ]);
    }
}
