<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {

            $table->bigIncrements('id')->index();
            $table->string('title', 200)->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('no_of_job_opening')->default(1);
            $table->enum('status', ['draft', 'open', 'closed', 'hold'])->default('draft');

            $table->foreignId('department_id')->nullable()->constrained('masters')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('employment_type_id')->nullable()->constrained('masters')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('location_type_id')->nullable()->constrained('masters')->onUpdate('restrict')->onDelete('cascade');

            $table->json('skill_ids')->nullable();
            $table->json('media_ids')->nullable();
            $table->json('question_ids')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('countries')->onUpdate('restrict')->onDelete('cascade');
            $table->string('state', 200)->nullable();

            $table->decimal('salary_from', 10, 2)->nullable();
            $table->decimal('salary_to', 10, 2)->nullable();

            $table->date('deadline')->nullable();
            $table->boolean('is_set_default_template')->default(false); 

            $table->foreignId('created_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
