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
        Schema::dropIfExists('psychometric_audit_logs');
        Schema::dropIfExists('psychometric_results');
        Schema::dropIfExists('psychometric_responses');
        Schema::dropIfExists('psychometric_assignments');
        Schema::dropIfExists('psychometric_question_options');
        Schema::dropIfExists('psychometric_questions');
        Schema::dropIfExists('psychometric_dimensions');
        Schema::dropIfExists('psychometric_tests');

        Schema::create('psychometric_tests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('allowed_attempts')->default(1);
            $table->boolean('randomize_questions')->default(true);
            $table->boolean('is_published')->default(false);
            $table->json('scoring_model')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category', 'is_published']);
        });

        Schema::create('psychometric_dimensions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('psychometric_test_id')->constrained('psychometric_tests')->onDelete('cascade');
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['psychometric_test_id', 'key']);
        });

        Schema::create('psychometric_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('psychometric_test_id')->constrained('psychometric_tests')->onDelete('cascade');
            $table->foreignId('dimension_id')->nullable()->constrained('psychometric_dimensions')->onDelete('set null');
            $table->string('reference_code')->nullable();
            $table->text('question_text');
            $table->enum('question_type', ['likert', 'multiple_choice', 'multi_select', 'numeric', 'open_text']);
            $table->decimal('weight', 8, 2)->default(1.00);
            $table->boolean('is_required')->default(true);
            $table->boolean('randomize_options')->default(false);
            $table->unsignedInteger('base_order')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['psychometric_test_id', 'question_type']);
        });

        Schema::create('psychometric_question_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('question_id')->constrained('psychometric_questions')->onDelete('cascade');
            $table->string('label');
            $table->string('value')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('weight', 8, 2)->default(1.00);
            $table->unsignedInteger('position')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['question_id']);
        });

        Schema::create('psychometric_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('psychometric_test_id')->constrained('psychometric_tests')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_applicant_id')->nullable()->constrained('job_applicants')->onDelete('set null');
            $table->nullableMorphs('talentable', 'psychometric_talentable_idx');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'scored', 'expired', 'cancelled'])->default('pending');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('attempts_used')->default(0);
            $table->string('randomization_seed', 64)->nullable();
            $table->json('question_order')->nullable();
            $table->string('target_role')->nullable();
            $table->json('metadata')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->timestamps();
            $table->index(['psychometric_test_id', 'status']);
            $table->index(['candidate_id', 'status']);
            $table->index(['job_applicant_id']);
        });

        Schema::create('psychometric_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('assignment_id')->constrained('psychometric_assignments')->onDelete('cascade');
            $table->foreignId('psychometric_test_id')->constrained('psychometric_tests')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('psychometric_questions')->onDelete('cascade');
            $table->foreignId('option_id')->nullable()->constrained('psychometric_question_options')->onDelete('set null');
            $table->decimal('numeric_response', 8, 2)->nullable();
            $table->text('text_response')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'question_id']);
            $table->index(['psychometric_test_id', 'question_id']);
        });

        Schema::create('psychometric_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('assignment_id')->constrained('psychometric_assignments')->onDelete('cascade');
            $table->foreignId('psychometric_test_id')->constrained('psychometric_tests')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('dimension_id')->nullable()->constrained('psychometric_dimensions')->onDelete('set null');
            $table->string('dimension_key')->nullable();
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('weighted_score', 8, 2)->default(0);
            $table->decimal('percentile', 5, 2)->nullable();
            $table->string('band')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['psychometric_test_id', 'dimension_key']);
            $table->index(['candidate_id']);
        });

        Schema::create('psychometric_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('psychometric_test_id')->nullable()->constrained('psychometric_tests')->onDelete('set null');
            $table->foreignId('assignment_id')->nullable()->constrained('psychometric_assignments')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['psychometric_test_id', 'action']);
            $table->index(['assignment_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psychometric_audit_logs');
        Schema::dropIfExists('psychometric_results');
        Schema::dropIfExists('psychometric_responses');
        Schema::dropIfExists('psychometric_assignments');
        Schema::dropIfExists('psychometric_question_options');
        Schema::dropIfExists('psychometric_questions');
        Schema::dropIfExists('psychometric_dimensions');
        Schema::dropIfExists('psychometric_tests');
    }
};
