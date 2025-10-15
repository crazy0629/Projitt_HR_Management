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
        Schema::create('learning_path_assignments', function (Blueprint $table) {
            $table->bigIncrements('id')->index();

            $table->bigInteger('learning_path_id')->unsigned();
            $table->foreign('learning_path_id')->references('id')->on('learning_paths')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->bigInteger('employee_id')->unsigned(); // Reference to users table (employees)
            $table->foreign('employee_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled', 'overdue'])->default('assigned')->index();
            $table->decimal('progress_percentage', 5, 2)->default(0.00); // Overall progress 0-100%
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Assignment metadata
            $table->bigInteger('assigned_by')->unsigned()->nullable();
            $table->foreign('assigned_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->text('notes')->nullable(); // Assignment notes or comments
            $table->json('completion_data')->nullable(); // Store completion details

            $table->timestamps();

            // Ensure unique employee assignments per learning path
            $table->unique(['learning_path_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['learning_path_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_path_assignments');
    }
};
