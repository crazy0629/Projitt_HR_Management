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
        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained('review_competencies')->onDelete('cascade');
            $table->text('text'); // The actual question/criterion
            $table->decimal('weight', 5, 2)->default(0); // Percentage weight for scoring
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['competency_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_criteria');
    }
};
