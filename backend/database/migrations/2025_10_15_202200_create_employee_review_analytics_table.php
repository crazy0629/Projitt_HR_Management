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
        Schema::create('employee_review_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users');
            $table->foreignId('cycle_id')->constrained('performance_review_cycles');
            $table->string('period_type')->default('annual'); // annual, quarterly, project
            $table->decimal('overall_score', 4, 2)->nullable();
            $table->string('overall_rating')->nullable(); // exceeds, meets, below
            $table->json('competency_breakdown')->nullable(); // Scores by competency
            $table->json('review_type_scores')->nullable(); // manager, self, peer, subordinate scores
            $table->json('trend_analysis')->nullable(); // Comparison with previous periods
            $table->json('strengths_summary')->nullable(); // Aggregated strengths
            $table->json('improvement_areas')->nullable(); // Aggregated areas for improvement
            $table->json('goal_achievement_rate')->nullable(); // Goal completion statistics
            $table->json('development_progress')->nullable(); // Learning and development tracking
            $table->integer('reviews_completed')->default(0);
            $table->integer('reviews_pending')->default(0);
            $table->decimal('peer_feedback_score', 4, 2)->nullable();
            $table->decimal('manager_feedback_score', 4, 2)->nullable();
            $table->decimal('self_assessment_score', 4, 2)->nullable();
            $table->text('ai_generated_summary')->nullable(); // AI insights and recommendations
            $table->json('performance_insights')->nullable(); // Data-driven insights
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'cycle_id'], 'unique_employee_cycle_analytics');
            $table->index(['overall_score', 'cycle_id']);
            $table->index(['overall_rating', 'period_type']);
            $table->index('last_calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_review_analytics');
    }
};
