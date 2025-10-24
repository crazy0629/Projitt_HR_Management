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
        Schema::create('cookies_visits', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->string('session_id', 255)->index('cookies_visit_session_id_index');
            $table->text('page_url');
            $table->string('page_title', 500)->nullable();
            $table->text('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('screen_resolution', 20)->nullable();
            $table->string('viewport_size', 20)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('page_type', 50)->nullable();
            $table->boolean('is_first_visit')->default(false);
            $table->string('ip_address', 45)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable()->default(null);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookies_visits');
    }
};
