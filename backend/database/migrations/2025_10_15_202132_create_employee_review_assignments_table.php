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
        Schema::create('employee_review_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('performance_review_cycles');
            $table->foreignId('reviewee_id')->constrained('users'); // Person being reviewed
            $table->foreignId('reviewer_id')->constrained('users'); // Person doing the review
            $table->string('review_type')->default('manager'); // manager, self, peer, subordinate
            $table->foreignId('form_id')->constrained('employee_review_forms');
            $table->string('status')->default('pending'); // pending, in_progress, completed, overdue
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('assignment_metadata')->nullable(); // Context and instructions
            $table->text('completion_notes')->nullable();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();

            $table->unique(['cycle_id', 'reviewee_id', 'reviewer_id', 'review_type'], 'unique_review_assignment');
            $table->index(['status', 'due_date']);
            $table->index(['reviewer_id', 'status']);
            $table->index(['reviewee_id', 'review_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_review_assignments');
    }
};
