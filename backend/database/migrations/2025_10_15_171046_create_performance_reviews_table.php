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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('performance_review_cycles')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users'); // Employee being reviewed
            $table->string('employee_name'); // Cached for performance
            $table->string('employee_email'); // Cached for performance
            $table->string('department_name')->nullable(); // Cached for performance
            $table->decimal('final_score', 3, 2)->nullable(); // Calculated after all reviewers complete
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->integer('progress')->default(0); // % of assigned reviewers completed (0-100)
            $table->integer('total_reviewers')->default(0); // Total number of assigned reviewers
            $table->integer('completed_reviewers')->default(0); // Number of completed reviews
            $table->text('ai_summary')->nullable(); // Optional AI-generated text
            $table->enum('potential_status', ['developing', 'solid', 'ready', 'high_potential'])->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'employee_id']); // One review per employee per cycle
            $table->index(['cycle_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index('final_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
