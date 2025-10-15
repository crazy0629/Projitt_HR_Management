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
        Schema::create('path_enrollments', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('path_id')->constrained('learning_paths')->onDelete('cascade');
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'abandoned'])->default('assigned')->index();
            $table->integer('progress_pct')->default(0); // 0-100
            $table->integer('completed_courses')->default(0);
            $table->integer('total_courses')->default(0);
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('due_date')->nullable(); // Optional deadline
            $table->json('metadata')->nullable(); // Additional path enrollment data
            $table->timestamps();

            // Unique constraint
            $table->unique(['employee_id', 'path_id']);

            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index(['path_id', 'status']);
            $table->index('due_date');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('path_enrollments');
    }
};
