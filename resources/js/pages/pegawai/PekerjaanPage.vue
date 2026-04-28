<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { BarChart2, CheckCircle2, Loader2, AlertTriangle } from 'lucide-vue-next';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import { useToast } from '@/composables/useToast';
import { useTaskStore } from '@/stores/task';
import { useKpiIndicatorStore } from '@/stores/kpiIndicator';
import { useAuthStore } from '@/stores/auth';

const taskStore = useTaskStore();
const kpiIndicatorStore = useKpiIndicatorStore();
const auth = useAuthStore();
const toast = useToast();

const showForm = ref(false);
const editMode = ref(false);
const editId = ref(null);
const editTaskType = ref(null);
const formLoading = ref(false);
const formError = ref('');

const deleteDialog = ref({ open: false, taskId: null, taskTitle: '' });
const deleteLoading = ref(false);

const emptyForm = () => ({
    judul: '',
    start_date: '',
    end_date: '',
    jenis_pekerjaan: '',
    waktu_mulai: '',
    waktu_selesai: '',
    status: '',
    ada_delay: false,
    ada_error: false,
    ada_komplain: false,
    deskripsi: '',
    kpi_indicator_id: '',
    evidence: null,
});

const evidenceFileName = ref('');
const indicatorOptions = computed(() =>
    kpiIndicatorStore.indicators.map(i => ({
        value: String(i.id),
        label: i.position ? `${i.name} (${i.position.nama})` : i.name,
    }))
);

const form = reactive(emptyForm());
const formErrors = reactive({});

const jobTypeOptions = ['Administratif', 'Teknis', 'Pelayanan', 'Koordinasi', 'Lainnya'];
const statusOptions = [
    { value: 'Selesai', label: 'Selesai' },
    { value: 'Dalam Proses', label: 'Dalam Proses' },
    { value: 'Pending', label: 'Pending' },
];

const currentPage = computed(() => taskStore.pagination.currentPage);
const lastPage = computed(() => taskStore.pagination.lastPage);
const tasks = computed(() => taskStore.tasks);
const isAssignedTaskEdit = computed(() => editMode.value && editTaskType.value === 'manual_assignment');

const taskSummary = computed(() => ({
    total: taskStore.pagination.total,
    done: tasks.value.filter((task) => task.status === 'Selesai').length,
    progress: tasks.value.filter((task) => task.status === 'Dalam Proses').length,
    flagged: tasks.value.filter((task) => task.ada_delay || task.ada_error || task.ada_komplain).length,
}));

const { refresh: refreshTasks, lastUpdated, isRefreshing } = useAutoRefresh(
    () => taskStore.fetchTasks(),
    { interval: 60_000 },
);

onMounted(() => {
    taskStore.fetchTasks();
    const params = { per_page: 200 };
    if (auth.user?.department_id) params.department_id = auth.user.department_id;
    kpiIndicatorStore.fetchIndicators(params);
});

function validate() {
    Object.assign(formErrors, { judul: '', start_date: '', end_date: '', jenis_pekerjaan: '', status: '', waktu_selesai: '' });

    let valid = true;

    if (!isAssignedTaskEdit.value && !form.judul.trim()) {
        formErrors.judul = 'Judul wajib diisi.';
        valid = false;
    }

    if (!isAssignedTaskEdit.value && !form.start_date) {
        formErrors.start_date = 'Tanggal mulai wajib diisi.';
        valid = false;
    }

    if (form.start_date && form.end_date && form.end_date < form.start_date) {
        formErrors.end_date = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        valid = false;
    }

    if (!isAssignedTaskEdit.value && !form.jenis_pekerjaan) {
        formErrors.jenis_pekerjaan = 'Jenis pekerjaan wajib dipilih.';
        valid = false;
    }

    if (!form.status) {
        formErrors.status = 'Status wajib dipilih.';
        valid = false;
    }

    if (form.waktu_mulai && form.waktu_selesai && form.waktu_mulai >= form.waktu_selesai) {
        formErrors.waktu_selesai = 'Waktu selesai harus setelah waktu mulai.';
        valid = false;
    }

    return valid;
}

function openCreate() {
    editMode.value = false;
    editId.value = null;
    editTaskType.value = null;
    Object.assign(form, emptyForm());
    Object.assign(formErrors, {});
    formError.value = '';
    evidenceFileName.value = '';
    showForm.value = true;
}

