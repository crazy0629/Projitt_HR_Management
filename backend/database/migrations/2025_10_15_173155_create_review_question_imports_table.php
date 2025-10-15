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
        Schema::create('review_question_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('performance_review_cycles')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('imported_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('import_log')->nullable(); // Store any import errors/warnings
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['cycle_id', 'status']);
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_question_imports');
    }
};
