<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Core fields
            $table->string('name');
            $table->string('tuid')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);

            // Relations
            $table->foreignId('manager_user_id')->constrained('users');
            $table->unsignedBigInteger('org_unit_id')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('cascade');

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['manager_user_id', 'is_active']);
            $table->index('org_unit_id');
            $table->index('deleted_at');
            $table->index(['created_by', 'updated_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
