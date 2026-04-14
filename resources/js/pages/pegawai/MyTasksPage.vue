<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { ClipboardList, CheckCircle2, Clock, AlertTriangle } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { useTaskAssignmentStore } from '@/stores/taskAssignment';

const store = useTaskAssignmentStore();
const toast = useToast();

const statusDialog = ref({ open: false, task: null });
const statusForm = reactive({ status: '', actual_value: '' });
const statusError = ref('');
const statusSubmitting = ref(false);

const tasks = computed(() => store.myTasks);

const summary = computed(() => ({
    total: tasks.value.length,
    done: tasks.value.filter((t) => t.status === 'Selesai').length,
    inProgress: tasks.value.filter((t) => t.status === 'Dalam Proses').length,
    overdue: tasks.value.filter((t) => isOverdue(t)).length,
}));

const summaryCards = [
    { key: 'total',      label: 'Total Tugas',   icon: ClipboardList, color: 'blue' },
    { key: 'done',       label: 'Selesai',        icon: CheckCircle2,  color: 'emerald' },
    { key: 'inProgress', label: 'Dalam Proses',   icon: Clock,         color: 'amber' },
    { key: 'overdue',    label: 'Terlambat',      icon: AlertTriangle, color: 'rose' },
];

const colorSchemes = {
    blue:    { border: 'border-blue-200',    icon: 'text-blue-500 bg-blue-50',    value: 'text-blue-700' },
    emerald: { border: 'border-emerald-200', icon: 'text-emerald-500 bg-emerald-50', value: 'text-emerald-700' },
    amber:   { border: 'border-amber-200',   icon: 'text-amber-500 bg-amber-50',  value: 'text-amber-700' },
    rose:    { border: 'border-rose-200',    icon: 'text-rose-500 bg-rose-50',    value: 'text-rose-700' },
};

const statusOptions = [
    { value: 'Pending',       label: 'Pending' },
    { value: 'Dalam Proses',  label: 'Dalam Proses' },
    { value: 'Selesai',       label: 'Selesai' },
];

const statusBadge = {
    'Selesai':      'badge-success',
    'Dalam Proses': 'badge-warning',
    'Pending':      'badge-neutral',
};

onMounted(() => store.fetchMyTasks());

function isOverdue(task) {
    if (task.status === 'Selesai') return false;
    if (!task.end_date) return false;
    return new Date(task.end_date) < new Date();
}

function daysRemaining(task) {
    if (!task.end_date) return null;
    const diff = Math.ceil((new Date(task.end_date) - new Date()) / (1000 * 60 * 60 * 24));
    return diff;
}

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function openStatusUpdate(task) {
    statusDialog.value = { open: true, task };
    statusForm.status = task.status ?? 'Pending';
    statusForm.actual_value = task.actual_value ?? '';
    statusError.value = '';
}

