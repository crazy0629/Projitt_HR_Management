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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('source', ['path', 'self_enroll', 'manager_assign', 'pip', 'succession'])->index();
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'expired'])->default('not_started')->index();
            $table->integer('progress_pct')->default(0); // 0-100
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For time-sensitive enrollments
            $table->json('metadata')->nullable(); // Additional enrollment data
            $table->timestamps();

            // Unique constraint to prevent duplicate enrollments
            $table->unique(['employee_id', 'course_id']);

            // Indexes for performance
            $table->index(['employee_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['source', 'status']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
