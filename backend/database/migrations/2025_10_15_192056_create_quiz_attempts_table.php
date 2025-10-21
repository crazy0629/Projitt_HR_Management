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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('lesson_id')->constrained('course_lessons')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->integer('score')->default(0); // Percentage score (0-100)
            $table->boolean('is_passed')->default(false);
            $table->integer('attempt_no')->default(1); // Attempt number
            $table->json('answers_json'); // Student's answers
            $table->integer('time_taken_seconds')->nullable(); // Time taken for the quiz
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Unique constraint for each attempt
            $table->unique(['lesson_id', 'enrollment_id', 'attempt_no']);

            // Indexes
            $table->index(['enrollment_id', 'is_passed']);
            $table->index(['lesson_id', 'score']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