async function submitStatus() {
    statusSubmitting.value = true;
    statusError.value = '';
    try {
        await store.updateTaskStatus(statusDialog.value.task.id, {
            status: statusForm.status,
            actual_value: statusForm.actual_value !== '' ? Number(statusForm.actual_value) : null,
        });
        toast.success('Status tugas berhasil diperbarui.');
        statusDialog.value.open = false;
        await store.fetchMyTasks();
    } catch (err) {
        statusError.value = err.response?.data?.message || 'Gagal memperbarui status.';
    } finally {
        statusSubmitting.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div>
                <div class="page-hero-meta">Pegawai · Tugas Saya</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Tugas yang Ditetapkan</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Daftar tugas yang ditetapkan HR untuk Anda. Perbarui status dan masukkan nilai aktual saat selesai.
                </p>
            </div>
        </section>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div
                v-for="card in summaryCards"
                :key="card.key"
                :class="['group relative overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md', colorSchemes[card.color].border]"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ card.label }}</p>
                        <p :class="['mt-2 tabular-nums tracking-tight text-3xl font-bold', colorSchemes[card.color].value]">
                            {{ summary[card.key] }}
                        </p>
                    </div>
                    <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', colorSchemes[card.color].icon]">
                        <component :is="card.icon" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Task List -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Tugas Saya</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ tasks.length }} tugas aktif</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 4" :key="i" class="h-20 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="tasks.length">
                    <div class="space-y-4">
                        <div
                            v-for="task in tasks"
                            :key="task.id"
                            :class="[
                                'rounded-2xl border bg-white p-5 shadow-sm transition-shadow hover:shadow-md',
                                isOverdue(task) ? 'border-l-4 border-l-rose-400' : 'border-slate-200',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-slate-900">{{ task.judul }}</span>
                                        <span :class="[statusBadge[task.status] ?? 'badge-neutral', '!text-[10px]']">
                                            {{ task.status }}
                                        </span>
                                        <span v-if="isOverdue(task)" class="badge-danger !text-[10px]">Terlambat</span>
                                    </div>

                                    <p v-if="task.deskripsi" class="mt-1.5 text-sm text-slate-500 line-clamp-2">
                                        {{ task.deskripsi }}
                                    </p>

                                    <div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                                            </svg>
                                            {{ formatDate(task.start_date) }} – {{ formatDate(task.end_date) }}
                                        </span>
                                        <span v-if="task.weight" class="flex items-center gap-1">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="9"/><path d="m9 12 2 2 4-4"/>
                                            </svg>
                                            Bobot {{ task.weight }}%
                                        </span>
                                        <span
                                            v-if="daysRemaining(task) !== null && !isOverdue(task) && task.status !== 'Selesai'"
                                            :class="[daysRemaining(task) <= 2 ? 'text-amber-500 font-medium' : '']"
                                        >
                                            {{ daysRemaining(task) === 0 ? 'Jatuh tempo hari ini' : `${daysRemaining(task)} hari lagi` }}
                                        </span>
                                        <span v-if="task.actual_value !== null && task.actual_value !== undefined" class="text-emerald-600 font-medium">
                                            Nilai aktual: {{ task.actual_value }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    class="btn-secondary shrink-0 !px-3 !py-1.5 text-xs"
                                    @click="openStatusUpdate(task)"
                                >
                                    Update Status
                                </button>
                            </div>

                            <!-- Progress bar -->
                            <div class="mt-4">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        :class="[
                                            'h-full rounded-full transition-all duration-500',
                                            task.status === 'Selesai' ? 'bg-emerald-500' :
                                            task.status === 'Dalam Proses' ? 'bg-amber-400' : 'bg-slate-300',
                                        ]"
                                        :style="{ width: task.status === 'Selesai' ? '100%' : task.status === 'Dalam Proses' ? '50%' : '5%' }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-16 text-center">
                    <svg class="mx-auto mb-4 h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                    </svg>
                    <p class="text-sm font-medium text-slate-400">Tidak ada tugas yang ditetapkan untuk Anda</p>
                    <p class="mt-1 text-xs text-slate-300">Tugas dari HR akan muncul di sini</p>
                </div>
            </div>
        </section>

        <!-- Status Update Dialog -->
        <Dialog v-model:open="statusDialog.open" title="Perbarui Status Tugas" class="max-w-md">
            <Alert v-if="statusError" variant="danger" class="mb-4">{{ statusError }}</Alert>

            <div v-if="statusDialog.task" class="mt-2 mb-4 rounded-xl bg-slate-50 p-3">
                <p class="text-sm font-semibold text-slate-700">{{ statusDialog.task.judul }}</p>
                <p class="mt-0.5 text-xs text-slate-400">
                    {{ formatDate(statusDialog.task.start_date) }} – {{ formatDate(statusDialog.task.end_date) }}
                </p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select v-model="statusForm.status" class="form-input">
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>

                <div v-if="statusForm.status === 'Selesai'">
                    <label class="form-label">Nilai Aktual</label>
                    <Input v-model="statusForm.actual_value" type="number" step="0.01" placeholder="Masukkan nilai realisasi..." />
                    <p class="mt-1 text-[11px] text-slate-400">
                        Nilai ini akan digunakan untuk penghitungan skor KPI.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button class="btn-secondary" :disabled="statusSubmitting" @click="statusDialog.open = false">Batal</button>
                <button class="btn-primary" :disabled="statusSubmitting" @click="submitStatus">
                    {{ statusSubmitting ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>
