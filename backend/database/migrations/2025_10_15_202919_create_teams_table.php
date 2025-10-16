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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('manager_user_id')->constrained('users');
            $table->unsignedBigInteger('org_unit_id')->nullable(); // Future org structure reference
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional team properties
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['manager_user_id', 'is_active']);
            $table->index('org_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
