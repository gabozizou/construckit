<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\EquipmentReportController;
use App\Http\Controllers\Api\FuelLoadController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\MaintenanceDueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// publica: solo login
Route::post('login', [AuthController::class, 'login']);

// Todo lo demás exige el header "Authorization: Bearer <token>".
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('user', fn (Request $request) => $request->user());

    // Debe ir ANTES del apiResource: si no, "maintenance-due" se interpreta como {equipment}.
    Route::get('equipment/maintenance-due', MaintenanceDueController::class);

    Route::apiResource('equipment', EquipmentController::class);

    Route::get('equipment/{equipment}/fuel-loads', [FuelLoadController::class, 'index']);
    Route::post('equipment/{equipment}/fuel-loads', [FuelLoadController::class, 'store']);

    Route::get('equipment/{equipment}/maintenances', [MaintenanceController::class, 'index']);
    Route::post('equipment/{equipment}/maintenances', [MaintenanceController::class, 'store']);

    Route::get('equipment/{equipment}/report', EquipmentReportController::class);
});