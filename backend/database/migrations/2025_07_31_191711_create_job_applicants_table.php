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
        Schema::create('job_applicants', function (Blueprint $table) {

            $table->bigIncrements('id')->index();

            $table->foreignId('applicant_id')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onUpdate('restrict')->onDelete('cascade');

            $table->string('address', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('contact_code', 10)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->enum('status', ['not-submitted', 'under-review', 'interviewing', 'rejected', 'submitted', 'short-listed'])->default('not-submitted');

            $table->string('linkedin_link', 200)->nullable();
            $table->string('portfolio_link', 200)->nullable();

            $table->foreignId('cv_media_id')->nullable()->constrained('media')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->onUpdate('restrict')->onDelete('cascade');

            $table->json('skill_ids')->nullable();
            $table->json('other_links')->nullable();

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
        Schema::dropIfExists('job_applicants');
    }
};
