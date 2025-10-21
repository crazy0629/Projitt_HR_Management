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
        Schema::create('learning_path_courses', function (Blueprint $table) {
            $table->bigIncrements('id')->index();

            $table->bigInteger('learning_path_id')->unsigned();
            $table->foreign('learning_path_id')->references('id')->on('learning_paths')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->bigInteger('course_id')->unsigned();
            $table->foreign('course_id')->references('id')->on('courses')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->integer('order_index')->default(0); // Course order in learning path
            $table->boolean('is_required')->default(true); // Required vs optional course
            $table->text('completion_criteria')->nullable(); // Custom completion requirements

            $table->timestamps();

            // Ensure unique combinations and order
            $table->unique(['learning_path_id', 'course_id']);
            $table->index(['learning_path_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_path_courses');
    }
};
