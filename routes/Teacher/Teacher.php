<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\TeacherController;


Route::get('/teachers', [TeacherController::class, 'getInstructors'])
    ->name('teachers.index');

Route::get('/{teacherId}', [TeacherController::class, 'getInstructorDetails'])
    ->name('teachers.show');

Route::put('/{teacherId}', [TeacherController::class, 'updateInstructor'])
    ->name('teachers.update');

Route::delete('/{teacherId}', [TeacherController::class, 'deleteInstructor'])
    ->name('teachers.destroy');

Route::get('/{teacherId}/timetable', [TeacherController::class, 'getTimettableByTeacher'])
    ->name('teachers.timetable.index');

Route::post('/specialty-preference', [TeacherController::class, 'assignTeacherSpecailtyPreference'])
    ->name('teachers.specialty-preference.store');

Route::post('/{teacherId}/deactivate', [TeacherController::class, 'deactivateTeacher'])
    ->name('teachers.deactivate');


Route::post('/{teacherId}/activate', [TeacherController::class, 'activateTeacher'])
    ->name('teachers.activate');

Route::put('/bulk-update', [TeacherController::class, 'bulkUpdateTeacher'])
    ->name('teachers.bulk-update');


Route::post('/bulk-activate', [TeacherController::class, 'bulkActivateTeacher'])
    ->name('teachers.bulk-activate');


Route::post('/bulk-deactivate', [TeacherController::class, 'bulkDeactivateTeacher'])
    ->name('teachers.bulk-deactivate');


Route::delete('/bulk-delete', [TeacherController::class, 'bulkDeleteTeacher'])
    ->name('teachers.bulk-delete');

Route::post('/teacher/avatar/upload', [TeacherController::class, 'uploadProfilePicture'])
->name('teacher.avatar.upload');

Route::delete('/teacher/avatar/delete', [TeacherController::class, 'deleteProfilePicture'])
->name('teacher.avatar.delete');

Route::get('/teachers/specialty/{specialtyId}', [TeacherController::class, 'getTeacherBySpecialtyPreference'])
    ->name('teachers.specialty.preference');

Route::post('/import', [TeacherController::class, 'importTeacher'])
->name('teacher.import');
