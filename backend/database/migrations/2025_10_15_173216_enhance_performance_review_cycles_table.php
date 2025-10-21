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
        Schema::table('performance_review_cycles', function (Blueprint $table) {
            // Add new fields for enhanced cycle setup
            $table->boolean('anonymous_peer_reviews')->default(false)->after('assignments');
            $table->boolean('allow_optional_text_feedback')->default(true)->after('anonymous_peer_reviews');
            $table->json('eligibility_criteria')->nullable()->after('allow_optional_text_feedback');
            $table->string('user_guide_path')->nullable()->after('eligibility_criteria');
            $table->string('user_guide_name')->nullable()->after('user_guide_path');
            $table->enum('setup_status', ['incomplete', 'competencies_added', 'criteria_added', 'ready_to_launch'])->default('incomplete')->after('status');
            $table->timestamp('launched_at')->nullable()->after('setup_status');
            $table->integer('total_employees')->default(0)->after('completion_rate');
            $table->integer('eligible_employees')->default(0)->after('total_employees');

            // Add indexes for new fields
            $table->index('setup_status');
            $table->index('launched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_review_cycles', function (Blueprint $table) {
            $table->dropIndex(['setup_status']);
            $table->dropIndex(['launched_at']);

            $table->dropColumn([
                'anonymous_peer_reviews',
                'allow_optional_text_feedback',
                'eligibility_criteria',
                'user_guide_path',
                'user_guide_name',
                'setup_status',
                'launched_at',
                'total_employees',
                'eligible_employees',
            ]);
        });
    }
};
