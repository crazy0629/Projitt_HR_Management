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
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('type', ['video', 'audio', 'pdf', 'external_link', 'quiz'])->index();
            $table->string('title', 250);
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0)->index(); // For ordering lessons
            $table->json('payload')->nullable(); // Stores type-specific data
            $table->integer('duration_est_min')->nullable(); // Estimated duration in minutes
            $table->boolean('is_required')->default(true);
            $table->enum('status', ['active', 'draft', 'archived'])->default('active');

            // For tracking and analytics
            $table->integer('completions_count')->default(0);
            $table->decimal('avg_completion_time_min', 8, 2)->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['course_id', 'order_index']);
            $table->index(['course_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
