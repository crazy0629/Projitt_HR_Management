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
        Schema::create('learning_path_criteria', function (Blueprint $table) {
            $table->bigIncrements('id')->index();

            $table->bigInteger('learning_path_id')->unsigned();
            $table->foreign('learning_path_id')->references('id')->on('learning_paths')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->string('field', 100); // Field to check (role, department, is_manager, etc.)
            $table->enum('operator', ['=', '!=', 'IN', 'NOT IN', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'])->default('=');
            $table->text('value'); // Value to compare (JSON for array values)
            $table->enum('connector', ['AND', 'OR'])->default('AND'); // Logical connector to next rule
            $table->integer('group_index')->default(0); // For grouping complex conditions

            $table->timestamps();

            $table->index(['learning_path_id', 'group_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_path_criteria');
    }
};
