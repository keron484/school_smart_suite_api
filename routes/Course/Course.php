<?php


use App\Http\Controllers\Course\CourseController;
use Illuminate\Support\Facades\Route;

Route::post('/courses', [CourseController::class, 'createCourse'])
    ->name('courses.store');

Route::get('/courses', [CourseController::class, 'getCourses'])
    ->name('courses.index');

Route::get('/active', [CourseController::class, 'getActiveCourses'])
    ->name('courses.active');

Route::get('/specialty/{specialtyId}', [CourseController::class, 'getCoursesGSemesterBspecialtyId'])
    ->name('course.specialty');

Route::get('/{courseId}', [CourseController::class, 'getCourseDetails'])
    ->name('courses.show');

Route::get('courses/specialty/{specialtyId}/semester/{semesterId}', [CourseController::class, 'getBySpecialtyLevelSemester'])
    ->name('my-courses.by-specialty-semester');

Route::get('/school-semesters/{semesterId}/specialties/{specialtyId}/courses', [CourseController::class, 'getCoursesBySchoolSemester'])
    ->name('school-semesters.specialties.courses.index');

Route::put('/{courseId}', [CourseController::class, 'updateCourse'])
    ->name('courses.update');

Route::post('/{courseId}/activate', [CourseController::class, 'activateCourse'])
    ->name('courses.activate');

Route::post('/{courseId}/deactivate', [CourseController::class, 'deactivateCourse'])
    ->name('courses.deactivate');

Route::delete('/{courseId}', [CourseController::class, 'deleteCourse'])
    ->name('courses.destroy');

Route::post('/bulk-delete', [CourseController::class, 'bulkDeleteCourse'])
    ->name('courses.bulk-delete');

Route::patch('/bulk-update', [CourseController::class, 'bulkUpdateCourse'])
    ->name('courses.bulk-update');

Route::post('/bulk-activate', [CourseController::class, 'bulkActivateCourse'])
    ->name('courses.bulk-activate');

Route::post('/bulk-deactivate', [CourseController::class, 'bulkDeactivateCourse'])
    ->name('courses.bulk-deactivate');

Route::get('/student/{studentId}', [CourseController::class, "getAllCoursesByStudentId"])->name("get.courses.by.studentId");
Route::get('/semester/{semesterId}/student/{studentId}', [CourseController::class, "getCoursesByStudentIdSemesterId"])->name("get.courses.by.semester.student");
Route::get('/specialty/{specialtyId}/semester/{semesterId}', [CourseController::class, 'getCoursesBySpecialtySemester'])
    ->name('courses.by-specialty-semester');
Route::post("/import", [CourseController::class, "importCourses"])->name("import.courses");
