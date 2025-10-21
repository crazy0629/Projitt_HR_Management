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
        Schema::create('password_resets', function (Blueprint $table) {

            $table->string('email');
            $table->string('token');
            $table->tinyInteger('type_id')->nullable()->comment('1=by_user, 2=by_admin, 3=first_login');
            $table->tinyInteger('status')->nullable()->comment('1=used, 2=expired, 3=deleted');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('deleted_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
