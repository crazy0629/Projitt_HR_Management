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
        Schema::create('promotion_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_cycle_id')->nullable()->constrained('performance_review_cycles');
            $table->foreignId('employee_user_id')->constrained('users');
            $table->foreignId('proposed_by_user_id')->constrained('users');
            $table->foreignId('current_role_id')->nullable()->constrained('roles');
            $table->foreignId('target_role_id')->constrained('roles');
            $table->text('justification');
            $table->decimal('comp_adjustment_min', 12, 2)->nullable();
            $table->decimal('comp_adjustment_max', 12, 2)->nullable();
            $table->string('workflow_id')->nullable(); // Reference to workflow system
            $table->string('status')->default('pending'); // pending, approved, rejected, withdrawn
            $table->json('meta')->nullable(); // Additional metadata
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('effective_date')->nullable();
            $table->timestamps();

            $table->index(['employee_user_id', 'status']);
            $table->index(['proposed_by_user_id', 'status']);
            $table->index(['review_cycle_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_recommendations');
    }
};
