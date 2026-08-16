<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hall\HallController;


Route::post('/create', [HallController::class, 'createHall'])->name("create.hall");
Route::delete('delete/{hallId}', [HallController::class, 'deleteHall'])->name("delete.hall");
Route::put('update/{hallId}', [HallController::class, 'updateHall'])->name("update.hall");
Route::get('/', [HallController::class, 'getAllHalls'])->name("get.hall");
Route::get('/active', [HallController::class, 'getActiveHalls'])->name("get.active.hall");
Route::post('/{hallId}/activate', [HallController::class, 'activateHall'])->name("activate.hall");
Route::post('/{hallId}/deactivate', [HallController::class, 'deactivateHall'])->name("deactivate.hall");
Route::get("/{hallId}", [HallController::class, "getHallDetails"])->name("hall.details");
Route::post("/import", [HallController::class, "importHall"])->name("import.hall");
