<script setup>
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Dialog from '@/components/ui/Dialog.vue';

const props = defineProps({
    collapsed: { type: Boolean, default: false },
    mobile: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'toggle']);

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const showLogoutDialog = ref(false);
const user = computed(() => auth.user);

const navMap = {
    pegawai: [
        {
            section: 'Menu Utama',
            items: [
                { label: 'Beranda', to: '/dashboard', icon: `<path d="M3 10.75L12 4l9 6.75V20a1 1 0 0 1-1 1h-5.5v-6.25h-5V21H4a1 1 0 0 1-1-1v-9.25Z"/>` },
                { label: 'Input Pekerjaan', to: '/pekerjaan', icon: `<path d="M7 3h10v18H7z"/><path d="M10 8h4M10 12h4M10 16h4"/>` },
                { label: 'Laporan KPI', to: '/laporan-kpi', icon: `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>` },
                { label: 'Progress KPI', to: '/progress-kpi', icon: `<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>` },
            ],
        },
        {
            section: 'Lainnya',
            items: [
                { label: 'Notifikasi', to: '/notifikasi', icon: `<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>` },
            ],
        },
    ],
    hr_manager: [
        {
            section: 'Menu Utama',
            items: [
                { label: 'Beranda HR', to: '/hr/dashboard', icon: `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>` },
                { label: 'Mapping KPI', to: '/hr/mapping', icon: `<path d="M7 3h10v18H7z"/><path d="M10 8h4M10 12h4M10 16h4"/>` },
                { label: 'Tinjau Laporan KPI', to: '/hr/laporan-review', icon: `<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>` },
                { label: 'Detail KPI Pegawai', to: '/hr/kpi-pegawai', icon: `<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M20 8v6m3-3h-6"/>` },
                { label: 'Analytics', to: '/hr/analytics', icon: `<path d="M4 19V5m0 14h16"/><path d="M7 15V9m5 6V5m5 10v-3"/>` },
                { label: 'Log Aktivitas', to: '/hr/logs', icon: `<path d="M4 6h16M4 10h16M4 14h10M4 18h6"/>` },
            ],
        },
        {
            section: 'HR Panel',
            items: [
                { label: 'Manajemen Pegawai', to: '/hr/pegawai', icon: `<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>` },
                { label: 'Manajemen Divisi', to: '/hr/divisi', icon: `<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>` },
                { label: 'Manajemen Departemen', to: '/hr/departemen', icon: `<path d="M3 9h18M9 21V9m6 12V9M3 3h18v18H3z"/>` },
                { label: 'Manajemen Jabatan', to: '/hr/jabatan', icon: `<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>` },
                { label: 'Komponen KPI', to: '/hr/kpi-components', icon: `<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>` },
                { label: 'SLA Pekerjaan', to: '/hr/sla', icon: `<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>` },
                { label: 'Pengaturan', to: '/hr/settings', icon: `<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="M3 12h2m14 0h2M12 3v2m0 14v2M5.64 5.64l1.41 1.41m9.9 9.9 1.41 1.41m0-12.72-1.41 1.41m-9.9 9.9-1.41 1.41"/>` },
            ],
        },
        {
            section: 'Lainnya',
            items: [
                { label: 'Notifikasi', to: '/notifikasi', icon: `<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>` },
            ],
        },
    ],
    direktur: [
        {
            section: 'Menu Utama',
            items: [
                { label: 'Executive Dashboard', to: '/direktur/dashboard', icon: `<path d="M4 19V5m0 14h16"/><path d="M7 15V9m5 6V5m5 10v-3"/>` },
                { label: 'Analytics', to: '/direktur/analytics', icon: `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>` },
                { label: 'Ranking Pegawai', to: '/direktur/ranking', icon: `<path d="M8 6l4-4 4 4M8 18l4 4 4-4M4 10h16M4 14h16"/>` },
                { label: 'Beranda HR', to: '/hr/dashboard', icon: `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>` },
            ],
        },
        {
            section: 'Manajemen',
            items: [
                { label: 'Tinjau Laporan KPI', to: '/hr/laporan-review', icon: `<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>` },
                { label: 'Detail KPI Pegawai', to: '/hr/kpi-pegawai', icon: `<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M20 8v6m3-3h-6"/>` },
                { label: 'Analytics HR', to: '/hr/analytics', icon: `<path d="M4 19V5m0 14h16"/><path d="M7 15V9m5 6V5m5 10v-3"/>` },
                { label: 'Log Aktivitas', to: '/hr/logs', icon: `<path d="M4 6h16M4 10h16M4 14h10M4 18h6"/>` },
                { label: 'Manajemen Divisi', to: '/hr/divisi', icon: `<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>` },
                { label: 'Manajemen Departemen', to: '/hr/departemen', icon: `<path d="M3 9h18M9 21V9m6 12V9M3 3h18v18H3z"/>` },
                { label: 'Pengaturan', to: '/hr/settings', icon: `<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="M3 12h2m14 0h2M12 3v2m0 14v2M5.64 5.64l1.41 1.41m9.9 9.9 1.41 1.41m0-12.72-1.41 1.41m-9.9 9.9-1.41 1.41"/>` },
            ],
        },
        {
            section: 'Lainnya',
            items: [
                { label: 'Notifikasi', to: '/notifikasi', icon: `<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>` },
            ],
        },
    ],
};

