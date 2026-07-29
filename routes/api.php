<?php


use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ably\AblyController;

Route::middleware(['auth:sanctum', IdentifyTenant::class])
->get('v1/auth/ably-token', [AblyController::class, 'getAuthAblyToken'])->name('get.auth.ably.token');

