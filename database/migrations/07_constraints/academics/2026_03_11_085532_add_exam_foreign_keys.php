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
        Schema::table('exams', function (Blueprint $table) {
            $table->string('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('exam_type_id');
            $table->foreign('exam_type_id')->references('id')->on('exam_types');
            $table->string('school_year_id')->index();
            $table->foreign('school_year_id')->references('id')->on('school_academic_years')->onDelete('cascade');
            $table->string('grades_category_id')->nullable();
            $table->foreign('grades_category_id')->references('id')->on('school_grade_scale_categories');
        });

        Schema::table('exam_types', function (Blueprint $table) {
            $table->string('semester_id');
            $table->foreign('semester_id')->references('id')->on('semesters');
        });

        Schema::table('exam_candidates', function (Blueprint $table) {
            $table->string('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('exam_id')->nullable()->index();
            $table->foreign('exam_id')->references('id')->on('exams');
            $table->string('student_id')->nullable()->index();
            $table->foreign('student_id')->references('id')->on('students');
        });

        Schema::table('marks', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('student_id');
            $table->foreign('student_id')->references('id')->on('students');
            $table->string('courses_id');
            $table->foreign('courses_id')->references('id')->on('courses');
            $table->string('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams');
            $table->string('level_id');
            $table->foreign('level_id')->references('id')->on('levels');
            $table->string('specialty_id');
            $table->foreign('specialty_id')->references('id')->on('specialties');
            $table->string('student_batch_id');
            $table->foreign('student_batch_id')->references('id')->on('student_batches');
        });

        Schema::table('student_results', function (Blueprint $table) {
            $table->string('student_id');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->string('specialty_id');
            $table->foreign('specialty_id')->references('id')->on('specialties');
            $table->string('student_batch_id');
            $table->foreign('student_batch_id')->references('id')->on('student_batches');
            $table->string('level_id');
            $table->foreign('level_id')->references('id')->on('levels');
            $table->string('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams');
            $table->string('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student_results')) {
            Schema::table('student_results', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['student_batch_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('marks')) {
            Schema::table('marks', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['courses_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['student_batch_id']);
            });
        }

        if (Schema::hasTable('exam_candidates')) {
            Schema::table('exam_candidates', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['level_id']);
            });
        }

        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['exam_type_id']);
                $table->dropForeign(['school_year_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['semester_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['student_batch_id']);
                $table->dropForeign(['grades_category_id']);
            });
        }

        if (Schema::hasTable('exam_types')) {
            Schema::table('exam_types', function (Blueprint $table) {
                $table->dropForeign(['semester_id']);
            });
        }
    }
};
