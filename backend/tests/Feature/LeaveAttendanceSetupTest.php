<?php

namespace Tests\Feature;

use App\Models\Talent\AuditLog;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveAttendanceSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_attendance_configuration_flow(): void
    {
        $admin = User::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'department' => 'HR',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($admin);

        $leaveTypePayload = [
            'name' => 'Annual Leave',
            'code' => 'ANNUAL',
            'description' => 'Paid annual leave allocation for employees',
            'is_paid' => true,
            'requires_approval' => true,
            'default_allocation_days' => 24,
            'max_balance' => 45,
            'carry_forward_limit' => 10,
            'accrual_method' => 'monthly',
            'metadata' => ['workflow' => 'hr-manager-approval'],
        ];

        $leaveTypeResponse = $this->postJson('/hr/leave-types', $leaveTypePayload);
        $leaveTypeResponse->assertCreated();
        $leaveTypeId = $leaveTypeResponse->json('data.id');

        $this->assertNotNull($leaveTypeId);

        $duplicateLeaveType = $this->postJson('/hr/leave-types', array_merge($leaveTypePayload, [
            'code' => 'ANNUAL-2',
        ]));
        $duplicateLeaveType->assertStatus(422);

        $accrualPayload = [
            'leave_type_id' => $leaveTypeId,
            'frequency' => 'monthly',
            'amount' => 2,
            'max_balance' => 45,
            'carry_forward_limit' => 10,
            'onboarding_waiting_period_days' => 30,
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'eligibility_criteria' => ['grade' => ['P3', 'P4']],
        ];

        $accrualResponse = $this->postJson('/hr/leave-accrual-rules', $accrualPayload);
        $accrualResponse->assertCreated();
        $accrualRuleId = $accrualResponse->json('data.id');

        $this->assertNotNull($accrualRuleId);

        $overlappingAccrual = $this->postJson('/hr/leave-accrual-rules', $accrualPayload);
        $overlappingAccrual->assertStatus(422);

        $calendarPayload = [
            'name' => 'Standard HQ Calendar',
            'timezone' => 'America/New_York',
            'description' => 'Corporate headquarters working calendar',
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'daily_start_time' => '09:00',
            'daily_end_time' => '17:30',
            'metadata' => ['location' => 'NYC'],
        ];

        $calendarResponse = $this->postJson('/hr/work-calendars', $calendarPayload);
        $calendarResponse->assertCreated();
        $calendarId = $calendarResponse->json('data.id');

        $this->assertNotNull($calendarId);

        $overlappingCalendar = $this->postJson('/hr/work-calendars', $calendarPayload);
        $overlappingCalendar->assertStatus(422);

        $holidayPayload = [
            'work_calendar_id' => $calendarId,
            'name' => 'Independence Day',
            'holiday_date' => '2025-07-04',
            'is_recurring' => true,
            'description' => 'Company holiday for Independence Day',
        ];

        $holidayResponse = $this->postJson('/hr/work-calendar-holidays', $holidayPayload);
        $holidayResponse->assertCreated();
        $holidayId = $holidayResponse->json('data.id');

        $this->assertNotNull($holidayId);

        $duplicateHoliday = $this->postJson('/hr/work-calendar-holidays', $holidayPayload);
        $duplicateHoliday->assertStatus(422);

        $listResponse = $this->getJson('/hr/leave-types');
        $listResponse->assertOk();
        $this->assertSame('Annual Leave', $listResponse->json('data.data.0.name'));

        $calendarList = $this->getJson('/hr/work-calendars');
        $calendarList->assertOk();
        $this->assertSame('Standard HQ Calendar', $calendarList->json('data.data.0.name'));

        $holidayList = $this->getJson('/hr/work-calendar-holidays');
        $holidayList->assertOk();
        $this->assertSame('Independence Day', $holidayList->json('data.data.0.name'));

        $this->assertEquals(4, AuditLog::query()->count());
    }
}
