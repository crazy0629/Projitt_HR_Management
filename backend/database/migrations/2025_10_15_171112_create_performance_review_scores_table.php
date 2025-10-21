<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_review_scores', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('review_id')->constrained('performance_reviews')->onDelete('cascade');
            $table->foreignId('cycle_id')->constrained('performance_review_cycles')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade'); // person being reviewed
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null'); // reviewer

            // Reviewer metadata
            $table->string('reviewer_name')->nullable(); // cached name
            $table->enum('type', ['self', 'manager', 'peer', 'direct_report'])->default('manager');

            // Scoring data
            $table->json('scores')->nullable(); // {"Leadership": 4.5, "Communication": 4.0}
            $table->decimal('average_score', 4, 2)->nullable();

            // Feedback
            $table->text('comments')->nullable();
            $table->text('strengths')->nullable();
            $table->text('opportunities')->nullable();

            // Flags and status
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['review_id', 'reviewer_id', 'type']);
            $table->index(['review_id', 'reviewee_id']);
            $table->index(['reviewer_id', 'status']);
            $table->index('average_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_review_scores');
    }
};
