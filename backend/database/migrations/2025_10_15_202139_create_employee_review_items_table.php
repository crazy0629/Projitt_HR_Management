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
        Schema::create('employee_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('employee_review_assignments');
            $table->foreignId('reviewee_id')->constrained('users'); // Person being reviewed
            $table->foreignId('reviewer_id')->constrained('users'); // Person doing the review
            $table->foreignId('cycle_id')->constrained('performance_review_cycles');
            $table->string('review_type')->default('manager'); // manager, self, peer, subordinate
            $table->string('status')->default('not_started'); // not_started, in_progress, submitted, approved
            $table->decimal('overall_score', 4, 2)->nullable();
            $table->string('overall_rating')->nullable(); // exceeds, meets, below, etc.
            $table->json('competency_scores')->nullable(); // Individual competency ratings
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_achievements')->nullable();
            $table->text('development_goals')->nullable();
            $table->text('additional_comments')->nullable();
            $table->json('action_items')->nullable(); // Follow-up actions
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->integer('completion_percentage')->default(0);
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['reviewee_id', 'cycle_id']);
            $table->index(['reviewer_id', 'status']);
            $table->index('overall_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_review_items');
    }
};
