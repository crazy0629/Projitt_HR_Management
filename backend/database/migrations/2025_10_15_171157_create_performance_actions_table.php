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
        Schema::create('performance_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('performance_reviews')->onDelete('cascade');
            $table->enum('action_type', [
                'promotion',
                'succession_pool',
                'career_path',
                'assign_mentor',
                'learning_path',
                'improvement_plan',
                'role_change',
                'salary_adjustment',
            ]);
            $table->string('title'); // e.g., "Promote to Senior Developer"
            $table->text('description')->nullable();
            $table->foreignId('target_role_id')->nullable()->constrained('roles'); // For promotions
            $table->foreignId('mentor_id')->nullable()->constrained('users'); // For mentoring
            $table->foreignId('learning_path_id')->nullable()->constrained('learning_paths'); // Integration with Learning Paths
            $table->json('metadata')->nullable(); // Additional action-specific data
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('target_date')->nullable(); // When this action should be completed
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Who should execute this action
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['review_id', 'action_type']);
            $table->index(['status', 'priority']);
            $table->index('target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_actions');
    }
};
