<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Invigilator\InvigilatorController;

Route::post("/assign", [InvigilatorController::class, "assignExamInvigilator"])->name("create.invigilator");
Route::post("/remove", [InvigilatorController::class, "removeAssignedInvigilators"])->name("remove.invigilator");
Route::get("exam/{examId}", [InvigilatorController::class, "getAssignedInvigilators"])->name("get.invigilators");
Route::get("exam/{examId}/potential", [InvigilatorController::class, "getPotentialInvigilators"])->name("get.potentialInvigilators");
