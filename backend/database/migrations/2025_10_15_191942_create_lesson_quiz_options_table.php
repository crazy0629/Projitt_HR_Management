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
        Schema::create('lesson_quiz_options', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->foreignId('question_id')->constrained('lesson_quiz_questions')->onDelete('cascade');
            $table->text('text'); // Option text
            $table->boolean('is_correct')->default(false);
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'order_index']);
            $table->index(['question_id', 'is_correct']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_quiz_options');
    }
};
