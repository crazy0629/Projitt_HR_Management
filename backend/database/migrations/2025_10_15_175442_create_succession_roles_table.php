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

            // Core Identifiers
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('incumbent_id')->nullable()->constrained('users')->onDelete('set null');

            // Organizational context
            $table->string('org_unit_id')->nullable(); // Department or team identifier
            $table->string('title')->nullable(); // Optional if role has a title field already
            $table->text('description')->nullable();

            // Succession metrics
            $table->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
            $table->enum('replacement_timeline', ['immediate', 'short', 'medium', 'long'])->default('medium');

            // Status flags
            $table->boolean('is_critical')->default(false);
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['org_unit_id', 'is_active']);
            $table->index(['is_critical', 'criticality', 'risk_level']);
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
