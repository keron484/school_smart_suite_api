<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GradeScale\SchoolGradeScaleController;
use App\Http\Controllers\Exam\ExamGradeController;

Route::post('/exam-grades', [SchoolGradeScaleController::class, 'createExamGrades'])
    ->name('exam-grades.store');

Route::post('/auto-gen-grading', [SchoolGradeScaleController::class, 'autoGenExamGrading'])->name('exam-grading.auto.generate');

Route::get('/exam-grades', [SchoolGradeScaleController::class, 'getAllGrades'])
    ->name('exam-grades.index');

Route::put('/exam-grades/{gradeId}', [SchoolGradeScaleController::class, 'update_grades_scoped'])
    ->name('exam-grades.update');

Route::delete('/exams/{examId}/grades', [SchoolGradeScaleController::class, 'deleteGrades'])
    ->name('exams.grades.destroy');

Route::get('/exams/{examId}/related-exams', [SchoolGradeScaleController::class, 'getRelatedExams'])
    ->name('exams.related-exams');

Route::get('/exams/{examId}/grades-config', [SchoolGradeScaleController::class, 'getGradesConfigByExam'])
    ->name('exams.grades-config');

Route::get('/exams/{examId}/config-data', [SchoolGradeScaleController::class, 'getExamConfigData'])
    ->name('exams.config-data');

Route::post('/exams/bulk-delete-grades', [SchoolGradeScaleController::class, 'bulkDeleteGrades'])
    ->name('exams.grades.bulk-delete');


Route::post('/grade-configs/{configId}/target-configs/{targetConfigId}/grades', [SchoolGradeScaleController::class, 'createGradesByOtherGrades'])
    ->name('grade-configs.target-configs.grades.store');

Route::delete('/grade-config/delete/{configId}', [SchoolGradeScaleController::class, 'deleteGradeConfig'])->name('delete-grades-configuration');
Route::get('/grades-config/details/{configId}', [SchoolGradeScaleController::class, 'getGradeConfigDetails'])->name('get-grades-configuration-details');

Route::patch('/exam-grades/update', [ExamGradeController::class, 'updateExamGrades'])->name('update.exam.grades');

Route::post('/bulk/create', [ExamGradeController::class, 'bulkCreateExamGrades'])->name('bulk.create.grades.config');

Route::post('/bulk/delete', [ExamGradeController::class, 'bulkDeleteGradesByGradeConfig'])->name('bulk.Delete.Grades.by.Grade.Category.Config');

Route::post('/bulk/create/grade-category', [ExamGradeController::class, 'bulkConfigureByOtherGradeConfig'])->name('bulk.configure.by.grade.category');
