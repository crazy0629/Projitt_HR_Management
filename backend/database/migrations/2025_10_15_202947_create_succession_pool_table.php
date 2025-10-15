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
        Schema::create('succession_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('incumbent_user_id')->nullable()->constrained('users'); // Current role holder
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->string('priority_level')->default('medium'); // high, medium, low
            $table->date('succession_target_date')->nullable(); // When succession planning is needed
            $table->json('required_competencies')->nullable(); // Key skills needed for role
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['role_id', 'is_active']);
            $table->index('created_by_user_id');
            $table->index('priority_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('succession_pool');
    }
};
