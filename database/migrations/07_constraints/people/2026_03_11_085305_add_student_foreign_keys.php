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
        Schema::table('parents', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
            $table->string('specialty_id');
            $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('cascade');
            $table->string('guardian_id');
            $table->foreign('guardian_id')->references('id')->on('parents');
            $table->string('student_batch_id');
            $table->foreign('student_batch_id')->references('id')->on('student_batches');
            $table->string('relationship_id');
            $table->foreign('relationship_id')->references('id')->on('stu_par_relationships');
            $table->string('student_source_id');
            $table->foreign('student_source_id')->references('id')->on('student_sources');
            $table->string('gender_id');
            $table->foreign('gender_id')->references('id')->on('genders');
        });

        Schema::table('student_batches', function (Blueprint $table) {
            $table->string('school_branch_id')->after('id');
            $table->foreign('school_branch_id')->references('id')->on('school_branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
                $table->dropForeign(['specialty_id']);
                $table->dropForeign(['department_id']);
                $table->dropForeign(['level_id']);
                $table->dropForeign(['guardian_id']);
                $table->dropForeign(['student_batch_id']);
                $table->dropForeign(['relationship_id']);
                $table->dropForeign(['student_source_id']);
                $table->dropForeign(['gender_id']);
            });
        }

        if (Schema::hasTable('parents')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
            });
        }

        if (Schema::hasTable('student_batches')) {
            Schema::table('student_batches', function (Blueprint $table) {
                $table->dropForeign(['school_branch_id']);
            });
        }
    }
};
