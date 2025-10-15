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
        Schema::create('lesson_quiz_questions', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('quiz_id')->constrained('lesson_quizzes')->onDelete('cascade');
            $table->text('text'); // Question text
            $table->text('explanation')->nullable(); // Explanation shown after answer
            $table->integer('order_index')->default(0);
            $table->enum('type', ['single', 'multi'])->default('single'); // single or multiple choice
            $table->integer('weight')->default(1); // For weighted scoring
            $table->timestamps();

            $table->index(['quiz_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_quiz_questions');
    }
};
