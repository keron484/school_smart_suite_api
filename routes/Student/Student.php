<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\StudentController;

    Route::post('/{studentId}/deactivate', [StudentController::class, 'deactivateAccount'])
        ->name('students.deactivate');

    Route::post('/{studentId}/activate', [StudentController::class, 'activateAccount'])
        ->name('students.activate');

    Route::post('/{studentId}/dropout', [StudentController::class, 'markStudentAsDropout'])
        ->name('students.dropout');

    Route::get('/dropouts', [StudentController::class, 'getStudentDropoutList'])
        ->name('students.dropouts.index');

    Route::post('/dropouts/{studentDropoutId}/reinstate', [StudentController::class, 'reinstateDropedOutStudent'])
        ->name('students.dropouts.reinstate');

    Route::post('/bulk-delete', [StudentController::class, 'bulkDeleteStudent'])
        ->name('students.bulk-delete');

    Route::post('/bulk-activate', [StudentController::class, 'bulkActivateStudent'])
        ->name('students.bulk-activate');

    Route::post('/bulk-deactivate', [StudentController::class, 'bulkDeActivateStudent'])
        ->name('students.bulk-deactivate');

    Route::post('/bulk-dropout', [StudentController::class, 'bulkMarkStudentAsDropout'])
        ->name('students.bulk-dropout');

    Route::patch('/bulk-update', [StudentController::class, 'bulkUpdateStudent'])
        ->name('students.bulk-update');

    Route::get('/students', [StudentController::class, 'getStudents'])
        ->name('students.index');

    Route::get('/{studentId}', [StudentController::class, 'getStudentDetails'])
        ->name('students.show');

    Route::put('/{studentId}', [StudentController::class, 'updateStudent'])
        ->name('students.update');

    Route::delete('/{studentId}', [StudentController::class, 'deleteStudent'])
        ->name('students.destroy');

    Route::post('/avatar/upload', [StudentController::class, 'uploadProfilePicture'])
    ->name('student.avatar.upload');

    Route::delete('/avatar/delete', [StudentController::class, 'deleteProfilePicture'])
    ->name('student.avatar.delete');

    Route::get('/{studentId}/profile', [StudentController::class, 'getStudentProfileDetails'])->name('get.student.profile.details');

    Route::post('/bulk-reinstate/dropout', [StudentController::class, "bulkReinstateDropedOutStudent"])->name("Bulk.reinstate.dropdout.student");

    Route::post("/import", [StudentController::class, 'importStudents'])->name("import.student");