const navGroups = computed(() => navMap[user.value?.role] ?? navMap.pegawai);

function isActive(to) {
    return route.path === to || route.path.startsWith(to + '/');
}

async function confirmLogout() {
    showLogoutDialog.value = false;
    await auth.logout();
}

function navigate(to) {
    router.push(to);
    emit('close');
}

const avatarLetter = computed(() => (user.value?.nama || 'U').slice(0, 1).toUpperCase());
</script>

<template>
    <div class="app-sidebar flex h-full w-full flex-col">

        <!-- ── Brand header ─────────────────────────────────────────────── -->
        <div
            :class="[
                'flex items-center border-b border-white/10 px-3 py-3.5',
                collapsed ? 'justify-center' : 'gap-3',
            ]"
        >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600">
                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>
                </svg>
            </div>

            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-2"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="!collapsed" class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-bold text-white">BASS KPI</p>
                    <p class="truncate text-[10px] text-white/40">Training Center</p>
                </div>
            </Transition>

            <!-- Close button on mobile -->
            <button
                v-if="mobile"
                type="button"
                class="ml-auto flex h-7 w-7 items-center justify-center rounded-md text-white/50 hover:bg-white/10 hover:text-white"
                @click="$emit('close')"
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>

            <!-- Collapse toggle on desktop -->
            <button
                v-else-if="!mobile"
                type="button"
                :class="[
                    'flex h-7 w-7 items-center justify-center rounded-md text-white/40 transition hover:bg-white/10 hover:text-white',
                    collapsed ? 'ml-0 mt-1' : 'ml-auto',
                ]"
                :title="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                @click="$emit('toggle')"
            >
                <svg
                    :class="['h-3.5 w-3.5 transition-transform duration-300', collapsed ? 'rotate-180' : '']"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
        </div>

        <!-- ── Navigation ─────────────────────────────────────────────── -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden px-2 py-3">
            <template v-for="group in navGroups" :key="group.section">
                <!-- Section label (hidden when collapsed) -->
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="!collapsed" class="sidebar-section-title">{{ group.section }}</div>
                </Transition>

                <div :class="['space-y-0.5', collapsed ? 'mb-4 mt-2' : 'mb-3 mt-1']">
                    <button
                        v-for="item in group.items"
                        :key="item.to"
                        type="button"
                        :class="[
                            'sidebar-link group relative',
                            isActive(item.to) ? 'is-active' : '',
                            collapsed ? 'w-full justify-center px-0 py-2.5' : '',
                        ]"
                        :title="collapsed ? item.label : undefined"
                        @click="navigate(item.to)"
                    >
                        <!-- Active indicator bar -->
                        <span
                            v-if="isActive(item.to)"
                            class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-blue-400"
                        />

                        <span :class="['sidebar-link-icon', collapsed ? 'mx-auto' : '']">
                            <svg
                                class="h-[18px] w-[18px]"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                                v-html="item.icon"
                            />
                        </span>

                        <Transition
                            enter-active-class="transition-all duration-200"
                            enter-from-class="opacity-0 -translate-x-1"
                            enter-to-class="opacity-100 translate-x-0"
                            leave-active-class="transition-all duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <span v-if="!collapsed" class="truncate text-[13.5px]">{{ item.label }}</span>
                        </Transition>

                        <!-- Tooltip when collapsed -->
                        <span
                            v-if="collapsed"
                            class="pointer-events-none absolute left-full ml-3 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-lg ring-1 ring-white/10 transition-opacity group-hover:opacity-100"
                        >
                            {{ item.label }}
                        </span>
                    </button>
                </div>
            </template>
        </nav>

        <!-- ── User footer ─────────────────────────────────────────────── -->
        <div class="border-t border-white/10 p-2.5">
            <div
                :class="[
                    'flex items-center gap-2.5 rounded-xl px-2.5 py-2',
                    collapsed ? 'justify-center' : '',
                ]"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">
                    {{ avatarLetter }}
                </div>

                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-all duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="!collapsed" class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-semibold text-white">{{ user?.nama || '-' }}</p>
                        <p class="truncate text-[10px] text-white/40">{{ user?.jabatan || user?.role || '-' }}</p>
                    </div>
                </Transition>

                <button
                    v-if="!collapsed"
                    type="button"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-white/40 transition hover:bg-white/10 hover:text-rose-400"
                    title="Keluar"
                    @click="showLogoutDialog = true"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                </button>

                <!-- Logout button when collapsed -->
                <button
                    v-if="collapsed"
                    type="button"
                    class="absolute bottom-3 right-0 left-0 mx-auto flex h-7 w-7 items-center justify-center rounded-md text-white/40 transition hover:bg-white/10 hover:text-rose-400"
                    title="Keluar"
                    @click="showLogoutDialog = true"
                />
            </div>
        </div>
    </div>

    <Dialog
        v-model:open="showLogoutDialog"
        title="Keluar dari aplikasi"
        description="Apakah Anda yakin ingin keluar? Anda perlu login kembali untuk mengakses dashboard."
    >
        <div class="mt-4 flex justify-end gap-2">
            <button class="btn-secondary" @click="showLogoutDialog = false">Batal</button>
            <button
                class="btn-danger"
                :disabled="auth.isLoading"
                @click="confirmLogout"
            >
                {{ auth.isLoading ? 'Keluar...' : 'Keluar' }}
            </button>
        </div>
    </Dialog>
</template>
