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
        Schema::create('pip_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pip_id')->constrained('pips')->onDelete('cascade');
            $table->date('checkin_date');
            $table->text('summary');
            $table->text('next_steps')->nullable();
            $table->integer('rating')->nullable(); // 1-5 scale
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['pip_id', 'checkin_date']);
            $table->index('checkin_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pip_checkins');
    }
};
