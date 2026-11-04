<?php

use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\FuelLoadController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\EquipmentReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('equipment', EquipmentController::class);

Route::get('equipment/{equipment}/fuel-loads', [FuelLoadController::class, 'index']);
Route::post('equipment/{equipment}/fuel-loads', [FuelLoadController::class, 'store']);

Route::get('equipment/{equipment}/maintenances', [MaintenanceController::class, 'index']);
Route::post('equipment/{equipment}/maintenances', [MaintenanceController::class, 'store']);

Route::get('equipment/{equipment}/report', EquipmentReportController::class);