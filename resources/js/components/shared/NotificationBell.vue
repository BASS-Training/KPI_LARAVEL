<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notification';
import { useAutoRefresh } from '@/composables/useAutoRefresh';

const store = useNotificationStore();
const router = useRouter();
const open = ref(false);

onMounted(() => store.fetchNotifications());
// Poll every 15 s so the unread badge stays current across all pages
useAutoRefresh(() => store.fetchNotifications(), { interval: 15_000 });

function toggle() { open.value = !open.value; }
function close()  { open.value = false; }

async function handleMarkAll() {
    await store.markAllRead();
}

async function handleMarkOne(id) {
    await store.markRead(id);
}

async function handleDelete(id) {
    await store.deleteNotification(id);
}

function goToAll() {
    close();
    router.push('/notifikasi');
}

const typeIcon = {
    low_performance:    '⚠️',
    low_percentage:     '⚠️',
    deadline_reminder:  '🔔',
    task_assigned:      '📋',
    kpi_updated:        '📊',
    report_approved:    '✅',
    report_rejected:    '❌',
    info:               'ℹ️',
    success:            '✅',
};
</script>

<template>
    <div class="relative">
        <!-- Bell button -->
        <button
            type="button"
            class="relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
            :class="open ? 'bg-slate-100 text-slate-700' : ''"
            @click="toggle"
        >
            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span
                v-if="store.unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
            >
                {{ store.unreadCount > 9 ? '9+' : store.unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 top-10 z-50 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                style="width:360px"
            >
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-800">Notifikasi</span>
                        <span
                            v-if="store.unreadCount > 0"
                            class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-600"
                        >
                            {{ store.unreadCount }}
                        </span>
                    </div>
                    <button
                        v-if="store.unreadCount > 0"
                        type="button"
                        class="text-[11px] font-medium text-blue-600 hover:underline"
                        @click="handleMarkAll"
                    >
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- List -->
                <div class="max-h-72 overflow-y-auto">
                    <template v-if="store.isLoading">
                        <div class="px-4 py-8 text-center text-sm text-slate-400">Memuat...</div>
                    </template>
                    <template v-else-if="store.recent.length === 0">
                        <div class="px-4 py-8 text-center text-sm text-slate-400">Tidak ada notifikasi.</div>
                    </template>
                    <template v-else>
                        <div
                            v-for="n in store.recent"
                            :key="n.id"
                            :class="[
                                'group flex w-full gap-3 px-4 py-3 transition-colors hover:bg-slate-50',
                                !n.is_read ? 'bg-blue-50/60' : '',
                            ]"
                        >
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 gap-3 text-left"
                                @click="handleMarkOne(n.id)"
                            >
                                <span class="mt-0.5 text-base leading-none">{{ typeIcon[n.type] ?? '🔔' }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12px] font-semibold text-slate-800">{{ n.title }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-[11px] text-slate-500">{{ n.body }}</p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ new Date(n.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                    </p>
                                </div>
                            </button>
                            <div class="flex shrink-0 flex-col items-end gap-1 pt-0.5">
                                <button
                                    type="button"
                                    class="hidden h-5 w-5 items-center justify-center rounded text-slate-300 hover:bg-slate-100 hover:text-slate-500 group-hover:flex"
                                    title="Hapus"
                                    @click.stop="handleDelete(n.id)"
                                >
                                    ×
                                </button>
                                <div v-if="!n.is_read" class="h-2 w-2 rounded-full bg-blue-500" />
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="border-t border-slate-100 px-4 py-2.5">
                    <button
                        type="button"
                        class="w-full text-center text-[12px] font-medium text-blue-600 hover:underline"
                        @click="goToAll"
                    >
                        Lihat semua notifikasi →
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Backdrop -->
        <div v-if="open" class="fixed inset-0 z-40" @click="close" />
    </div>
</template>
