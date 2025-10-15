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
        Schema::create('promotion_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotion_candidates')->onDelete('cascade');
            $table->integer('step_order');
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['promotion_id', 'step_order']);
            $table->index(['approver_id', 'decision']);
            $table->unique(['promotion_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_approvals');
    }
};
