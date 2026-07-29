<?php

use App\Http\Controllers\Teacher\TeacherSpecialtyController;
use Illuminate\Support\Facades\Route;

Route::get('/teacher/{teacherId}/specialties', [TeacherSpecialtyController::class, 'getTeacherSpecialtyByTeacherId'])->name('get.specialtybyteacherId');
Route::post('/assign-teachers', [TeacherSpecialtyController::class, 'assignTeachers'])->name('get.assignTeachers');
Route::post('/remove-teachers', [TeacherSpecialtyController::class, 'removeAssignedTeachers'])->name('get.removeAssignedTeachers');
Route::get('/specialty/{specialtyId}/teacher-assignable', [TeacherSpecialtyController::class, 'getAssignableTeachers'])->name('get.assignableTeachers');
