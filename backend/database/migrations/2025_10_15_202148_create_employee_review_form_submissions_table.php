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
        Schema::create('employee_review_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_item_id')->constrained('employee_review_items');
            $table->foreignId('form_id')->constrained('employee_review_forms');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->foreignId('reviewee_id')->constrained('users');
            $table->json('form_responses')->nullable(); // Complete form responses
            $table->json('section_scores')->nullable(); // Scores by form section
            $table->json('competency_ratings')->nullable(); // Detailed competency evaluations
            $table->decimal('calculated_score', 4, 2)->nullable();
            $table->string('performance_level')->nullable(); // exceeds, meets, below
            $table->text('narrative_feedback')->nullable();
            $table->json('goals_evaluation')->nullable(); // Assessment of previous goals
            $table->json('development_recommendations')->nullable(); // Future development suggestions
            $table->boolean('is_draft')->default(true);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamps();

            $table->index(['reviewer_id', 'is_draft']);
            $table->index(['reviewee_id', 'submitted_at']);
            $table->index('calculated_score');
            $table->index('performance_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_review_form_submissions');
    }
};
