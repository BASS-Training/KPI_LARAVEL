<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTheme } from '@/composables/useTheme';
import NotificationBell from '@/components/shared/NotificationBell.vue';

defineEmits(['open-sidebar', 'toggle-sidebar']);

const route = useRoute();
const auth = useAuthStore();
const { isDark, toggle: toggleTheme } = useTheme();
const user = computed(() => auth.user);

// ── Live / Echo connectivity indicator ──────────────────────────────────────
const isLive = ref(false);

function checkEcho() {
    isLive.value = !!(window.Echo?.connector?.pusher?.connection?.state === 'connected');
}

let echoTimer = null;
onMounted(() => {
    checkEcho();
    echoTimer = setInterval(checkEcho, 3000);
});
onUnmounted(() => clearInterval(echoTimer));

// ── Page title map ───────────────────────────────────────────────────────────
const pageMap = {
    '/dashboard': 'Beranda',
    '/pekerjaan': 'Input Pekerjaan',
    '/laporan-kpi': 'Laporan KPI',
    '/progress-kpi': 'Progress KPI',
    '/hr/dashboard': 'Beranda HR',
    '/hr/pegawai': 'Manajemen Pegawai',
    '/hr/mapping': 'Mapping KPI',
    '/hr/jabatan': 'Manajemen Jabatan',
    '/hr/kpi-components': 'Komponen KPI',
    '/hr/sla': 'SLA Pekerjaan',
    '/hr/settings': 'Pengaturan',
    '/hr/divisi': 'Manajemen Divisi',
    '/hr/departemen': 'Manajemen Departemen',
    '/hr/analytics': 'Analytics KPI',
    '/hr/laporan-review': 'Tinjau Laporan KPI',
    '/hr/kpi-pegawai': 'Detail KPI Pegawai',
    '/hr/logs': 'Log Aktivitas',
    '/direktur/dashboard': 'Executive Dashboard',
    '/direktur/analytics': 'Analytics',
    '/direktur/ranking': 'Ranking KPI Pegawai',
    '/notifikasi': 'Notifikasi',
};

const pageTitle = computed(() => pageMap[route.path] ?? 'Dashboard KPI');
const avatarLetter = computed(() => (user.value?.nama || 'U').slice(0, 1).toUpperCase());
</script>

<template>
    <header class="app-topbar">

        <!-- Left: menu button + title ───────────────────────────────────── -->
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <!-- Mobile hamburger -->
            <button
                type="button"
                class="topbar-menu-button lg:hidden"
                aria-label="Buka menu"
                @click="$emit('open-sidebar')"
            >
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>

            <div class="min-w-0">
                <h1 class="truncate text-[14.5px] font-semibold leading-tight text-slate-900 dark:text-slate-100">
                    {{ pageTitle }}
                </h1>
                <p class="truncate text-[11px] text-slate-400 dark:text-slate-500">
                    PT. BASS Training Center &amp; Consultant
                </p>
            </div>
        </div>

        <!-- Slot for extra page actions -->
        <slot name="actions" />

        <!-- Right: live dot + dark toggle + notifications + user ─────────── -->
        <div class="flex shrink-0 items-center gap-2">

            <!-- Live indicator -->
            <div
                class="hidden items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 dark:border-slate-700 dark:bg-slate-900 sm:flex"
                :title="isLive ? 'Realtime aktif (WebSocket)' : 'Realtime via polling'"
            >
                <span
                    :class="[
                        'h-1.5 w-1.5 rounded-full',
                        isLive
                            ? 'bg-emerald-500 shadow-[0_0_0_2px_rgba(16,185,129,0.25)] animate-pulse'
                            : 'bg-amber-400',
                    ]"
                />
                <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ isLive ? 'Live' : 'Polling' }}
                </span>
            </div>

            <!-- Dark mode toggle -->
            <button
                type="button"
                class="topbar-icon-btn"
                :title="isDark ? 'Mode terang' : 'Mode gelap'"
                @click="toggleTheme"
            >
                <!-- Sun -->
                <svg v-if="isDark" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
                <!-- Moon -->
                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79Z"/>
                </svg>
            </button>

            <!-- Notifications -->
            <NotificationBell />

            <!-- User chip -->
            <div class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 dark:border-slate-700 dark:bg-slate-900 sm:flex">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-600 text-[11px] font-bold text-white">
                    {{ avatarLetter }}
                </div>
                <div class="min-w-0">
                    <p class="max-w-[130px] truncate text-xs font-semibold text-slate-800 dark:text-slate-100">
                        {{ user?.nama || '-' }}
                    </p>
                    <p class="max-w-[130px] truncate text-[10px] text-slate-400 dark:text-slate-500">
                        {{ user?.jabatan || user?.role || '-' }}
                    </p>
                </div>
            </div>
        </div>
    </header>
</template>
