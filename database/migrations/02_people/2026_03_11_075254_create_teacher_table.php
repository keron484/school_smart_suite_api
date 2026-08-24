<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name', 150);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('password');
            $table->string('username', 150)->index();
            $table->string('profile_picture')->nullable();
            $table->string('phone')->nullable();
            $table->string('email');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_course_preferences', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });

        Schema::create('teacher_availability_slots', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });

        Schema::create('teacher_specialty_preferences', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });

        Schema::create('teacher_qualifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('field_of_study', 150);
            $table->string('year', 50);
            $table->string('institution', 150);
            $table->timestamps();
        });
        Schema::create('teacher_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_availability_slots');
        Schema::dropIfExists('teacher_availabilities');
        Schema::dropIfExists('teacher_course_preferences');
        Schema::dropIfExists('teacher_specialty_preferences');
        Schema::dropIfExists('teachers');
    }
};
