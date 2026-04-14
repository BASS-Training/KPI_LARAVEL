<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\KpiComponentController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\KpiIndicatorController;
use App\Http\Controllers\Api\KpiManagementController;
use App\Http\Controllers\Api\KpiReportController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SlaController;
use App\Http\Controllers\Api\TaskController;
use App\Models\User;
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
    Route::get('/my-tasks', [TaskController::class, 'myTasks']);
    Route::put('/tasks/{task}/update-status', [TaskController::class, 'updateStatus']);
    Route::put('/tasks/{task}/mapping', [TaskController::class, 'mapping'])->middleware('role:hr_manager,direktur');

    Route::get('/kpi/me', [KpiController::class, 'me']);
    Route::get('/kpi/ranking', [KpiManagementController::class, 'ranking']);
    Route::get('/kpi/dashboard', [KpiManagementController::class, 'dashboard']);
    Route::get('/kpi/export', [ExportController::class, 'export'])->middleware('role:admin,hr_manager,direktur');
    Route::get('/kpi/user/{id}', [KpiManagementController::class, 'showUser']);
    Route::post('/kpi/input', [KpiManagementController::class, 'input'])->middleware('role:hr_manager,direktur');
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

    // Reference data
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('role:hr_manager,direktur');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->middleware('role:hr_manager,direktur');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->middleware('role:hr_manager,direktur');

    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store'])->middleware('role:hr_manager,direktur');
    Route::put('/positions/{position}', [PositionController::class, 'update'])->middleware('role:hr_manager,direktur');
    Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->middleware('role:hr_manager,direktur');

    // Divisions
    Route::get('/divisions', [DivisionController::class, 'index']);
    Route::post('/divisions', [DivisionController::class, 'store'])->middleware('role:hr_manager,direktur');
    Route::put('/divisions/{division}', [DivisionController::class, 'update'])->middleware('role:hr_manager,direktur');
    Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->middleware('role:hr_manager,direktur');

    // KPI Indicators
    Route::get('/kpi-indicators/meta', [KpiIndicatorController::class, 'meta'])->middleware('role:hr_manager,direktur');
    Route::get('/kpi-indicators', [KpiIndicatorController::class, 'index']);
    Route::post('/kpi-indicators', [KpiIndicatorController::class, 'store'])->middleware('role:hr_manager,direktur');
    Route::get('/kpi-indicators/{kpiIndicator}', [KpiIndicatorController::class, 'show']);
    Route::put('/kpi-indicators/{kpiIndicator}', [KpiIndicatorController::class, 'update'])->middleware('role:hr_manager,direktur');
    Route::delete('/kpi-indicators/{kpiIndicator}', [KpiIndicatorController::class, 'destroy'])->middleware('role:hr_manager,direktur');

    // KPI Reports
    Route::get('/kpi-reports', [KpiReportController::class, 'index']);
    Route::post('/kpi-reports', [KpiReportController::class, 'store']);
    Route::put('/kpi-reports/{kpiReport}', [KpiReportController::class, 'update']);
    Route::delete('/kpi-reports/{kpiReport}', [KpiReportController::class, 'destroy']);
    Route::post('/kpi-reports/{kpiReport}/evidence', [KpiReportController::class, 'uploadEvidence']);
    Route::patch('/kpi-reports/{kpiReport}/review', [KpiReportController::class, 'review'])->middleware('role:hr_manager,direktur');

    // Analytics (HR & Direktur only)
    Route::prefix('analytics')->middleware('role:hr_manager,direktur')->group(function () {
        Route::get('/trend', [AnalyticsController::class, 'trend']);
        Route::get('/per-division', [AnalyticsController::class, 'perDivision']);
        Route::get('/distribution', [AnalyticsController::class, 'distribution']);
        Route::get('/overview', [AnalyticsController::class, 'overview']);
    });

    // Export
    Route::prefix('export')->middleware('role:hr_manager,direktur')->group(function () {
        Route::get('/kpi/{user}/pdf', [ExportController::class, 'kpiPdf']);
        Route::get('/kpi', [ExportController::class, 'export']);
        Route::get('/ranking/csv', [ExportController::class, 'rankingCsv']);
        Route::get('/reports/csv', [ExportController::class, 'reportsCsv']);
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});
