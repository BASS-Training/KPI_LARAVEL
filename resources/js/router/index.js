import { createRouter, createWebHistory } from 'vue-router';

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
const DivisionPage = () => import('@/pages/hr/DivisionPage.vue');
const HRAnalyticsPage = () => import('@/pages/hr/AnalyticsPage.vue');

// Pegawai (new)
const KpiReportPage = () => import('@/pages/pegawai/KpiReportPage.vue');

// Direktur
const DirekturDashboard = () => import('@/pages/direktur/DashboardPage.vue');
const DirekturAnalyticsPage = () => import('@/pages/direktur/AnalyticsPage.vue');

// Shared
const NotificationsPage = () => import('@/pages/NotificationsPage.vue');

// ─── Route definitions ─────────────────────────────────────────────────────
const routes = [
    // Root redirect
    { path: '/', redirect: '/login' },

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
        meta: { requiresAuth: true, roles: ['hr_manager'] },
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
        path: '/hr/divisi',
        component: DivisionPage,
        meta: { requiresAuth: true, roles: ['hr_manager', 'direktur'] },
    },
    {
        path: '/hr/analytics',
        component: HRAnalyticsPage,
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

    // Shared
    {
        path: '/notifikasi',
        component: NotificationsPage,
        meta: { requiresAuth: true, roles: ['pegawai', 'hr_manager', 'direktur'] },
    },

    // Halaman khusus
    { path: '/403', component: ForbiddenPage },

    // Catch-all → login
    { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

// ─── Navigation guards ─────────────────────────────────────────────────────
router.beforeEach((to, _from, next) => {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || 'null');

    if (token && !user) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    // Halaman guest-only (login): arahkan ke dashboard sesuai role jika sudah login
    if (to.meta.guest) {
        if (token && user) {
            const role = user.role;
            if (role === 'hr_manager') return next('/hr/dashboard');
            if (role === 'direktur') return next('/direktur/dashboard');
            return next('/dashboard');
        }
        return next();
    }

    // Halaman yang butuh autentikasi
    if (to.meta.requiresAuth) {
        if (!token) return next('/login');

        // Cek role jika route punya pembatasan role
        if (to.meta.roles && !to.meta.roles.includes(user?.role)) {
            return next('/403');
        }
    }

    next();
});

export default router;
