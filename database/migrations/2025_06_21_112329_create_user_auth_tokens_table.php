<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_auth_tokens', function (Blueprint $table) {

            $table->bigincrements('id')->index();
            $table->morphs('tokenable');
            $table->string('name')->nullable();
            $table->string('token', 100)->unique();
            $table->string('actual_token', 100)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('agent')->nullable();
            $table->string('ip')->nullable();

            $table->biginteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onupdate('restrict')->ondelete('cascade');

            $table->biginteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onupdate('restrict')->ondelete('cascade');

            $table->biginteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onupdate('restrict')->ondelete('cascade');

            $table->biginteger('deleted_by')->unsigned()->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onupdate('restrict')->ondelete('cascade');

            $table->timestamp('created_at')->default(db::raw('current_timestamp'));
            $table->timestamp('updated_at')->default(db::raw('current_timestamp on update current_timestamp'));
            $table->softdeletes()->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_auth_tokens');
    }
};
