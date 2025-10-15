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
        Schema::create('lesson_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('lesson_id')->constrained('course_lessons')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('passing_score')->default(80); // Percentage (0-100)
            $table->integer('attempts_allowed')->nullable(); // NULL = unlimited
            $table->integer('time_limit_minutes')->nullable(); // NULL = no time limit
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->timestamps();

            $table->unique('lesson_id'); // One quiz per lesson
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_quizzes');
    }
};
