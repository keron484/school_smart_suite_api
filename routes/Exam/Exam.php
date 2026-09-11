<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Exam\ExamController;

Route::post('/', [ExamController::class, 'createExam'])
    ->name('exams.store');

Route::get('/', [ExamController::class, 'getExams'])
    ->name('exams.index');

Route::get('/{examId}', [ExamController::class, 'getExamDetails'])
    ->name('exams.show');

Route::get('/related-ca/school-year/{schoolYearId}/exam-type/{examTypeId}', [ExamController::class, 'getRelatedCaExam'])
    ->name('get.relatedExams');

Route::put('/{examId}', [ExamController::class, 'updateExam'])
    ->name('exams.update');

Route::delete('/{examId}', [ExamController::class, 'deleteExam'])
    ->name('exams.destroy');

Route::get('/{examId}/letter-grades', [ExamController::class, 'associateWeightedMarkWithLetterGrades'])
    ->name('exams.letter-grades');

Route::get('/students/{studentId}/accessed-exams', [ExamController::class, 'getAccessedExams'])
    ->name('students.accessed-exams.index');

Route::post('/{examId}/grade-scale/{gradeScaleCategoryId}', [ExamController::class, 'addExamGradeScale'])
    ->name('exams.grading-configs.store');

Route::post('/bulk-delete', [ExamController::class, 'bulkDeleteExam'])
    ->name('exams.bulk-delete');

Route::patch('/bulk-update', [ExamController::class, 'bulkUpdateExam'])
    ->name('exams.bulk-update');

Route::post('/bulk-add-grading-configs', [ExamController::class, 'bulkAddExamGrading'])
    ->name('exams.grading-configs.bulk-store');


Route::get('/student/{studentId}/exams-all', [ExamController::class, 'getAllExamsByStudentId'])->name("get.all.exams.by.studentId");
Route::get('/student/{studentId}/semester/{semesterId}', [ExamController::class, "getAllExamsByStudentIdSemesterId"])->name("get.exams.by.studentId.semesteId");
Route::get('/{examId}/grade-scale', [ExamController::class, 'getExamGradeScale'])->name('get.exams.grade-scale');
Route::get('/upcoming/student', [ExamController::class, 'getStudentUpcomingExams'])->name('get.student.upcoming.exams');
