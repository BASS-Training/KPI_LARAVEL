<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import api from '@/services/api';

const logs = ref([]);
const isLoading = ref(false);
const pagination = ref({ total: 0, current_page: 1, last_page: 1 });
const filterAction = ref('');
const page = ref(1);

async function fetchLogs() {
    isLoading.value = true;
    try {
        const params = { page: page.value, per_page: 20 };
        if (filterAction.value) params.action = filterAction.value;
        const { data: resp } = await api.get('/logs', { params });
        logs.value = resp.data ?? [];
        pagination.value = {
            total: resp.meta?.total ?? 0,
            current_page: resp.meta?.current_page ?? 1,
            last_page: resp.meta?.last_page ?? 1,
        };
    } finally {
        isLoading.value = false;
    }
}

onMounted(fetchLogs);

function changePage(p) {
    page.value = p;
    fetchLogs();
}

function applyFilter() {
    page.value = 1;
    fetchLogs();
}

function resetFilter() {
    filterAction.value = '';
    page.value = 1;
    fetchLogs();
}

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

const actionBadge = {
    create: 'badge-success',
    update: 'badge-info',
    delete: 'badge-danger',
    login:  'badge-neutral',
    logout: 'badge-neutral',
};

function badgeClass(action) {
    return actionBadge[action?.toLowerCase()] ?? 'badge-neutral';
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel · Audit</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Log Aktivitas</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Pantau seluruh aktivitas pengguna dalam sistem.
                </p>
            </div>
        </section>

        <!-- Filter -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Filter</p>
            </div>
            <div class="flex flex-wrap items-end gap-4 p-6">
                <div class="w-full sm:w-48">
                    <label class="form-label">Aksi</label>
                    <select v-model="filterAction" class="form-input">
                        <option value="">— Semua Aksi —</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                    </select>
                </div>
                <div class="flex gap-2 pb-0.5">
                    <button class="btn-primary" @click="applyFilter">Terapkan</button>
                    <button class="btn-secondary" @click="resetFilter">Reset</button>
                </div>
            </div>
        </section>

        <!-- Log List -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Riwayat Aktivitas</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ pagination.total }} log tercatat</h3>
            </div>

            <div class="p-6">
                <template v-if="isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 8" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="logs.length">
                    <div class="space-y-2">
                        <div
                            v-for="log in logs"
                            :key="log.id"
                            class="data-row"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="[badgeClass(log.action), '!text-[10px]']">{{ log.action }}</span>
                                    <span class="text-sm font-medium text-slate-800">
                                        {{ log.model_type ?? '-' }}
                                        <span v-if="log.model_id" class="font-normal text-slate-400">#{{ log.model_id }}</span>
                                    </span>
                                </div>
                                <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                    <span v-if="log.user">
                                        <svg class="mr-0.5 inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                                        </svg>
                                        {{ log.user.nama ?? log.user.email ?? '-' }}
                                    </span>
                                    <span v-if="log.ip_address">· IP {{ log.ip_address }}</span>
                                    <span>· {{ formatDate(log.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
                        <button
                            class="btn-secondary !px-3 !py-1.5 text-xs"
                            :disabled="pagination.current_page <= 1"
                            @click="changePage(pagination.current_page - 1)"
                        >
                            Sebelumnya
                        </button>
                        <span class="text-xs text-slate-500">
                            Halaman {{ pagination.current_page }} / {{ pagination.last_page }}
                        </span>
                        <button
                            class="btn-secondary !px-3 !py-1.5 text-xs"
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="changePage(pagination.current_page + 1)"
                        >
                            Berikutnya
                        </button>
                    </div>
                </template>

                <div v-else class="py-14 text-center">
                    <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>
                    <p class="text-sm text-slate-400">Tidak ada log aktivitas ditemukan.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