function openEdit(task) {
    editMode.value = true;
    editId.value = task.id;
    editTaskType.value = task.task_type || null;
    Object.assign(form, {
        judul: task.judul || '',
        start_date: task.start_date || task.tanggal || '',
        end_date: task.end_date || '',
        jenis_pekerjaan: task.jenis_pekerjaan || '',
        waktu_mulai: task.waktu_mulai || '',
        waktu_selesai: task.waktu_selesai || '',
        status: task.status || '',
        ada_delay: Boolean(task.ada_delay),
        ada_error: Boolean(task.ada_error),
        ada_komplain: Boolean(task.ada_komplain),
        deskripsi: task.deskripsi || '',
        kpi_indicator_id: task.kpi_indicator_id ? String(task.kpi_indicator_id) : '',
        evidence: null,
    });
    evidenceFileName.value = task.file_evidence ? task.file_evidence.split('/').pop() : '';
    Object.assign(formErrors, {});
    formError.value = '';
    showForm.value = true;
}

function onEvidenceChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.evidence = file;
    evidenceFileName.value = file?.name ?? '';
}

async function submitForm() {
    if (!validate()) return;

    formLoading.value = true;
    formError.value = '';

    try {
        const buildPayload = (base) => {
            const fd = new FormData();
            Object.entries(base).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== '') fd.append(k, v);
            });
            if (form.evidence) fd.append('file_evidence', form.evidence);
            return fd;
        };

        if (editMode.value && editId.value) {
            const base = isAssignedTaskEdit.value
                ? {
                    status: form.status,
                    waktu_mulai: form.waktu_mulai,
                    waktu_selesai: form.waktu_selesai,
                    ada_delay: form.ada_delay ? 1 : 0,
                    ada_error: form.ada_error ? 1 : 0,
                    ada_komplain: form.ada_komplain ? 1 : 0,
                    deskripsi: form.deskripsi,
                }
                : {
                    judul: form.judul,
                    tanggal: form.start_date,
                    end_date: form.end_date || '',
                    jenis_pekerjaan: form.jenis_pekerjaan,
                    status: form.status,
                    waktu_mulai: form.waktu_mulai,
                    waktu_selesai: form.waktu_selesai,
                    ada_delay: form.ada_delay ? 1 : 0,
                    ada_error: form.ada_error ? 1 : 0,
                    ada_komplain: form.ada_komplain ? 1 : 0,
                    deskripsi: form.deskripsi,
                    kpi_indicator_id: form.kpi_indicator_id || '',
                };
            await taskStore.updateTask(editId.value, buildPayload(base));
            toast.success('Pekerjaan berhasil diperbarui.');
        } else {
            const base = {
                judul: form.judul,
                tanggal: form.start_date,
                end_date: form.end_date || '',
                jenis_pekerjaan: form.jenis_pekerjaan,
                status: form.status,
                waktu_mulai: form.waktu_mulai,
                waktu_selesai: form.waktu_selesai,
                ada_delay: form.ada_delay ? 1 : 0,
                ada_error: form.ada_error ? 1 : 0,
                ada_komplain: form.ada_komplain ? 1 : 0,
                deskripsi: form.deskripsi,
                kpi_indicator_id: form.kpi_indicator_id || '',
            };
            await taskStore.createTask(buildPayload(base));
            toast.success('Pekerjaan berhasil disimpan.');
        }

        showForm.value = false;
        await taskStore.fetchTasks();
    } catch (error) {
        formError.value = error.userMessage || 'Gagal menyimpan pekerjaan.';
    } finally {
        formLoading.value = false;
    }
}

function cancelForm() {
    showForm.value = false;
    formError.value = '';
    editTaskType.value = null;
    evidenceFileName.value = '';
}

function openDeleteDialog(task) {
    deleteDialog.value = { open: true, taskId: task.id, taskTitle: task.judul };
}

async function confirmDelete() {
    deleteLoading.value = true;

    try {
        await taskStore.deleteTask(deleteDialog.value.taskId);
        toast.success('Pekerjaan berhasil dihapus.');
        deleteDialog.value.open = false;
    } catch (error) {
        toast.error(error.userMessage || 'Gagal menghapus pekerjaan.');
    } finally {
        deleteLoading.value = false;
    }
}

