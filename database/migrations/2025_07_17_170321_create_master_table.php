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
        Schema::create('masters', function (Blueprint $table) {

            $table->bigIncrements('id')->index();
            $table->string('name', 250)->nullable();
            $table->string('slug',250)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('type_id')->nullable()->comment('1=department, 2=designation, 3=employment_type, 4=skills, 5=stage_tpye, 6=sub_stage_type, 7=employee_onboarding_check_list, 8=employee_benefit_list');

            $table->unsignedBigInteger('parent_id')->nullable()->comment('Self referencing parent id');
            $table->foreign('parent_id')->references('id')->on('masters')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->softDeletes()->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
