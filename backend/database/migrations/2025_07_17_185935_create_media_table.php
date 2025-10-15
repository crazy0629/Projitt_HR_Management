<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {

            $table->bigIncrements('id')->index();
            $table->string('unique_name')->index()->nullable();
            $table->string('thumb_size')->nullable();
            $table->string('medium_size')->nullable();
            $table->string('base_url')->nullable();
            $table->string('folder_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('title')->nullable();
            $table->string('extension')->nullable();
            $table->string('size')->nullable();
            $table->string('alt_tag')->nullable();
            $table->string('batch_no')->nullable();
            $table->text('description')->nullable();

            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->softDeletes()->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
