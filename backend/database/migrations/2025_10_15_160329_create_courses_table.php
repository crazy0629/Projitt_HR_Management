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
        Schema::create('courses', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->string('title', 250)->index();
            $table->string('slug', 300)->unique()->nullable();
            $table->text('description')->nullable();
            $table->text('learning_objectives')->nullable();
            
            // Course Library enhancements
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->enum('type', ['video', 'text', 'external_link', 'file_upload'])->default('external_link');
            $table->string('url')->nullable(); // For external links (YouTube, Vimeo, etc.)
            $table->string('file_path')->nullable(); // For uploaded files
            $table->string('file_type')->nullable(); // mp4, pdf, etc.
            $table->bigInteger('file_size')->nullable(); // In bytes
            
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('duration_minutes')->nullable(); // Changed from hours to minutes for precision
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('instructor', 150)->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('video_url')->nullable(); // Deprecated in favor of 'url'
            $table->json('materials')->nullable(); // Course materials/resources
            $table->enum('status', ['active', 'archived'])->default('active')->index(); // Simplified status
            $table->decimal('rating', 3, 2)->default(0.00); // Average rating
            $table->integer('enrollments_count')->default(0);
            
            // Usage statistics
            $table->integer('learning_paths_count')->default(0);
            $table->integer('assigned_users_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // User references
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->timestamps();
            $table->softDeletes()->index();
            
            // Indexes for better performance
            $table->index(['type', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('learning_paths_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
