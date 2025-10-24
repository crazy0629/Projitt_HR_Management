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
        Schema::create('cookies_trackings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_id', 255);
            $table->enum('consent_status', ['accepted', 'rejected']);
            $table->timestamp('consent_timestamp');
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('session_id', 'cookies_tracking_session_id_index');
            $table->index('consent_status', 'idx_consent_status');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookies_trackings');
    }
};
