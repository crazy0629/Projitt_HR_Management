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
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('duration_hours')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('instructor', 150)->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('video_url')->nullable();
            $table->json('materials')->nullable(); // Course materials/resources
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->decimal('rating', 3, 2)->default(0.00); // Average rating
            $table->integer('enrollments_count')->default(0);

            // User references
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->timestamps();
            $table->softDeletes()->index();
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
