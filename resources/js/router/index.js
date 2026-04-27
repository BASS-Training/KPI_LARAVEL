import { createRouter, createWebHistory } from 'vue-router';
import { readStoredUser } from '@/lib/authStorage';

// ─── Page imports (lazy-loaded untuk performa lebih baik) ─────────────────
const LoginPage = () => import('@/pages/LoginPage.vue');
const ForbiddenPage = () => import('@/pages/ForbiddenPage.vue');

// Pegawai
const PegawaiDashboard = () => import('@/pages/pegawai/DashboardPage.vue');
const PekerjaanPage = () => import('@/pages/pegawai/PekerjaanPage.vue');

// HR Manager
const HRDashboard = () => import('@/pages/hr/DashboardPage.vue');
const PegawaiPage = () => import('@/pages/hr/PegawaiPage.vue');
const MappingPage = () => import('@/pages/hr/MappingPage.vue');
const KpiComponentPage = () => import('@/pages/hr/KpiComponentPage.vue');
const SlaPage = () => import('@/pages/hr/SlaPage.vue');
const SettingsPage = () => import('@/pages/hr/SettingsPage.vue');

// HR Manager (new)
const DepartmentPage         = () => import('@/pages/hr/DepartmentPage.vue');
const HRAnalyticsPage        = () => import('@/pages/hr/AnalyticsPage.vue');
const KpiReportReviewPage    = () => import('@/pages/hr/KpiReportReviewPage.vue');
const EmployeeKpiPage        = () => import('@/pages/hr/EmployeeKpiPage.vue');
const ActivityLogsPage       = () => import('@/pages/hr/ActivityLogsPage.vue');
const PositionPage           = () => import('@/pages/hr/PositionPage.vue');

// Pegawai (new)
const KpiReportPage = () => import('@/pages/pegawai/KpiReportPage.vue');

// Direktur
const DirekturDashboard = () => import('@/pages/direktur/DashboardPage.vue');
const DirekturAnalyticsPage = () => import('@/pages/direktur/AnalyticsPage.vue');
const DirekturRankingPage = () => import('@/pages/direktur/RankingPage.vue');

// Pegawai progress
const KpiProgressPage = () => import('@/pages/pegawai/KpiProgressPage.vue');

// Enterprise KPI pages
const MyTasksPage           = () => import('@/pages/pegawai/MyTasksPage.vue');
const TaskAssignmentPage    = () => import('@/pages/hr/TaskAssignmentPage.vue');
const KpiIndicatorPage      = () => import('@/pages/hr/KpiIndicatorPage.vue');

// Shared
const NotificationsPage = () => import('@/pages/NotificationsPage.vue');

// ─── Admin Layout ────────────────────────────────────────────────────────
const AdminLayout        = () => import('@/layouts/AdminLayout.vue');

// ─── Multi-Tenant Admin Pages (v2) ───────────────────────────────────────
const TenantsPage        = () => import('@/pages/admin/TenantsPage.vue');
const KpiTemplatesPage   = () => import('@/pages/admin/KpiTemplatesPage.vue');
const KpiAssignmentsPage = () => import('@/pages/admin/KpiAssignmentsPage.vue');
const KpiSubmissionPage    = () => import('@/pages/admin/KpiSubmissionPage.vue');
const AdminDashboardPage   = () => import('@/pages/admin/DashboardPage.vue');
const UsersPage            = () => import('@/pages/admin/UsersPage.vue');
const TenantDetailPage     = () => import('@/pages/admin/TenantDetailPage.vue');
const AuditLogsPage      = () => import('@/pages/admin/AuditLogsPage.vue');
const ReportsPage        = () => import('@/pages/admin/ReportsPage.vue');

