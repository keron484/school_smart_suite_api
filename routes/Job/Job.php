<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Job\JobController;

Route::get('/jobs', [JobController::class, 'getJobs'])->name('get.jobs');
Route::get('/{jobId}', [JobController::class, 'getJobDetails'])->name('get.jobs');
Route::delete('/delete', [JobController::class, 'deleteJob'])->name('get.jobs');
Route::get('/errors', [JobController::class, 'getJobErrors'])->name('get.jobs');
