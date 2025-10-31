<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->decimal('default_allocation_days', 8, 2)->default(0);
            $table->decimal('max_balance', 8, 2)->nullable();
            $table->decimal('carry_forward_limit', 8, 2)->nullable();
            $table->enum('accrual_method', ['none', 'monthly', 'annual'])->default('none');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('leave_accrual_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'annually']);
            $table->decimal('amount', 8, 2);
            $table->decimal('max_balance', 8, 2)->nullable();
            $table->decimal('carry_forward_limit', 8, 2)->nullable();
            $table->unsignedInteger('onboarding_waiting_period_days')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('eligibility_criteria')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['leave_type_id', 'frequency', 'effective_from'], 'leave_accrual_rules_unique');
            $table->index(['leave_type_id', 'effective_from']);
        });

        Schema::create('work_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('timezone')->default('UTC');
            $table->text('description')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('working_days');
            $table->time('daily_start_time')->nullable();
            $table->time('daily_end_time')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('name');
            $table->index(['effective_from', 'effective_to']);
        });

        Schema::create('work_calendar_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_calendar_id')->nullable()->constrained('work_calendars')->nullOnDelete();
            $table->string('name');
            $table->date('holiday_date');
            $table->boolean('is_recurring')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['work_calendar_id', 'holiday_date', 'name'], 'work_calendar_holidays_unique');
            $table->index(['holiday_date', 'is_recurring']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_calendar_holidays');
        Schema::dropIfExists('work_calendars');
        Schema::dropIfExists('leave_accrual_rules');
        Schema::dropIfExists('leave_types');
    }
};
