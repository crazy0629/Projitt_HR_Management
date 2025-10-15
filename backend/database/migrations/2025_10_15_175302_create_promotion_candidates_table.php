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
        Schema::create('promotion_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('current_role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->foreignId('proposed_role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->text('justification');
            $table->json('comp_adjustment')->nullable(); // {"type":"amount","value":5000} or {"type":"percentage","value":15}
            $table->foreignId('workflow_id')->nullable()->constrained('promotion_workflows')->onDelete('set null');
            $table->enum('status', ['draft', 'submitted', 'in_review', 'approved', 'rejected', 'withdrawn'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_candidates');
    }
};
