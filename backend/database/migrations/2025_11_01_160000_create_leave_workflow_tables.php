<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            $table->unsignedInteger('level');
            $table->string('name')->nullable();
            $table->string('approver_role')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('escalate_after_hours')->nullable();
            $table->string('escalate_to_role')->nullable();
            $table->foreignId('escalate_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('requires_all')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['leave_type_id', 'level']);
        });

        Schema::create('leave_request_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('leave_workflow_steps')->nullOnDelete();
            $table->unsignedInteger('level');
            $table->string('name')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_role')->nullable();
            $table->foreignId('delegated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delegated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delegated_at')->nullable();
            $table->text('delegation_note')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalate_to_role')->nullable();
            $table->foreignId('escalate_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('escalation_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['leave_request_id', 'level']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('leave_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('leave_request_approval_steps')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['leave_request_id', 'created_at']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedInteger('current_step_level')->nullable()->after('metadata');
            $table->timestamp('workflow_completed_at')->nullable()->after('current_step_level');
            $table->unsignedInteger('escalation_count')->default(0)->after('workflow_completed_at');
            $table->timestamp('latest_escalated_at')->nullable()->after('escalation_count');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['current_step_level', 'workflow_completed_at', 'escalation_count', 'latest_escalated_at']);
        });

        Schema::dropIfExists('leave_approval_logs');
        Schema::dropIfExists('leave_request_approval_steps');
        Schema::dropIfExists('leave_workflow_steps');
    }
};
