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
        Schema::create('certificates', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->string('certificate_id', 50)->unique(); // Unique identifier for verification
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['course', 'learning_path'])->index();
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('path_id')->nullable()->constrained('learning_paths')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issued_date');
            $table->date('expiry_date')->nullable();
            $table->string('pdf_url')->nullable(); // Generated certificate PDF
            $table->json('metadata')->nullable(); // Additional certificate data
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'type']);
            $table->index(['course_id', 'employee_id']);
            $table->index(['path_id', 'employee_id']);
            $table->index('issued_date');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
