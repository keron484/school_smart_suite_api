<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('DOB')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('profile_picture')->nullable();
            $table->boolean('deactivate')->default(false);
            $table->string('username', 150);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('dropout_status')->default(false);
            $table->enum('payment_format', ['one time', 'installmental'])->default('installmental');
            $table->enum('sub_status', ['subscribed', 'expired', 'renewed', 'pending'])->default('pending');
            $table->timestamps();
        });

        Schema::create('student_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('student_sources', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name', 150);
            $table->text('description');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('genders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name', 150);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('parents', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('address');
            $table->string('phone');
            $table->string('preferred_contact_method')->default('All');
            $table->string('preferred_language')->nullable();
            $table->timestamps();
        });

        Schema::create('stu_par_relationships', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('student_batches');
        Schema::dropIfExists('parents');
        Schema::dropIfExists('stu_par_relationships');
        Schema::dropIfExists('student_sources');
        Schema::dropIfExists('genders');
    }
};
