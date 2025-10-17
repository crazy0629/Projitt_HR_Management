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

            // Relation to the main PIP
            $table->foreignId('pip_id')->constrained('pips')->onDelete('cascade');

            // Core details
            $table->date('checkin_date')->default(now());
            $table->text('summary'); // overall manager/coach summary

            // Optional rating system
            $table->enum('status', ['on_track', 'off_track', 'improving', 'completed'])->default('on_track');
            $table->integer('rating')->nullable(); // numeric (1–5)

            // JSON-based goal progress tracking
            // Each item: { goal_id, title, status, notes, metric }
            $table->json('goals_progress')->nullable();

            // Coaching & future plan
            $table->text('manager_notes')->nullable();
            $table->text('next_steps')->nullable();
            $table->date('next_checkin_date')->nullable();

            // Ownership / audit
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->index(['pip_id', 'checkin_date']);
            $table->index('status');
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
