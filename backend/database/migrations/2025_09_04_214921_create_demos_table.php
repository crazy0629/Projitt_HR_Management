<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('contact_code', 10)->nullable();
            $table->string('contact_no', 20)->nullable();
            $table->string('company', 150)->nullable();
            $table->string('company_size')->nullable();
            $table->enum('industry', [
                'Information Technology',
                'Healthcare',
                'Education',
                'Finance & Banking',
                'Manufacturing',
                'Retail & E-Commerce',
                'Real Estate & Construction',
                'Transportation & Logistics',
                'Hospitality & Tourism',
                'Energy & Utilities'
            ])->nullable();
            $table->enum('how_hear_bout_us', ['Social', 'Internet', 'Friends', 'Family', 'NewsPaper'])->nullable();
            $table->json('service')->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('demos');
    }
};
