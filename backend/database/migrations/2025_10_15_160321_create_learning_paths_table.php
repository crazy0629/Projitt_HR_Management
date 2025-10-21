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
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->string('name', 250)->index();
            $table->string('slug', 300)->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('begin_month', 7)->nullable(); // Format: YYYY-MM
            $table->string('end_month', 7)->nullable();   // Format: YYYY-MM
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->integer('estimated_duration_hours')->nullable(); // Total estimated hours
            $table->json('metadata')->nullable(); // Additional flexible data

            // User references
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->bigInteger('published_by')->unsigned()->nullable();
            $table->foreign('published_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_paths');
    }
};
