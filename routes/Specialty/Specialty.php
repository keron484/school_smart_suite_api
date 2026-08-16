<?php

use illuminate\Support\Facades\Route;
use App\Http\Controllers\Specialty\SpecialtyController;

Route::post('/', [SpecialtyController::class, 'createSpecialty'])
    ->name('specialties.store');

Route::get('/', [SpecialtyController::class, 'getSpecialtiesBySchoolBranch'])
    ->name('specialties.index');

Route::get('/specialty/level', [SpecialtyController::class, 'getSpecialtyLevels'])->name('specialty.level.get');

Route::get('/{specialtyId}', [SpecialtyController::class, 'getSpecialtyDetails'])
    ->name('specialties.show');

Route::put('/{specialtyId}', [SpecialtyController::class, 'updateSpecialty'])
    ->name('specialties.update');

Route::delete('/{specialtyId}', [SpecialtyController::class, 'deleteSpecialty'])
    ->name('specialties.destroy');

Route::post('/{specialtyId}/activate', [SpecialtyController::class, 'activateSpecialty'])
    ->name('specialties.activate');

Route::post('/{specialtyId}/deactivate', [SpecialtyController::class, 'deactivateSpecialty'])
    ->name('specialties.deactivate');

Route::post('/bulk-delete', [SpecialtyController::class, 'bulkDeleteSpecialty'])
    ->name('specialties.bulk-delete');

Route::patch('/bulk-update', [SpecialtyController::class, 'bulkUdateSpecialty'])
    ->name('specialties.bulk-update');

Route::post('/bulk-activate', [SpecialtyController::class, 'bulkActivateSpecialty'])
    ->name('specialties.bulk-activate');

Route::post('/bulk-deactivate', [SpecialtyController::class, 'bulkDeactivateSpecialty'])
    ->name('specialties.bulk-deactivate');

Route::post('/import', [SpecialtyController::class, 'importSpecialty'])->name('import.specialty');
