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
        Schema::create('manager_succession_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('succession_pool_id')->constrained('succession_pool');
            $table->foreignId('employee_user_id')->constrained('users');
            $table->string('readiness')->default('6_12m'); // ready_now, 3_6m, 6_12m, 12_24m
            $table->foreignId('learning_path_id')->nullable()->constrained('learning_paths');
            $table->foreignId('mentor_user_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->string('source')->default('manager'); // ai, manager, hr
            $table->string('status')->default('active'); // active, removed
            $table->decimal('readiness_score', 4, 2)->nullable(); // 0-100 score
            $table->json('competency_gaps')->nullable(); // Areas needing development
            $table->json('development_plan')->nullable(); // Specific development activities
            $table->date('target_promotion_date')->nullable();
            $table->foreignId('nominated_by_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['succession_pool_id', 'employee_user_id'], 'unique_manager_pool_candidate');
            $table->index(['employee_user_id', 'status']);
            $table->index(['readiness', 'status']);
            $table->index('readiness_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_succession_candidates');
    }
};
