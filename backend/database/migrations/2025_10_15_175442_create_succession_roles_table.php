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
        Schema::create('succession_roles', function (Blueprint $table) {
            $table->id();
            $table->string('org_unit_id')->nullable(); // department or team identifier
            $table->string('title');
            $table->foreignId('incumbent_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('description')->nullable();
            $table->boolean('is_critical')->default(false); // critical role for business continuity
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['org_unit_id', 'is_active']);
            $table->index('is_critical');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('succession_roles');
    }
};
