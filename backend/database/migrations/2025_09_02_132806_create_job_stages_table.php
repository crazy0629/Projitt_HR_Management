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
        Schema::create('job_stages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();

            $table->foreignId('type_id')
                ->nullable()
                ->constrained('masters')
                ->onUpdate('RESTRICT')
                ->onDelete('CASCADE');

            $table->foreignId('job_id')
                ->nullable()
                ->constrained('jobs')
                ->onUpdate('RESTRICT')
                ->onDelete('CASCADE');

            $table->foreignId('sub_type_id')
                ->nullable()
                ->constrained('masters')
                ->onUpdate('RESTRICT')
                ->onDelete('CASCADE');

            $table->unsignedInteger('order')
                ->default(0)
                ->comment('Used for custom sorting');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');

            // created_by, updated_by, deleted_by
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')
                ->onDelete('CASCADE');

            $table->foreign('updated_by')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')
                ->onDelete('CASCADE');

            $table->foreign('deleted_by')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')
                ->onDelete('CASCADE');

            $table->index(['created_by', 'updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_stages');
    }
};
