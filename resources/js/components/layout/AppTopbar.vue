<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import NotificationBell from '@/components/shared/NotificationBell.vue';

defineEmits(['open-sidebar']);

const route = useRoute();
const auth = useAuthStore();
const user = computed(() => auth.user);

const pageMap = {
    '/dashboard': { title: 'Beranda', subtitle: 'PT. BASS Training Center & Consultant' },
    '/pekerjaan': { title: 'Input Pekerjaan', subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/dashboard': { title: 'Beranda HR', subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/pegawai': { title: 'Manajemen Pegawai', subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/mapping': { title: 'Mapping KPI', subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/kpi-components': { title: 'Komponen KPI', subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/sla': { title: 'SLA Pekerjaan', subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/settings': { title: 'Pengaturan', subtitle: 'PT. BASS Training Center & Consultant' },
    '/direktur/dashboard':   { title: 'Executive Dashboard', subtitle: 'PT. BASS Training Center & Consultant' },
    '/direktur/analytics':   { title: 'Analytics',          subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/divisi':            { title: 'Manajemen Divisi',   subtitle: 'PT. BASS Training Center & Consultant' },
    '/hr/analytics':         { title: 'Analytics',          subtitle: 'PT. BASS Training Center & Consultant' },
    '/laporan-kpi':          { title: 'Laporan KPI',        subtitle: 'PT. BASS Training Center & Consultant' },
    '/notifikasi':           { title: 'Notifikasi',         subtitle: 'PT. BASS Training Center & Consultant' },
};

const pageInfo = computed(() => pageMap[route.path] || { title: 'Dashboard KPI', subtitle: 'PT. BASS Training Center & Consultant' });
</script>

<template>
    <header class="app-topbar">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button
                type="button"
                class="topbar-menu-button lg:hidden"
                aria-label="Buka menu"
                @click="$emit('open-sidebar')"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>

            <div class="min-w-0">
                <h1 class="text-[15px] font-semibold leading-tight text-slate-900">
                    {{ pageInfo.title }}
                </h1>
                <p class="text-[11px] text-slate-400">{{ pageInfo.subtitle }}</p>
            </div>
        </div>

        <slot name="actions" />

        <NotificationBell />

        <div class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 sm:flex">
            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-600 text-[11px] font-bold text-white">
                {{ (user?.nama || 'U').slice(0, 1).toUpperCase() }}
            </div>
            <div class="min-w-0">
                <p class="max-w-[140px] truncate text-xs font-semibold text-slate-800">{{ user?.nama || '-' }}</p>
                <p class="max-w-[140px] truncate text-[10px] text-slate-400">{{ user?.jabatan || user?.role || '-' }}</p>
            </div>
        </div>
    </header>
</template>
