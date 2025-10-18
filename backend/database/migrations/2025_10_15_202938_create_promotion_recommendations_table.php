<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_recommendations', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('review_cycle_id')->nullable()->constrained('performance_review_cycles')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete(); // promoted person
            $table->foreignId('proposed_by_id')->constrained('users')->cascadeOnDelete(); // manager
            $table->foreignId('current_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('proposed_role_id')->constrained('roles')->cascadeOnDelete();

            // Business details
            $table->text('justification');
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->decimal('proposed_salary', 12, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Compensation band adjustments
            $table->decimal('comp_adjustment_min', 12, 2)->nullable();
            $table->decimal('comp_adjustment_max', 12, 2)->nullable();

            // Workflow + approval process
            $table->string('workflow_id')->nullable();
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'withdrawn'
            ])->default('pending');
            $table->json('meta')->nullable();
            $table->text('approval_notes')->nullable();

            // Approval tracking
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Effectivity
            $table->timestamp('effective_date')->nullable();

            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['employee_id', 'status']);
            $table->index(['proposed_by_id', 'status']);
            $table->index(['review_cycle_id', 'status']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_recommendations');
    }
};
