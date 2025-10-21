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
        Schema::create('learning_path_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->index();

            $table->bigInteger('learning_path_id')->unsigned();
            $table->foreign('learning_path_id')->references('id')->on('learning_paths')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->bigInteger('user_id')->unsigned(); // Who performed the action
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('action', 100)->index(); // created, updated, published, archived, etc.
            $table->json('payload')->nullable(); // Store the changed data
            $table->json('previous_data')->nullable(); // Store previous state for audit
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['learning_path_id', 'action']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_path_logs');
    }
};
