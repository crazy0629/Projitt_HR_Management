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
        Schema::create('supports', function (Blueprint $table) {
            
            $table->bigIncrements('id');
            $table->string('full_name', 150);
            $table->string('email', 150);
            $table->string('company_name', 200)->nullable();
            $table->tinyInteger('question_category_id')->nullable();
            $table->text('question');
            $table->tinyInteger('preferred_response_method')->comment('1=email, 2=phone');
            $table->unsignedBigInteger('media_id')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable()->default(null);

            $table->foreign('media_id')
                  ->references('id')
                  ->on('media')
                  ->onDelete('set null');

            $table->index('preferred_response_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supports');
    }
};