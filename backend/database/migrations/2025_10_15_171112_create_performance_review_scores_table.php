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
        Schema::create('performance_review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('performance_reviews')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users'); // Who is giving the review
            $table->string('reviewer_name'); // Cached for performance
            $table->enum('type', ['self', 'manager', 'peer', 'direct_report'])->default('manager');
            $table->json('scores'); // e.g., {"Leadership": 4.5, "Communication": 4.0, "Teamwork": 3.8}
            $table->decimal('average_score', 3, 2)->nullable(); // Average of all scores
            $table->text('comments')->nullable(); // Qualitative feedback
            $table->text('strengths')->nullable(); // What the employee does well
            $table->text('opportunities')->nullable(); // Areas for improvement
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'reviewer_id', 'type']); // One score per reviewer type per review
            $table->index(['review_id', 'type']);
            $table->index(['reviewer_id', 'status']);
            $table->index('average_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_review_scores');
    }
};
