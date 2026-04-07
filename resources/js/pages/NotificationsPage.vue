<script setup>
import { computed, onMounted } from 'vue';
import { useNotificationStore } from '@/stores/notification';
import AppLayout from '@/components/layout/AppLayout.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const store = useNotificationStore();

onMounted(() => store.fetchNotifications());

const typeIcon = {
    low_performance:   '⚠️',
    deadline_reminder: '🔔',
    info:              'ℹ️',
    success:           '✅',
};

const typeLabelMap = {
    low_performance:   'Performa Rendah',
    deadline_reminder: 'Pengingat Deadline',
    info:              'Informasi',
    success:           'Sukses',
};

const typeColorMap = {
    low_performance:   'border-l-red-400 bg-red-50/40',
    deadline_reminder: 'border-l-amber-400 bg-amber-50/40',
    info:              'border-l-blue-400 bg-blue-50/40',
    success:           'border-l-green-400 bg-green-50/40',
};

function formatDate(dt) {
    return new Date(dt).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function markOne(id) {
    await store.markRead(id);
}

async function markAll() {
    await store.markAllRead();
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">Sistem</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Notifikasi</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Semua notifikasi sistem — pengingat KPI, peringatan performa, dan informasi penting lainnya.
                    </p>
                </div>
                <button
                    v-if="store.unreadCount > 0"
                    class="btn-primary shrink-0"
                    @click="markAll"
                >
                    Tandai Semua Dibaca
                </button>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <!-- Header -->
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-heading">Daftar Notifikasi</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">
                            {{ store.notifications.length }} notifikasi
                            <span v-if="store.unreadCount > 0" class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-600">
                                {{ store.unreadCount }} belum dibaca
                            </span>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="store.isLoading" class="space-y-3 p-6">
                <Skeleton v-for="i in 6" :key="i" class="h-20 rounded-xl" />
            </div>

            <!-- Empty state -->
            <div v-else-if="!store.notifications.length" class="py-20 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">🔔</div>
                <p class="text-sm font-medium text-slate-500">Tidak ada notifikasi</p>
                <p class="mt-1 text-xs text-slate-400">Notifikasi baru akan muncul di sini</p>
            </div>

            <!-- Notification list -->
            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="n in store.notifications"
                    :key="n.id"
                    :class="[
                        'flex gap-4 border-l-4 px-6 py-4 transition-colors',
                        typeColorMap[n.type] ?? 'border-l-slate-300 bg-white',
                        !n.is_read ? '' : 'opacity-70',
                    ]"
                >
                    <!-- Icon -->
                    <div class="mt-0.5 shrink-0 text-xl leading-none">
                        {{ typeIcon[n.type] ?? '🔔' }}
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ n.title }}</p>
                                <span class="mt-0.5 inline-block rounded-full bg-white/60 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-slate-200">
                                    {{ typeLabelMap[n.type] ?? n.type }}
                                </span>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-[11px] text-slate-400">{{ formatDate(n.created_at) }}</span>
                                <div v-if="!n.is_read" class="h-2 w-2 rounded-full bg-blue-500" />
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs leading-5 text-slate-600">{{ n.body }}</p>
                    </div>

                    <!-- Mark as read button -->
                    <div class="shrink-0 self-start">
                        <button
                            v-if="!n.is_read"
                            type="button"
                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-800"
                            @click="markOne(n.id)"
                        >
                            Baca
                        </button>
                        <span v-else class="text-[11px] text-slate-400">✓ Dibaca</span>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
