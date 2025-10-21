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
        Schema::create('job_applicant_certificates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('job_id')->constrained('jobs')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->string('title', 200);
            $table->string('number', 100)->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiration_date')->nullable();

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
        Schema::dropIfExists('job_applicant_certificates');
    }
};
