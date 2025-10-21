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
        Schema::create('web_jobs', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->string('full_name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('linkdin_profile_link', 255)->nullable();
            $table->unsignedBigInteger('resume_media_id')->nullable();
            $table->foreign('resume_media_id')->references('id')->on('media')->onUpdate('RESTRICT')->onDelete('SET NULL');
            $table->unsignedBigInteger('cover_media_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->foreign('cover_media_id')->references('id')->on('media')->onUpdate('RESTRICT')->onDelete('SET NULL');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_jobs');
    }
};
