<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
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
    Route::post('/kpi-components', [KpiComponentController::class, 'store'])->middleware('role:hr_manager,direktur');
    Route::put('/kpi-components/{kpiComponent}', [KpiComponentController::class, 'update'])->middleware('role:hr_manager,direktur');
    Route::delete('/kpi-components/{kpiComponent}', [KpiComponentController::class, 'destroy'])->middleware('role:hr_manager,direktur');

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
        Route::get('/per-department', [AnalyticsController::class, 'perDepartment']);
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

// =============================================================
// MULTI-TENANT API — v2
// All routes below use SetTenantContext middleware to resolve
// the authenticated user's tenant and enforce isolation.
// =============================================================

use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\KpiTemplateController;
use App\Http\Controllers\Api\EmployeeKpiAssignmentController;
use App\Http\Controllers\Api\TaskManagementController;
use App\Http\Controllers\Api\ReportManagementController;
use App\Http\Controllers\Api\AuditLogController;
use App\Models\KpiTemplateIndicator;
use App\Models\Tenant;

// --- Super Admin: Tenant Management ---
Route::middleware(['auth:sanctum', \App\Http\Middleware\SetTenantContext::class])
    ->prefix('v2')
    ->group(function () {

    // Tenant CRUD — Super Admin only
    Route::prefix('tenants')->middleware('role:super_admin')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('/{tenant}', [TenantController::class, 'show']);
        Route::put('/{tenant}', [TenantController::class, 'update']);
        Route::patch('/{tenant}/activate', [TenantController::class, 'activate']);
        Route::patch('/{tenant}/deactivate', [TenantController::class, 'deactivate']);
        Route::get('/{tenant}/users', [TenantController::class, 'users']);
    });

    // My tenants (any authenticated user)
    Route::get('/my/tenants', [UserManagementController::class, 'myTenants']);

    // --- User Management (Tenant Admin + HR Manager) ---
    Route::prefix('users')->middleware('role:super_admin,tenant_admin,hr_manager')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::post('/', [UserManagementController::class, 'store']);
        Route::get('/roles', [UserManagementController::class, 'roles']);
        Route::get('/{user}', [UserManagementController::class, 'show']);
        Route::put('/{user}', [UserManagementController::class, 'update']);
        Route::delete('/{user}', [UserManagementController::class, 'destroy']);

        // Multi-tenant user assignment
        Route::post('/{user}/tenants', [UserManagementController::class, 'assignToTenant'])
            ->middleware('role:super_admin,tenant_admin');
        Route::delete('/{user}/tenants/{tenant}', [UserManagementController::class, 'removeFromTenant'])
            ->middleware('role:super_admin,tenant_admin');
    });

    // --- KPI Templates (HR Manager) ---
    Route::prefix('kpi/templates')->group(function () {
        Route::get('/', [KpiTemplateController::class, 'index']);
        Route::post('/', [KpiTemplateController::class, 'store'])
            ->middleware('role:super_admin,tenant_admin,hr_manager');
        Route::get('/{kpiTemplate}', [KpiTemplateController::class, 'show']);
        Route::put('/{kpiTemplate}', [KpiTemplateController::class, 'update'])
            ->middleware('role:super_admin,tenant_admin,hr_manager');
        Route::delete('/{kpiTemplate}', [KpiTemplateController::class, 'destroy'])
            ->middleware('role:super_admin,tenant_admin,hr_manager');

        // Indicator sub-resources
        Route::post('/{kpiTemplate}/indicators', [KpiTemplateController::class, 'storeIndicator'])
            ->middleware('role:super_admin,tenant_admin,hr_manager');
    });

    Route::prefix('kpi/indicators')->middleware('role:super_admin,tenant_admin,hr_manager')->group(function () {
        Route::put('/{indicator}', [KpiTemplateController::class, 'updateIndicator']);
        Route::delete('/{indicator}', [KpiTemplateController::class, 'destroyIndicator']);
    });

    // --- KPI Assignments ---
    Route::prefix('kpi/assignments')->group(function () {
        Route::get('/', [EmployeeKpiAssignmentController::class, 'index']);
        Route::get('/my', [EmployeeKpiAssignmentController::class, 'myAssignments']);
        Route::post('/', [EmployeeKpiAssignmentController::class, 'store'])
            ->middleware('role:super_admin,tenant_admin,hr_manager,dept_head,supervisor');
        Route::get('/{assignment}', [EmployeeKpiAssignmentController::class, 'show']);

        // Workflow actions
        Route::post('/{assignment}/submit', [EmployeeKpiAssignmentController::class, 'submit']);
        Route::post('/{assignment}/approve', [EmployeeKpiAssignmentController::class, 'approve'])
            ->middleware('role:super_admin,tenant_admin,hr_manager,dept_head,supervisor');
        Route::post('/{assignment}/reject', [EmployeeKpiAssignmentController::class, 'reject'])
            ->middleware('role:super_admin,tenant_admin,hr_manager,dept_head,supervisor');
    });

    // --- Tasks ---
    Route::prefix('tasks/v2')->group(function () {
        Route::get('/', [TaskManagementController::class, 'index']);
        Route::get('/my', [TaskManagementController::class, 'myTasks']);
        Route::post('/', [TaskManagementController::class, 'store'])
            ->middleware('role:super_admin,tenant_admin,hr_manager,dept_head,supervisor');
        Route::get('/{task}', [TaskManagementController::class, 'show']);
        Route::put('/{task}', [TaskManagementController::class, 'update']);
        Route::delete('/{task}', [TaskManagementController::class, 'destroy'])
            ->middleware('role:super_admin,tenant_admin,hr_manager');
    });

    // --- Reports ---
    Route::prefix('reports')
        ->middleware('role:super_admin,tenant_admin,hr_manager,dept_head')
        ->group(function () {
            Route::get('/kpi-summary', [ReportManagementController::class, 'kpiSummary']);
            Route::get('/employee-performance', [ReportManagementController::class, 'employeePerformance']);
            Route::get('/department-performance', [ReportManagementController::class, 'departmentPerformance']);
            Route::get('/export/employee-pdf', [ReportManagementController::class, 'exportEmployeePdf']);
            Route::get('/export/summary-pdf', [ReportManagementController::class, 'exportSummaryPdf']);
        });

    // --- Audit Logs ---
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:super_admin,tenant_admin,hr_manager');
});
