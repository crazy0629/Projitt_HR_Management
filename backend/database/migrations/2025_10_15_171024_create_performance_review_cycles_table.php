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
        Schema::create('performance_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "H1 2025 Mid-Year Review"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->date('period_start'); // Review start date
            $table->date('period_end'); // Review end date
            $table->enum('frequency', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('quarterly');
            $table->json('competencies'); // e.g., ["Leadership", "Teamwork", "Communication"]
            $table->json('assignments'); // e.g., ["self_review", "manager_review", "peer_review"]
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->integer('employee_count')->default(0); // Auto-calculated
            $table->integer('completed_count')->default(0); // Auto-calculated
            $table->decimal('completion_rate', 5, 2)->default(0.00); // Percentage
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'period_start']);
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_review_cycles');
    }
};
