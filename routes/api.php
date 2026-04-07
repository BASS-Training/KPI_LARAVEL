<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\KpiComponentController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SlaController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('role:hr_manager,direktur');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('role:hr_manager,direktur');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('role:hr_manager,direktur');

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::put('/tasks/{task}/mapping', [TaskController::class, 'mapping'])->middleware('role:hr_manager,direktur');

    Route::get('/kpi/me', [KpiController::class, 'me']);
    Route::get('/kpi/ranking', [KpiController::class, 'ranking']);
    Route::get('/kpi/{user}', [KpiController::class, 'show'])->middleware('role:hr_manager,direktur');

    Route::get('/kpi-components', [KpiComponentController::class, 'index']);
    Route::post('/kpi-components', [KpiComponentController::class, 'store'])->middleware('role:hr_manager');
    Route::put('/kpi-components/{kpiComponent}', [KpiComponentController::class, 'update'])->middleware('role:hr_manager');
    Route::delete('/kpi-components/{kpiComponent}', [KpiComponentController::class, 'destroy'])->middleware('role:hr_manager');

    Route::get('/sla', [SlaController::class, 'index']);
    Route::post('/sla', [SlaController::class, 'store'])->middleware('role:hr_manager');
    Route::put('/sla/{sla}', [SlaController::class, 'update'])->middleware('role:hr_manager');
    Route::delete('/sla/{sla}', [SlaController::class, 'destroy'])->middleware('role:hr_manager');

    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update'])->middleware('role:hr_manager,direktur');

    Route::get('/logs', [LogController::class, 'index'])->middleware('role:hr_manager,direktur');
});
