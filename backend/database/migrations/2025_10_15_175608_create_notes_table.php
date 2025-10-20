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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users');
            $table->string('title')->nullable();
            $table->text('body');
            $table->enum('note_type', ['performance', 'behavior', 'general'])->default('general');
            $table->enum('visibility', ['hr_only', 'manager_chain', 'employee_visible'])->default('manager_chain');
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'visibility']);
            $table->index('author_id');
            $table->index('note_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
