<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Department\DepartmentController;

Route::post('/', [DepartmentController::class, 'createDepartment'])
    ->name('departments.store');

Route::get('/', [DepartmentController::class, 'getDepartments'])
    ->name('departments.index');

Route::get('/{departmentId}', [DepartmentController::class, 'getDepartmentDetails'])
    ->name('departments.show');

Route::get('/my/departments', [DepartmentController::class, 'getDepartments'])
    ->name('my-departments.index');

Route::put('/{departmentId}', [DepartmentController::class, 'updateDepartment'])
    ->name('departments.update');

Route::delete('/{departmentId}', [DepartmentController::class, 'deleteDepartment'])
    ->name('departments.destroy');

Route::post('/{departmentId}/activate', [DepartmentController::class, 'activateDepartment'])
    ->name('departments.activate');

Route::post('/{departmentId}/deactivate', [DepartmentController::class, 'deactivateDepartment'])
    ->name('departments.deactivate');

Route::patch('/bulk-update', [DepartmentController::class, 'bulkUpdateDepartment'])
    ->name('departments.bulk-update');

Route::post('/bulk-delete', [DepartmentController::class, 'bulkDeleteDepartment'])
    ->name('departments.bulk-delete');

Route::post('/bulk-activate', [DepartmentController::class, 'bulkActivateDepartment'])
    ->name('departments.bulk-activate');

Route::post('/bulk-deactivate', [DepartmentController::class, 'bulkDeactivateDepartment'])
    ->name('departments.bulk-deactivate');

Route::post('/import', [DepartmentController::class, 'importDepartment'])->name('departents.import');
