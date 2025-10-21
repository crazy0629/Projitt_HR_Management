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
        Schema::create('job_applicant_experiences', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('job_id')->constrained('jobs')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->string('job_title', 150);
            $table->string('company', 150);
            $table->string('location', 150)->nullable();

            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_currently_working')->default(false);

            $table->text('role_description')->nullable();

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
        Schema::dropIfExists('job_applicant_experiences');
    }
};
