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
        Schema::dropIfExists('coding_submission_reviews');
        Schema::dropIfExists('coding_submission_test_results');
        Schema::dropIfExists('coding_submissions');
        Schema::dropIfExists('coding_assessment_assignments');
        Schema::dropIfExists('coding_test_cases');
        Schema::dropIfExists('coding_assessments');

        Schema::create('coding_assessments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('languages')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->unsignedInteger('time_limit_minutes');
            $table->unsignedInteger('max_score')->default(0);
            $table->json('rubric')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['difficulty', 'deleted_at']);
        });

        Schema::create('coding_test_cases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('coding_assessment_id')->constrained('coding_assessments')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->longText('input');
            $table->longText('expected_output');
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('timeout_seconds')->default(5);
            $table->timestamps();
        });

        Schema::create('coding_assessment_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('coding_assessment_id')->constrained('coding_assessments')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->nullableMorphs('talentable', 'coding_assign_talentable_idx');
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'expired', 'reviewed'])->default('pending');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('invitation_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['coding_assessment_id', 'status']);
            $table->index(['candidate_id', 'status']);
        });

        Schema::create('coding_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('assignment_id')->constrained('coding_assessment_assignments')->onDelete('cascade');
            $table->foreignId('coding_assessment_id')->constrained('coding_assessments')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->string('language');
            $table->longText('source_code');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'timeout'])->default('pending');
            $table->unsignedInteger('passed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->unsignedInteger('memory_kb')->nullable();
            $table->string('sandbox_job_id')->nullable();
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['assignment_id', 'status']);
            $table->index(['candidate_id', 'status']);
        });

        Schema::create('coding_submission_test_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('submission_id')->constrained('coding_submissions')->onDelete('cascade');
            $table->foreignId('test_case_id')->nullable()->constrained('coding_test_cases')->onDelete('set null');
            $table->enum('status', ['passed', 'failed', 'error', 'timeout']);
            $table->string('error_type')->nullable();
            $table->decimal('score_earned', 8, 2)->default(0);
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->unsignedInteger('memory_kb')->nullable();
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['submission_id', 'status']);
        });

        Schema::create('coding_submission_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('submission_id')->constrained('coding_submissions')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('score_adjustment', 8, 2)->default(0);
            $table->text('comment')->nullable();
            $table->json('rubric_scores')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coding_submission_reviews');
        Schema::dropIfExists('coding_submission_test_results');
        Schema::dropIfExists('coding_submissions');
        Schema::dropIfExists('coding_assessment_assignments');
        Schema::dropIfExists('coding_test_cases');
        Schema::dropIfExists('coding_assessments');
    }
};
