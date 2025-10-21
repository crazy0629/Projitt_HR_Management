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
        Schema::create('learning_path_roles', function (Blueprint $table) {
            $table->bigIncrements('id')->index();

            $table->bigInteger('learning_path_id')->unsigned();
            $table->foreign('learning_path_id')->references('id')->on('learning_paths')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->bigInteger('role_id')->unsigned();
            $table->foreign('role_id')->references('id')->on('roles')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->timestamps();

            // Ensure unique combinations
            $table->unique(['learning_path_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_path_roles');
    }
};
