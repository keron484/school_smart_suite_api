<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Job\JobController;

Route::post('/jobs', [JobController::class, 'getJobs'])->name('get.jobs');
Route::get('/{jobId}', [JobController::class, 'getJobDetails'])->name('get.job');
Route::delete('/{jobId}/delete', [JobController::class, 'deleteJob'])->name('delete.jobs');
Route::get('/{jobId}/errors', [JobController::class, 'getJobErrors'])->name('get.jobErrors');
