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
        Schema::create('retention_risk_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->string('period', 7); // YYYY-MM format
            $table->enum('risk', ['low', 'medium', 'high']);
            $table->string('source')->default('calculated'); // calculated, manual, survey
            $table->json('factors')->nullable(); // risk factors that contributed to the score
            $table->decimal('score', 3, 2)->nullable(); // 0.00-1.00 risk probability
            $table->timestamps();

            $table->index(['employee_id', 'period']);
            $table->index(['period', 'risk']);
            $table->unique(['employee_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retention_risk_snapshots');
    }
};
