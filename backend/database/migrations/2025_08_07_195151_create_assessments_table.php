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
        // Create assessments table
        Schema::create('assessments', function (Blueprint $table) {
            $table->bigIncrements('id')->index();

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('time_duration')->comment('Time duration in minutes');
            $table->unsignedTinyInteger('type_id')->comment('1 = Psychometric, 2 = Coding');
            $table->integer('points')->default(0);
            $table->enum('status', ['draft', 'open', 'closed', 'hold'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');
        });

        // Create assessment_questions table
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->integer('question_id')->nullable();
            $table->integer('point')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
    }
};
