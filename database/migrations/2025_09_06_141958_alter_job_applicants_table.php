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
        Schema::table('job_applicants', function (Blueprint $table) {
            $table->unsignedBigInteger('current_job_stage_id')->nullable()->after('id');

            $table->foreign('current_job_stage_id')
                  ->references('id')
                  ->on('job_stages')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applicants', function (Blueprint $table) {
            $table->dropForeign(['current_job_stage_id']);
            $table->dropColumn('current_job_stage_id');
        });
    }
};