// ─── Route definitions ─────────────────────────────────────────────────────
const routes = [
    // Root redirect
    { path: '/', redirect: () => defaultRouteForRole(readStoredUser()?.role) },

    // Guest only
    { path: '/login', component: LoginPage, meta: { guest: true } },

    // Pegawai
    {
        path: '/dashboard',
        component: PegawaiDashboard,
        meta: { requiresAuth: true, roles: ['pegawai'] },
    },
    {
        path: '/pekerjaan',
        component: PekerjaanPage,
        meta: { requiresAuth: true, roles: ['pegawai'] },
    },
    {
        path: '/laporan-kpi',
        component: KpiReportPage,
        meta: { requiresAuth: true, roles: ['pegawai'] },
    },
    {
        path: '/progress-kpi',
        component: KpiProgressPage,
        meta: { requiresAuth: true, roles: ['pegawai'] },
    },
    {
        path: '/my-tasks',
        component: MyTasksPage,
        meta: { requiresAuth: true, roles: ['pegawai'] },
    },

    // HR Manager
    {
        path: '/hr/dashboard',
        component: HRDashboard,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/pegawai',
        component: PegawaiPage,
        meta: { requiresAuth: true, roles: ['hr_manager'] },
    },
    {
        path: '/hr/mapping',
        component: MappingPage,
        meta: { requiresAuth: true, roles: ['hr_manager'] },
    },
    {
        path: '/hr/kpi-components',
        component: KpiComponentPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/sla',
        component: SlaPage,
        meta: { requiresAuth: true, roles: ['hr_manager'] },
    },
    {
        path: '/hr/settings',
        component: SettingsPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/departemen',
        component: DepartmentPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/analytics',
        component: HRAnalyticsPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/laporan-review',
        component: KpiReportReviewPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/kpi-pegawai',
        component: EmployeeKpiPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/logs',
        component: ActivityLogsPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/jabatan',
        component: PositionPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/penugasan',
        component: TaskAssignmentPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/kpi-indicators',
        component: KpiIndicatorPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },

    // Direktur
    {
        path: '/direktur/dashboard',
        component: DirekturDashboard,
        meta: { requiresAuth: true, roles: ['direktur'] },
    },
    {
        path: '/direktur/analytics',
        component: DirekturAnalyticsPage,
        meta: { requiresAuth: true, roles: ['direktur'] },
    },
    {
        path: '/direktur/ranking',
        component: DirekturRankingPage,
        meta: { requiresAuth: true, roles: ['direktur'] },
    },

    // Shared
    {
        path: '/notifikasi',
        component: NotificationsPage,
        meta: { requiresAuth: true, roles: ['pegawai', 'hr_manager', 'direktur'] },
    },

    // Halaman khusus
    { path: '/403', component: ForbiddenPage },

    // ─── Multi-Tenant v2 Routes (Admin Layout) ─────────────────────────
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'tenants',
                component: TenantsPage,
                meta: { requiresAuth: true, roles: ['super_admin'] },
            },
            {
                path: '',
                component: AdminDashboardPage,
                meta: { requiresAuth: true, roles: ['super_admin', 'tenant_admin', 'hr_manager', 'direktur'] },
            },
            {
                path: 'dashboard',
                component: AdminDashboardPage,
                meta: { requiresAuth: true, roles: ['super_admin', 'tenant_admin', 'hr_manager', 'direktur'] },
            },
            {
                path: 'tenants/:id',
                component: TenantDetailPage,
                meta: { requiresAuth: true, roles: ['super_admin'] },
            },
            {
                path: 'users',
                component: UsersPage,
                meta: { requiresAuth: true, roles: ['super_admin', 'tenant_admin', 'hr_manager'] },
            },
            {
                path: 'kpi/templates',
                component: KpiTemplatesPage,
                meta: { requiresAuth: true, roles: ['hr_manager', 'direktur', 'tenant_admin', 'super_admin'] },
            },
            {
                path: 'kpi/assignments',
                component: KpiAssignmentsPage,
                meta: { requiresAuth: true, roles: ['hr_manager', 'direktur', 'tenant_admin', 'super_admin'] },
            },
            {
                path: 'reports',
                component: ReportsPage,
                meta: { requiresAuth: true, roles: ['hr_manager', 'direktur', 'tenant_admin', 'super_admin'] },
            },
            {
                path: 'audit-logs',
                component: AuditLogsPage,
                meta: { requiresAuth: true, roles: ['hr_manager', 'direktur', 'tenant_admin', 'super_admin'] },
            },
        ],
    },

    // Employee: submit own KPI
    {
        path: '/kpi/submit',
        component: KpiSubmissionPage,
        meta: { requiresAuth: true, roles: ['pegawai', 'hr_manager', 'direktur', 'tenant_admin', 'super_admin'] },
    },

    // Catch-all → login
    { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

// ─── Navigation guards ─────────────────────────────────────────────────────
export function defaultRouteForRole(role) {
    if (role === 'super_admin') return '/admin/tenants';
    if (role === 'tenant_admin') return '/admin/kpi/templates';
    if (role === 'hr_manager') return '/hr/dashboard';
    if (role === 'direktur') return '/direktur/dashboard';

    return '/dashboard';
}

router.beforeEach((to, _from, next) => {
    const token = localStorage.getItem('token');
    const user  = readStoredUser();

    // Role aktif: pakai active_role (tenant-specific) jika ada, fallback ke role utama
    const activeRole = localStorage.getItem('active_role') || user?.role;

    if (token && !user) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    // Halaman guest-only (login): arahkan ke dashboard sesuai role jika sudah login
    if (to.meta.guest) {
        if (token && user) {
            return next(defaultRouteForRole(activeRole));
        }
        return next();
    }

    // Halaman yang butuh autentikasi
    if (to.meta.requiresAuth) {
        if (!token) return next('/login');

        // Cek role menggunakan activeRole (termasuk role rangkap)
        if (to.meta.roles && !to.meta.roles.includes(activeRole)) {
            return next('/403');
        }
    }

    next();
});

export default router;
