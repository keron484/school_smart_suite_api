<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('max_score', 6, 2)->nullable();
            // $table->boolean('timetable_published')->default(false);
            // $table->boolean('result_released')->default(false);
            // // $table->enum('status', ['finished', 'inprogress', 'pending'])->default('pending');
            // $table->integer('expected_candidate_number')->default(0);
            // $table->integer('evaluated_candidate_number')->default(0);
            // $table->boolean('grading_added')->default(false);
            $table->timestamps();
        });

        Schema::create('exam_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('exam_name');
            $table->string('semester');
            $table->enum('type', ['exam', 'ca', 'resit']);
            $table->string('program_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_candidates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });

        Schema::create('marks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->decimal('score', 5, 2);
            $table->decimal('grade_points', 5, 2);
            $table->enum('grade_status', ['passed', 'failed'])->default('passed');
            $table->string('gratification');
            $table->enum('resit_status', ['resit', 'no_resit', 'high_resit_potential', 'low_resit_potential']);
            $table->string('grade');
            $table->timestamps();
        });

        Schema::create('student_results', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->decimal('gpa', 4, 2);
            $table->decimal('total_score', 6, 2);
            $table->enum('exam_status', ['passed', 'failed']);
            $table->json('score_details');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('student_results');
        Schema::dropIfExists('marks');
        Schema::dropIfExists('exam_candidates');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_types');
    }
};
