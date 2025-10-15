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
        Schema::create('masters', function (Blueprint $table) {

            $table->bigIncrements('id')->index();
            $table->string('name', 250)->nullable();
            $table->string('slug', 250)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('type_id')->nullable()->comment('1=department');

            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->timestamps();
            $table->softDeletes()->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
