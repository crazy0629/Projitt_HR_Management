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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();

            $table->enum('schedule_type', ['request_availability', 'propose_time']);
            $table->enum('mode', ['google_meet', 'zoom', 'projitt_video_conference', 'microsoft_team'])->nullable();
            $table->json('interviewers_ids')->nullable()->comment('Array of interviewer user IDs');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('applicant_id')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->enum('status', ['review', 'screen', 'test', 'rejected', 'selected', 'hired'])->default('review');
            $table->date('date')->comment('Scheduled date');
            $table->time('time')->comment('Scheduled time');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');
            $table->index(['created_by', 'updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
