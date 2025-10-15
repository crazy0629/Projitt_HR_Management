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
        Schema::create('lms_events', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->enum('event_type', [
                'course_enrolled', 'course_started', 'course_completed', 'course_abandoned',
                'lesson_started', 'lesson_completed', 'lesson_viewed',
                'quiz_started', 'quiz_completed', 'quiz_failed', 'quiz_retaken',
                'path_enrolled', 'path_started', 'path_completed', 'path_abandoned',
                'certificate_earned', 'progress_checkpoint',
            ])->index();
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained('course_lessons')->onDelete('cascade');
            $table->foreignId('path_id')->nullable()->constrained('learning_paths')->onDelete('cascade');
            $table->json('event_data')->nullable(); // Additional event-specific data
            $table->timestamp('event_timestamp');
            $table->timestamps();

            // Indexes for analytics and reporting
            $table->index(['employee_id', 'event_type']);
            $table->index(['course_id', 'event_type']);
            $table->index(['lesson_id', 'event_type']);
            $table->index(['path_id', 'event_type']);
            $table->index('event_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lms_events');
    }
};
