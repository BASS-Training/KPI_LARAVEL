<script setup>
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Dialog from '@/components/ui/Dialog.vue';

defineProps({ mobile: { type: Boolean, default: false } });
const emit = defineEmits(['close']);

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
                { label: 'Analytics', to: '/hr/analytics', icon: `<path d="M4 19V5m0 14h16"/><path d="M7 15V9m5 6V5m5 10v-3"/>` },
            ],
        },
        {
            section: 'HR Panel',
            items: [
                { label: 'Manajemen Pegawai', to: '/hr/pegawai', icon: `<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>` },
                { label: 'Manajemen Divisi', to: '/hr/divisi', icon: `<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>` },
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
                { label: 'Beranda HR', to: '/hr/dashboard', icon: `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>` },
            ],
        },
        {
            section: 'Manajemen',
            items: [
                { label: 'Analytics HR', to: '/hr/analytics', icon: `<path d="M4 19V5m0 14h16"/><path d="M7 15V9m5 6V5m5 10v-3"/>` },
                { label: 'Manajemen Divisi', to: '/hr/divisi', icon: `<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>` },
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

const navGroups = computed(() => navMap[user.value?.role] || navMap.pegawai);

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
</script>

<template>
    <div class="app-sidebar">
        <div class="relative flex items-center gap-3 border-b border-white/10 px-4 py-4">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600">
                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[13px] font-bold text-white">BASS KPI</p>
                <p class="truncate text-[11px] text-white/45">Training Center</p>
            </div>
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
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-3">
            <template v-for="group in navGroups" :key="group.section">
                <div class="sidebar-section-title">{{ group.section }}</div>
                <div class="mb-3 mt-1 space-y-0.5">
                    <button
                        v-for="item in group.items"
                        :key="item.to"
                        type="button"
                        :class="['sidebar-link', { 'is-active': isActive(item.to) }]"
                        @click="navigate(item.to)"
                    >
                        <span class="sidebar-link-icon">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" v-html="item.icon" />
                        </span>
                        <span>{{ item.label }}</span>
                    </button>
                </div>
            </template>
        </nav>

        <div class="border-t border-white/10 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">
                    {{ (user?.nama || 'U').slice(0, 1).toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-white">{{ user?.nama || '-' }}</p>
                    <p class="truncate text-[11px] text-white/45">{{ user?.jabatan || user?.role || '-' }}</p>
                </div>
                <button
                    type="button"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-white/40 transition-colors hover:bg-white/10 hover:text-white"
                    title="Keluar"
                    @click="showLogoutDialog = true"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                </button>
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
                class="btn-primary"
                style="background: linear-gradient(135deg, #dc2626, #b91c1c);"
                :disabled="auth.isLoading"
                @click="confirmLogout"
            >
                Keluar
            </button>
        </div>
    </Dialog>
</template>
