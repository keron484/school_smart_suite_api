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
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('gender_id');
            $table->foreign('gender_id')->references('id')->on('genders');
        });

        Schema::table('teacher_course_preferences', function (Blueprint $table) {
            $table->string('teacher_id')->index();
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->string('course_id')->nullable()->index();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->string('school_branch_id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('teacher_availability_slots', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('availability_id')->index();
            $table->foreign('availability_id')->references('id')->on('teacher_availabilities');
        });

        Schema::table('teacher_availabilities', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('school_semester_id');
            $table->foreign('school_semester_id')->references('id')->on('school_semesters');
            $table->string('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
        });

        Schema::table('teacher_specialty_preferences', function (Blueprint $table) {
            $table->string('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('teacher_id')->index();
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->string('specialty_id')->index();
            $table->foreign('specialty_id')->references('id')->on('specialties');
        });

        Schema::table('teacher_qualifications', function (Blueprint $table) {
            $table->uuid('teacher_id')->index();
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->uuid('qualification_id')->index();
            $table->foreign('qualification_id')->references('id')->on('qualifications');
            $table->uuid('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('teacher_levels', function (Blueprint $table) {
            $table->uuid('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->uuid('level_id');
            $table->foreign('level_id')->references('id')->on('levels');
            $table->uuid('school_branch_id')->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_qualifications')) {
            Schema::table('teacher_qualifications', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['qualification_id']);
                $table->dropForeign(['teacher_id']);
            });
        }
        if (Schema::hasTable('teacher_levels')) {
            Schema::table('teacher_levels', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['teacher_id']);
            });
        }
        if (Schema::hasTable('teacher_availability_slots')) {
            Schema::table('teacher_availability_slots', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['school_semester_id']);
                $table->dropForeign(['teacher_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['teacher_availability_id']);
            });
        }

        if (Schema::hasTable('teacher_availabilities')) {
            Schema::table('teacher_availabilities', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['school_semester_id']);
                $table->dropForeign(['teacher_id']);
                $table->dropForeign(['specialty_id']);
            });
        }

        if (Schema::hasTable('teacher_course_preferences')) {
            Schema::table('teacher_course_preferences', function (Blueprint $table) {
                $table->dropForeign(['teacher_id']);
                $table->dropForeign(['course_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('teacher_specialty_preferences')) {
            Schema::table('teacher_specialty_preferences', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['teacher_id']);
                $table->dropForeign(['specialty_id']);
            });
        }

        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['gender_id']);
            });
        }
    }
};
