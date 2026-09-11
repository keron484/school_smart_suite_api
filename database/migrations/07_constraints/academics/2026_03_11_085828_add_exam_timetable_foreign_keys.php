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

        Schema::table('exam_timetable_versions', function (Blueprint $table) {
            $table->string('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams');
            $table->string('school_branch_id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('exam_timetable_slots', function (Blueprint $table) {
            $table->string('course_id');
            $table->foreign('course_id')->references('id')->on('courses');
            $table->string('version_id');
            $table->foreign('version_id')->references('id')->on('exam_timetable_versions');
            $table->string('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams');
            $table->string('school_branch_id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('active_exam_timetables', function (Blueprint $table) {
            $table->string('version_id');
            $table->foreign('version_id')->references('id')->on('exam_timetable_versions');
            $table->string('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams');
            $table->string('school_branch_id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('exam_invigilators', function (Blueprint $table) {
            $table->string('school_branch_id', 64)->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams');
        });


        Schema::table('exam_session_halls', function (Blueprint $table) {
            $table->string('exam_slot_id', 64);
            $table->foreign('exam_slot_id')->references('id')->on('exam_timetable_slots');
            $table->string('specialty_id', 64);
            $table->foreign('specialty_id')->references('id')->on('specialties');
            $table->string('hall_id', 64);
            $table->foreign('hall_id')->references('id')->on('halls');
            $table->string('school_branch_id', 64)->index();
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('exam_session_invigs', function (Blueprint $table) {
            $table->string('exam_slot_id', 64);
            $table->foreign('exam_slot_id')->references('id')->on('exam_timetable_slots');
            $table->string('invigilator_id');
            $table->foreign('invigilator_id')->references('id')->on('exam_invigilators');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exam_invigs')) {
            Schema::table('exam_invigs', function (Blueprint $table) {
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('active_exam_timetable')) {
            Schema::table('active_exam_timetable', function (Blueprint $table) {
                $table->dropForeign(['version_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('exam_timetable_slots')) {
            Schema::table('exam_timetable_slots', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
                $table->dropForeign(['version_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('exam_timetable_versions')) {
            Schema::table('exam_timetable_versions', function (Blueprint $table) {
                $table->dropForeign(['version_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('exam_invigs')) {
            Schema::table('exam_invigs', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['exam_id']);
                // Optionally drop the columns if they were created in this migration
                // $table->dropColumn(['school_branch_id', 'exam_id']);
            });
        }

        if (Schema::hasTable('exam_session_halls')) {
            Schema::table('exam_session_halls', function (Blueprint $table) {
                $table->dropForeign(['exam_slot_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['hall_id']);
                $table->dropForeign(['school_branch_id']);
                // $table->dropColumn(['exam_slot_id', 'candidate', 'specialty_id', 'hall_id', 'school_branch_id']);
            });
        }

        if (Schema::hasTable('exam_session_invigs')) {
            Schema::table('exam_session_invigs', function (Blueprint $table) {
                $table->dropForeign(['exam_slot_id']);
                // $table->dropColumn(['exam_slot_id']);
            });
        }
    }
};
