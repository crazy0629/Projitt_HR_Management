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
        Schema::create('career_paths_assigned', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_user_id')->constrained('users');
            $table->foreignId('target_role_id')->constrained('roles');
            $table->foreignId('learning_path_id')->constrained('learning_paths');
            $table->foreignId('assigned_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->json('milestones')->nullable(); // Key development milestones
            $table->date('target_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->json('progress_tracking')->nullable(); // Detailed progress data
            $table->timestamps();

            $table->index(['employee_user_id', 'status']);
            $table->index(['assigned_by_user_id', 'status']);
            $table->index(['target_role_id', 'status']);
            $table->index('progress_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_paths_assigned');
    }
};
