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
        Schema::create('job_applicant_question_answers', function (Blueprint $table) {

            $table->bigIncrements('id')->index();

            $table->foreignId('question_id')->nullable()->constrained('questions')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('applicant_id')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->text('answer')->nullable()->comment('stores string, json, file path, or HTML');

            $table->foreignId('created_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applicant_question_answers');
    }
};
