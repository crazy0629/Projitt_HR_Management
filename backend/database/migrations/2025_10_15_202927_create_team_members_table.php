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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('employee_user_id')->constrained('users');
            $table->foreignId('reports_to_user_id')->constrained('users');
            $table->boolean('is_primary')->default(true); // Primary team assignment
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('role_in_team')->nullable(); // Team Lead, Member, etc.
            $table->json('permissions')->nullable(); // Team-specific permissions
            $table->timestamps();

            $table->unique(['team_id', 'employee_user_id', 'effective_from'], 'unique_team_member_effective');
            $table->index(['employee_user_id', 'is_primary']);
            $table->index(['reports_to_user_id', 'effective_from']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