async function changePage(page) {
    taskStore.setPage(page);
    await taskStore.fetchTasks();
}

function formatDate(value) {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateRange(task) {
    const start = task.start_date || task.tanggal;
    const end = task.end_date || task.tanggal;

    if (!start && !end) return '-';
    if (start === end) return formatDate(start);

    return `${formatDate(start)} - ${formatDate(end)}`;
}

function resolveProgress(task) {
    const status = task.status_code || task.status;

    if (status === 'done' || status === 'Selesai') return 100;
    if (status === 'on_progress' || status === 'Dalam Proses') return 50;
    return 0;
}

function progressTone(task) {
    const progress = resolveProgress(task);

    if (progress >= 100) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-blue-500';
    return 'bg-amber-400';
}

const statusBadgeMap = {
    Selesai: 'badge-success',
    'Dalam Proses': 'badge-info',
    Pending: 'badge-warning',
};
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Input Pekerjaan
            </button>
        </template>

        <section class="page-hero">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl space-y-3">
                    <div class="page-hero-meta">Task Workspace</div>
                    <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">Kelola pekerjaan harian dengan UI yang lebih modern</h1>
                    <p class="text-sm leading-6 text-white/75">
                        Pantau task, status progres, dan indikator masalah dalam workspace yang lebih rapi, lapang, dan mudah dibaca.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="lastUpdated" class="text-xs text-white/60">{{ formatTime(lastUpdated) }}</span>
                    <button class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15" :class="{ 'opacity-70': isRefreshing }" @click="refreshTasks">
                        <svg class="h-4 w-4" :class="{ 'animate-spin': isRefreshing }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6" />
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Total Pekerjaan</p>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <BarChart2 class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-slate-100">{{ taskSummary.total }}</p>
                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Semua task yang tercatat pada periode aktif.</p>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900/40 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Selesai</p>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <CheckCircle2 class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-emerald-600 dark:text-emerald-400">{{ taskSummary.done }}</p>
                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Task yang telah dituntaskan penuh.</p>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-blue-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-blue-900/40 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Dalam Proses</p>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <Loader2 class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-blue-600 dark:text-blue-400">{{ taskSummary.progress }}</p>
                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Task yang masih berjalan saat ini.</p>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-rose-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-rose-900/40 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Perlu Perhatian</p>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                        <AlertTriangle class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-rose-600 dark:text-rose-400">{{ taskSummary.flagged }}</p>
                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Task dengan delay, error, atau komplain.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Task Timeline</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Daftar pekerjaan & progres terbaru</h2>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Status, rentang tanggal, dan progress setiap task.</p>
                </div>
                <div v-if="lastPage > 1" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                    {{ currentPage }} / {{ lastPage }}
                </div>
            </div>
            <div class="p-6 space-y-6">

            <template v-if="taskStore.isLoading">
                <div class="space-y-4">
                    <Skeleton v-for="i in 4" :key="i" class="h-40 rounded-2xl" />
                </div>
            </template>

            <template v-else-if="tasks.length">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <article
                        v-for="task in tasks"
                        :key="task.id"
                        class="rounded-2xl border bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md space-y-4"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="statusBadgeMap[task.status] || 'badge-neutral'">{{ task.status }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                        {{ task.jenis_pekerjaan || 'Pekerjaan' }}
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <h3 class="text-lg font-semibold text-slate-950">{{ task.judul }}</h3>
                                    <p class="text-sm leading-6 text-muted-foreground">
                                        {{ task.deskripsi || 'Belum ada deskripsi tambahan untuk task ini.' }}
                                    </p>
                                    <!-- KPI Indicator chip -->
                                    <div v-if="task.kpi_indicator" class="flex items-center gap-1.5 text-xs text-blue-700">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                        <span class="font-medium">{{ task.kpi_indicator.name }}</span>
                                        <span v-if="task.kpi_indicator.position" class="text-blue-400">({{ task.kpi_indicator.position.nama }})</span>
                                    </div>
                                    <!-- Evidence chip -->
                                    <div v-if="task.file_evidence_url" class="flex items-center gap-1.5 text-xs text-emerald-700">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <a :href="task.file_evidence_url" target="_blank" rel="noreferrer" class="underline hover:no-underline">Lihat evidence</a>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-muted/40 px-4 py-3 text-sm text-muted-foreground sm:min-w-[200px]">
                                <p class="font-medium text-slate-800">Tanggal</p>
                                <p class="mt-1">{{ formatDateRange(task) }}</p>
                                <p class="mt-3 font-medium text-slate-800">Waktu</p>
                                <p class="mt-1">{{ task.waktu_mulai || '-' }} - {{ task.waktu_selesai || '-' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-[1.2fr_0.8fr]">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">Progress Task</span>
                                    <span class="font-medium text-slate-900">{{ resolveProgress(task) }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full transition-all duration-300" :class="progressTone(task)" :style="{ width: `${resolveProgress(task)}%` }" />
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="task.ada_delay" class="badge-danger">Delay</span>
                                    <span v-if="task.ada_error" class="badge-danger">Error</span>
                                    <span v-if="task.ada_komplain" class="badge-warning">Komplain</span>
                                    <span v-if="!task.ada_delay && !task.ada_error && !task.ada_komplain" class="text-sm text-muted-foreground">
                                        Tidak ada indikator masalah.
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-slate-50/70 p-4 space-y-3">
                                <p class="text-sm font-medium text-slate-900">Aksi Cepat</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="btn-secondary flex-1 justify-center sm:flex-none" @click="openEdit(task)">Edit</button>
                                    <button
                                        v-if="task.task_type !== 'manual_assignment'"
                                        type="button"
                                        class="btn-danger flex-1 justify-center sm:flex-none"
                                        @click="openDeleteDialog(task)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-if="lastPage > 1" class="flex items-center justify-center gap-3 border-t pt-6">
                    <button class="btn-secondary" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">Sebelumnya</button>
                    <span class="text-sm font-medium text-muted-foreground">{{ currentPage }} / {{ lastPage }}</span>
                    <button class="btn-secondary" :disabled="currentPage === lastPage" @click="changePage(currentPage + 1)">Berikutnya</button>
                </div>
            </template>

            <div v-else class="rounded-2xl border border-dashed bg-muted/30 px-6 py-16 text-center space-y-3">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm">
                    <svg class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">Belum ada pekerjaan di periode ini</h3>
                <p class="mx-auto max-w-md text-sm leading-6 text-muted-foreground">
                    Mulai catat aktivitas harian Anda agar dashboard KPI dan task system memiliki data yang lengkap dan mudah direview.
                </p>
                <div>
                    <button class="btn-primary" @click="openCreate">Input Pekerjaan Pertama</button>
                </div>
            </div>
            </div>
        </section>

        <Dialog
            v-model:open="showForm"
            :title="editMode ? 'Edit Pekerjaan' : 'Input Pekerjaan Baru'"
            class="w-full max-w-lg sm:max-w-2xl"
        >
            <!-- Scrollable body -->
            <div class="max-h-[70vh] overflow-y-auto pr-1">
                <div v-if="formError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ formError }}
                </div>

                <p v-if="isAssignedTaskEdit" class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    Task dari HR — hanya status, waktu, indikator masalah, dan deskripsi yang bisa diubah.
                </p>

                <div class="space-y-4">
                    <!-- Judul -->
                    <div>
                        <label class="form-label">Judul Pekerjaan</label>
                        <input
                            v-model="form.judul"
                            type="text"
                            class="form-input"
                            placeholder="Contoh: Pembuatan laporan bulanan"
                            :disabled="isAssignedTaskEdit"
                        />
                        <p v-if="formErrors.judul" class="mt-1 text-xs text-red-500">{{ formErrors.judul }}</p>
                    </div>

                    <!-- Tanggal Mulai & Selesai -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                            <input v-model="form.start_date" type="date" class="form-input" :disabled="isAssignedTaskEdit" />
                            <p v-if="formErrors.start_date" class="mt-1 text-xs text-red-500">{{ formErrors.start_date }}</p>
                        </div>
                        <div>
                            <label class="form-label">Tanggal Selesai</label>
                            <input v-model="form.end_date" type="date" class="form-input" :min="form.start_date || undefined" :disabled="isAssignedTaskEdit" />
                            <p v-if="formErrors.end_date" class="mt-1 text-xs text-red-500">{{ formErrors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Jenis & Indikator KPI -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Jenis Pekerjaan</label>
                            <select v-model="form.jenis_pekerjaan" class="form-input" :disabled="isAssignedTaskEdit">
                                <option value="">Pilih jenis...</option>
                                <option v-for="opt in jobTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p v-if="formErrors.jenis_pekerjaan" class="mt-1 text-xs text-red-500">{{ formErrors.jenis_pekerjaan }}</p>
                        </div>
                        <div>
                            <label class="form-label">Indikator KPI</label>
                            <select v-model="form.kpi_indicator_id" class="form-input" :disabled="isAssignedTaskEdit">
                                <option value="">— Tanpa indikator KPI —</option>
                                <option v-for="opt in indicatorOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p class="mt-1 text-[11px] text-slate-400">Hubungkan ke indikator KPI divisi Anda.</p>
                        </div>
                    </div>

                    <!-- Status & Waktu -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-input">
                                <option value="">Pilih status...</option>
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="formErrors.status" class="mt-1 text-xs text-red-500">{{ formErrors.status }}</p>
                        </div>
                        <div>
                            <label class="form-label">Waktu Mulai</label>
                            <input v-model="form.waktu_mulai" type="time" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Waktu Selesai</label>
                            <input v-model="form.waktu_selesai" type="time" class="form-input" />
                            <p v-if="formErrors.waktu_selesai" class="mt-1 text-xs text-red-500">{{ formErrors.waktu_selesai }}</p>
                        </div>
                    </div>

                    <!-- Indikator Masalah -->
                    <div class="border-t pt-4">
                        <label class="form-label mb-2 block">Indikator Masalah</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <label
                                v-for="flag in [
                                    { key: 'ada_delay', label: 'Ada Delay' },
                                    { key: 'ada_error', label: 'Ada Error' },
                                    { key: 'ada_komplain', label: 'Ada Komplain' },
                                ]"
                                :key="flag.key"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors"
                                :class="form[flag.key]
                                    ? 'border-red-300 bg-red-50 text-red-700'
                                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                            >
                                <input type="checkbox" v-model="form[flag.key]" class="h-4 w-4 rounded border-slate-300" />
                                {{ flag.label }}
                            </label>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="form-label">Deskripsi</label>
                        <textarea
                            v-model="form.deskripsi"
                            class="form-textarea"
                            placeholder="Keterangan tambahan..."
                            rows="3"
                        />
                    </div>

                    <!-- Upload Evidence -->
                    <div>
                        <label class="form-label mb-2 block">Upload Evidence</label>
                        <label
                            class="group flex cursor-pointer items-center gap-4 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/80 px-4 py-4 transition hover:border-blue-300 hover:bg-blue-50/50"
                            :class="isAssignedTaskEdit ? 'opacity-50 pointer-events-none' : ''"
                        >
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm transition group-hover:bg-blue-50">
                                <svg class="h-4 w-4 text-slate-400 transition group-hover:text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-700">
                                    {{ evidenceFileName || 'Klik untuk upload evidence' }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-400">PDF, PNG, JPG, DOC, XLSX — maks 10 MB</p>
                            </div>
                            <input
                                type="file"
                                accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xlsx"
                                class="sr-only"
                                :disabled="isAssignedTaskEdit"
                                @change="onEvidenceChange"
                            >
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer buttons -->
            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                <button class="btn-secondary w-full sm:w-auto" :disabled="formLoading" @click="cancelForm">Batal</button>
                <button class="btn-primary w-full sm:w-auto" :disabled="formLoading" @click="submitForm">
                    <svg v-if="formLoading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83" />
                    </svg>
                    {{ formLoading ? 'Menyimpan...' : editMode ? 'Perbarui Pekerjaan' : 'Simpan Pekerjaan' }}
                </button>
            </div>
        </Dialog>

        <Dialog
            v-model:open="deleteDialog.open"
            title="Hapus Pekerjaan"
            :description="`Apakah kamu yakin ingin menghapus &quot;${deleteDialog.taskTitle}&quot;? Aksi ini tidak dapat dibatalkan.`"
        >
            <div class="mt-4 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="deleteLoading" @click="deleteDialog.open = false">Batal</button>
                <button class="btn-danger" :disabled="deleteLoading" @click="confirmDelete">
                    {{ deleteLoading ? 'Menghapus...' : 'Ya, Hapus' }}
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>
