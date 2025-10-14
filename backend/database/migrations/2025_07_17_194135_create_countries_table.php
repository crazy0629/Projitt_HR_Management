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
        Schema::create('countries', function (Blueprint $table) {
            
            $table->bigIncrements('id')->index();

            $table->string('name', 150)->index();
            $table->string('iso', 2)->unique();
            $table->string('iso3', 3)->unique();
            $table->string('dial_code', 10)->nullable();
            $table->string('contact_code', 10)->nullable();
            $table->string('flag_svg')->nullable();
            $table->string('currency_sign', 10)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->string('language', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onUpdate('restrict')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
