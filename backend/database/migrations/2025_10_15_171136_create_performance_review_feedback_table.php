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
        Schema::create('performance_review_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('performance_reviews')->onDelete('cascade');
            $table->text('strengths')->nullable(); // AI-generated or manually compiled strengths
            $table->text('opportunities')->nullable(); // AI-generated or manually compiled opportunities
            $table->text('ai_summary')->nullable(); // AI-generated overall summary
            $table->text('manager_summary')->nullable(); // Manager's overall assessment
            $table->text('development_recommendations')->nullable(); // Recommended learning paths or actions
            $table->json('key_themes')->nullable(); // Common themes across all feedback
            $table->enum('sentiment', ['positive', 'neutral', 'needs_attention'])->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users'); // Who generated/reviewed this feedback
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique('review_id'); // One feedback record per review
            $table->index('sentiment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_review_feedback');
    }
};
