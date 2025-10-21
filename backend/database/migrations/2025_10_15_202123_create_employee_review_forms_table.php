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
        Schema::create('employee_review_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('annual'); // annual, quarterly, project, 360
            $table->json('sections')->nullable(); // Array of form sections with questions
            $table->json('competency_weights')->nullable(); // Mapping of competencies to weights
            $table->json('scoring_rules')->nullable(); // Custom scoring and calculation rules
            $table->integer('estimated_duration_minutes')->default(30);
            $table->boolean('requires_manager_approval')->default(false);
            $table->boolean('allows_self_review')->default(true);
            $table->boolean('allows_peer_review')->default(false);
            $table->boolean('allows_subordinate_review')->default(false);
            $table->boolean('is_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('is_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_review_forms');
    }
};
