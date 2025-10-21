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
        Schema::create('wait_list', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-increment big int primary key
            $table->string('name', 150)->nullable(); // Name (optional, max 150 chars)
            $table->string('email', 150)->unique();  // Email (unique constraint)
            $table->string('company_name', 150)->nullable(); // Name (optional, max 150 chars)
            $table->string('company_email', 150)->unique();  // Email (unique constraint)
            $table->timestamp('created_at')->useCurrent(); // Auto set on insert
            $table->timestamp('updated_at')->useCurrent(); // Auto set on insert
            $table->softDeletes(); // Adds deleted_at column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wait_list');
    }
};
