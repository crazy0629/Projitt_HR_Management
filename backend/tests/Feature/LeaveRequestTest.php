<?php

namespace Tests\Feature;

use App\Models\HR\LeaveApprovalLog;
use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveRequestApprovalStep;
use App\Models\HR\LeaveType;
use App\Models\HR\LeaveWorkflowStep;
use App\Models\Role\Role;
use App\Models\Talent\AuditLog;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_level_leave_approval_flow(): void
    {
        $employee = $this->createUser('employee@example.com');
        $managerRole = $this->createRole('Manager');
        $hrRole = $this->createRole('HR');

        $manager = $this->createUser('manager@example.com', $managerRole->id);
        $hr = $this->createUser('hr@example.com', $hrRole->id);

        $leaveType = $this->createLeaveType('Sick Leave', 'SICK');

        LeaveWorkflowStep::query()->create([
            'leave_type_id' => $leaveType->id,
            'level' => 1,
            'name' => 'Manager Approval',
            'approver_role' => 'Manager',
            'escalate_after_hours' => 24,
            'escalate_to_role' => 'HR',
        ]);

        LeaveWorkflowStep::query()->create([
            'leave_type_id' => $leaveType->id,
            'level' => 2,
            'name' => 'HR Approval',
            'approver_role' => 'HR',
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

        $this->assertSame('pending', $response->json('data.status'));
        $this->assertSame(3, $response->json('data.total_days'));

        $steps = LeaveRequestApprovalStep::query()
            ->where('leave_request_id', $submissionId)
            ->orderBy('level')
            ->get();

        $this->assertCount(2, $steps);
        $this->assertEquals(1, $steps->first()->level);

        Sanctum::actingAs($manager);

        $managerResponse = $this->postJson("/hr/leave-requests/{$submissionId}/approve", [
            'comments' => 'Reviewed and approved at level 1',
        ]);

        $managerResponse->assertOk();
        $managerResponse->assertJsonPath('data.status', 'pending');
        $managerResponse->assertJsonPath('data.current_step_level', 2);

        Sanctum::actingAs($hr);

        $hrResponse = $this->postJson("/hr/leave-requests/{$submissionId}/approve");

        $hrResponse->assertOk();
        $hrResponse->assertJsonPath('data.status', 'approved');
        $this->assertNotNull($hrResponse->json('data.workflow_completed_at'));
        $this->assertEquals($hr->id, $hrResponse->json('data.approver_id'));

        $this->assertGreaterThanOrEqual(2, LeaveApprovalLog::query()->count());
        $this->assertGreaterThanOrEqual(2, AuditLog::query()->count());
    }

    public function test_manager_can_reject_leave_request(): void
    {
        $employee = $this->createUser('employee@example.com');
        $managerRole = $this->createRole('Manager');
        $manager = $this->createUser('manager@example.com', $managerRole->id);

        $leaveType = $this->createLeaveType('Personal Leave', 'PERSONAL');

        LeaveWorkflowStep::query()->create([
            'leave_type_id' => $leaveType->id,
            'level' => 1,
            'name' => 'Manager Approval',
            'approver_role' => 'Manager',
        ]);

        Sanctum::actingAs($employee);
        $response = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-04-01',
            'end_date' => '2025-04-02',
            'reason' => 'Personal matters',
        ]);
        $response->assertCreated();

        $submissionId = $response->json('data.id');

        Sanctum::actingAs($manager);
        $rejectResponse = $this->postJson("/hr/leave-requests/{$submissionId}/reject", [
            'reason' => 'Team workload is high',
            'comments' => 'Please reschedule',
        ]);

        $rejectResponse->assertOk();
        $rejectResponse->assertJsonPath('data.status', 'rejected');
        $this->assertSame('Team workload is high', $rejectResponse->json('data.metadata.rejection_reason'));

        $this->assertTrue(LeaveApprovalLog::query()->where('action', 'rejected')->exists());
    }

    public function test_workflow_delegation_and_escalation(): void
    {
        $employee = $this->createUser('employee@example.com');
        $managerRole = $this->createRole('Manager');
        $hrRole = $this->createRole('HR');

        $manager = $this->createUser('manager@example.com', $managerRole->id);
        $backupManager = $this->createUser('backup@example.com', $managerRole->id);
        $hr = $this->createUser('hr@example.com', $hrRole->id);

        $leaveType = $this->createLeaveType('Annual Leave', 'ANNUAL');

        LeaveWorkflowStep::query()->create([
            'leave_type_id' => $leaveType->id,
            'level' => 1,
            'name' => 'Manager Approval',
            'approver_role' => 'Manager',
            'escalate_after_hours' => 1,
            'escalate_to_role' => 'HR',
        ]);

        Sanctum::actingAs($employee);
        $response = $this->postJson('/hr/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-03',
            'reason' => 'Family vacation',
        ]);
        $response->assertCreated();

        $submissionId = $response->json('data.id');

        Sanctum::actingAs($manager);
        $delegateResponse = $this->postJson("/hr/leave-requests/{$submissionId}/delegate", [
            'delegate_to' => $backupManager->id,
            'note' => 'Out of office, delegating to backup manager',
        ]);
        $delegateResponse->assertOk();
        $delegateResponse->assertJsonPath('data.status', 'pending');

        $step = LeaveRequestApprovalStep::query()->where('leave_request_id', $submissionId)->first();
        $this->assertEquals($backupManager->id, $step->delegated_to);

        // simulate overdue by backdating due_at
        $step->update(['due_at' => now()->subHours(2)]);

        Sanctum::actingAs($hr);
        $escalateResponse = $this->postJson('/hr/leave-requests/escalations/run');
        $escalateResponse->assertOk();
        $this->assertSame(1, $escalateResponse->json('data.processed_steps'));

        $step->refresh();
        $this->assertEquals('HR', $step->approver_role);
        $this->assertGreaterThan(0, $step->escalation_count);

        $leaveRequest = LeaveRequest::find($submissionId);
        $this->assertGreaterThan(0, $leaveRequest->escalation_count);
        $this->assertNotNull($leaveRequest->latest_escalated_at);

        $this->assertTrue(LeaveApprovalLog::query()->where('action', 'escalated')->exists());
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

    private function createLeaveType(string $name, string $code): LeaveType
    {
        return LeaveType::query()->create([
            'name' => $name,
            'code' => $code,
            'description' => $name.' allocation',
            'default_allocation_days' => 10,
            'max_balance' => 10,
            'accrual_method' => 'none',
            'is_paid' => true,
            'requires_approval' => true,
        ]);
    }
}
