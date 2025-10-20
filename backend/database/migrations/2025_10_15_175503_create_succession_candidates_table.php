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
        Schema::create('succession_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('succession_role_id')->constrained('succession_roles')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_role_id')->nullable()->constrained('roles')->onDelete('set null');

            // Matches your model
            $table->enum('readiness', ['ready', 'developing', 'long_term']);
            $table->json('development_plan')->nullable();
            $table->foreignId('learning_path_id')->nullable()->constrained('learning_paths')->onDelete('set null');
            $table->date('target_ready_date')->nullable();
            $table->json('strengths')->nullable();
            $table->json('development_areas')->nullable();

            $table->enum('source', ['hr_action', 'review_recommendation', 'manager_action'])->nullable();

            $table->foreignId('mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('last_updated_readiness')->nullable();
            $table->timestamps();

            $table->index(['succession_role_id', 'readiness']);
            $table->index(['employee_id', 'readiness']);
            $table->unique(['succession_role_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('succession_candidates');
    }
};
