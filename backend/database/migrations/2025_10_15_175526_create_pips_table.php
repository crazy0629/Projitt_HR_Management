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
        Schema::create('pips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('goals')->nullable(); // store multiple goals as JSON
            $table->text('success_criteria')->nullable();
            $table->foreignId('learning_path_id')->nullable()->constrained('learning_paths')->onDelete('set null');
            $table->foreignId('mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('checkin_frequency', ['weekly', 'biweekly', 'monthly'])->default('weekly');
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->text('completion_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pips');
    }
};
