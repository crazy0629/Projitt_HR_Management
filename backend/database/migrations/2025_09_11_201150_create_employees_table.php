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
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Core attributes
            $table->enum('employee_type', ['full_time', 'freelance', 'part_time', 'intern'])->nullable();

            // Relations (explicit FK style)
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('alice_work_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // Contract / earnings
            $table->date('contract_start_date')->nullable();
            $table->enum('earning_structure', ['salary_based', 'hourly_rate'])->nullable();
            $table->decimal('rate', 10, 2)->nullable();

            // Learning / onboarding / benefits
            $table->json('onboarding_check_list_ids')->nullable();
            $table->unsignedTinyInteger('learning_path_id')->nullable();
            $table->json('benefit_ids')->nullable();

            // Audit columns
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();

            // Timestamps & soft delete (keeping your style)
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable()->default(null);

            // Foreign keys with your policy
            $table->foreign('country_id')
                ->references('id')->on('countries')
                ->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->foreign('department_id')
                ->references('id')->on('masters')
                ->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->foreign('job_title_id')
                ->references('id')->on('masters')
                ->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->foreign('alice_work_id')
                ->references('id')->on('masters')
                ->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->foreign('manager_id')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')->onDelete('SET NULL');

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onUpdate('RESTRICT')->onDelete('SET NULL');
            
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->foreign('updated_by')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->foreign('deleted_by')
                ->references('id')->on('users')
                ->onUpdate('RESTRICT')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
