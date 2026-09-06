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
        Schema::table('grade_scales', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('letter_grade_id');
            $table->foreign('letter_grade_id')->references('id')->on('letter_grades');
            $table->string('grades_category_id');
            $table->foreign('grades_category_id')->references('id')->on('school_grade_scale_categories');
        });

        Schema::table('school_grade_scale_categories', function (Blueprint $table) {
            $table->string('grades_category_id');
            $table->foreign('grades_category_id')->references('id')->on('grade_scale_categories');
            $table->string('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('grade_scales')) {
            Schema::table('grade_scales', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['letter_grade_id']);
                $table->dropForeign(['grades_category_id']);
            });
        }

        if (Schema::hasTable('school_grade_scale_categories')) {
            Schema::table('school_grade_scale_categories', function (Blueprint $table) {
                $table->dropForeign(['grades_category_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }
    }
};
