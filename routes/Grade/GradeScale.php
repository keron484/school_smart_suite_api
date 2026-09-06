<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GradeScale\SchoolGradeScaleController;

Route::get('/categories', [SchoolGradeScaleController::class, "getGradeScaleCategories"])
    ->name("grade-scale.categories");

Route::get('/category/{categoryId}/configType/{configType}/maxScore/{maxScore}', [SchoolGradeScaleController::class, 'getGradeScaleCategoryById'])
    ->name('grade-scale.category');

Route::post('/bulk-delete', [SchoolGradeScaleController::class, 'bulkDeleteGradeScale'])
    ->name('grade-scale.bulk-delete');

Route::delete('/{categoryId}', [SchoolGradeScaleController::class, 'deleteGradeScale'])
    ->name('grade-scale.delete');

Route::post('/bulk-copy', [SchoolGradeScaleController::class, 'bulkCopyGradeScale'])
    ->name('grade-scale.bulk-copy');

Route::post('/import', [SchoolGradeScaleController::class, 'importGradeScale'])
    ->name('grade-scale.import');

Route::patch('/update', [SchoolGradeScaleController::class, 'updateGradeScale'])
    ->name('grade-scale.update');

Route::post('category/activate/{categoryId}', [SchoolGradeScaleController::class, 'activateGradeScale'])
    ->name('grade-scale.activate');

Route::post('category/deactivate/{categoryId}', [SchoolGradeScaleController::class, 'deactivateGradeScale'])
    ->name('grade-scale.deactivate');

Route::get('categories/active', [SchoolGradeScaleController::class, 'getActiveGradeScaleCategory'])
    ->name('grade-scale.active');

Route::get('category/details/{categoryId}', [SchoolGradeScaleController::class, 'getGradeScaleDetails'])
    ->name('grade-scale.details');

Route::post('/create', [SchoolGradeScaleController::class, 'createGradeScale'])
    ->name('grade-scale.create');

Route::post('/copy/source-category/{sourceCategoryId}/target-category/{targetCategoryId}', [SchoolGradeScaleController::class, 'copyGradeScaleCategory'])
    ->name('grade-scale.copy');
