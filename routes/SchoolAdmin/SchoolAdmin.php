<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SchoolAdmin\UpdateSchoolAdminProfileController;
use App\Http\Controllers\SchoolAdmin\SchoolAdminController;


Route::get('/', [SchoolAdminController::class, 'getSchoolAdmin'])
    ->name('school-admins.index');
Route::put('/update-profile', [UpdateSchoolAdminProfileController::class, "UpdateSchoolAdminProfile"])->name('school-admin.update-profile');

Route::get('/{schoolAdminId}', [SchoolAdminController::class, 'getSchoolAdminDetails'])
    ->name('school-admins.show');

Route::put('/{schoolAdminId}', [SchoolAdminController::class, 'updateSchoolAdmin'])
    ->name('school-admins.update');

Route::delete('/{schoolAdminId}', [SchoolAdminController::class, 'deleteSchoolAdmin'])
    ->name('school-admins.destroy');

Route::post('/profile-picture', [SchoolAdminController::class, 'uploadProfilePicture'])
    ->name('school-admins.profile-picture.upload');


Route::delete('/profile-picture', [SchoolAdminController::class, 'deleteProfilePicture'])
    ->name('school-admins.profile-picture.destroy');

Route::post('/{schoolAdminId}/deactivate', [SchoolAdminController::class, 'deactivateAccount'])
    ->name('school-admins.deactivate');

Route::post('/{schoolAdminId}/activate', [SchoolAdminController::class, 'activateAccount'])
    ->name('school-admins.activate');

Route::post('/bulk-delete', [SchoolAdminController::class, 'bulkDeleteSchoolAdmin'])
    ->name('school-admins.bulk-delete');

Route::patch('/bulk-update', [SchoolAdminController::class, 'bulkUpdateSchoolAdmin'])
    ->name('school-admins.bulk-update');

Route::post('/bulk-deactivate', [SchoolAdminController::class, 'bulkDeactivateSchoolAdmin'])
    ->name('school-admins.bulk-deactivate');

Route::post('/bulk-activate', [SchoolAdminController::class, 'bulkActivateSchoolAdmin'])
    ->name('school-admins.bulk-activate');

Route::post("/import", [SchoolAdminController::class, "importSchoolAdmins"])->name("import.schooladmin");
