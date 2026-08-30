<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->decimal('grade_points', 5, 2);
            $table->enum('result', ['failed', 'passed']);
            $table->enum('resit_result', ['resit', 'no_resit', 'high_resit_potential', 'low_resit_potential']);
            $table->decimal('minimum_score', 5, 2);
            $table->decimal('maximum_score', 5, 2);
            $table->string('performance');
            $table->timestamps();
        });

        Schema::create('letter_grades', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('letter_grade')->unique();
            $table->timestamps();
        });

        Schema::create('school_grade_scale_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->decimal('max_score', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('grade_scale_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->enum('status', ['active', 'inactive']);
            $table->enum('exam_type', ['exam', 'resit', 'ca']);
            $table->timestamps();
        });

    }

    public function down(): void
    {

        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('school_grade_scale_categories');
        Schema::dropIfExists('grade_scale_categories');
        Schema::dropIfExists('letter_grades');
    }
};
