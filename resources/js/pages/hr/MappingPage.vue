<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useTaskStore } from '@/stores/task';
import { useToast } from '@/composables/useToast';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import api from '@/services/api';

const taskStore = useTaskStore();
const toast     = useToast();

// ── Filters ───────────────────────────────────────────────────────────────────
const filterBulan    = ref(new Date().getMonth() + 1);
const filterTahun    = ref(new Date().getFullYear());
const filterUnmapped = ref(true);
const filterSearch   = ref('');

const months = [
    { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },   { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },     { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },{ value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },{ value: 12, label: 'Desember' },
];
const years = computed(() => {
    const y = new Date().getFullYear();
    return [y - 1, y, y + 1];
});

// ── KPI indicators list (for dropdown) ───────────────────────────────────────
const kpiIndicators    = ref([]);
const loadingIndicators = ref(false);

async function loadKpiIndicators() {
    loadingIndicators.value = true;
    try {
        const { data: resp } = await api.get('/kpi-indicators', { params: { per_page: 200 } });
        kpiIndicators.value = resp.data?.items ?? [];
    } finally {
        loadingIndicators.value = false;
    }
}

// ── Tasks ─────────────────────────────────────────────────────────────────────
function loadTasks() {
    taskStore.fetchTasks({
        bulan:    filterBulan.value,
        tahun:    filterTahun.value,
        per_page: 200,
    });
}

onMounted(() => {
    loadTasks();
    loadKpiIndicators();
});

watch([filterBulan, filterTahun], loadTasks);

const { refresh: autoRefresh, lastUpdated, isRefreshing } = useAutoRefresh(loadTasks, { interval: 30_000 });

// ── Derived/filtered list ─────────────────────────────────────────────────────
const displayedTasks = computed(() => {
    let list = taskStore.tasks;
    if (filterUnmapped.value) list = list.filter(t => !t.kpi_indicator);
    if (filterSearch.value.trim()) {
        const q = filterSearch.value.toLowerCase();
        list = list.filter(t =>
            t.judul?.toLowerCase().includes(q) ||
            t.user?.nama?.toLowerCase().includes(q)
        );
    }
    return list;
});

const unmappedCount = computed(() => taskStore.tasks.filter(t => !t.kpi_indicator).length);
const mappedCount   = computed(() => taskStore.tasks.filter(t =>  t.kpi_indicator).length);

// ── Mapping dialog ────────────────────────────────────────────────────────────
const mappingDialog = reactive({
    open:            false,
    task:            null,
    kpiIndicatorId:  '',
    manualScore:     '',
    loading:         false,
    error:           '',
});

function openMapping(task) {
    mappingDialog.task           = task;
    mappingDialog.kpiIndicatorId = task.kpi_indicator_id ? String(task.kpi_indicator_id) : '';
    mappingDialog.manualScore    = task.manual_score ?? '';
    mappingDialog.error          = '';
    mappingDialog.open           = true;
}

async function submitMapping() {
    if (!mappingDialog.kpiIndicatorId) {
        mappingDialog.error = 'Pilih indikator KPI terlebih dahulu.';
        return;
    }
    mappingDialog.loading = true;
    mappingDialog.error   = '';
    try {
        const updated = await taskStore.mapKpi(mappingDialog.task.id, {
            kpi_indicator_id: Number(mappingDialog.kpiIndicatorId),
            manual_score: mappingDialog.manualScore !== '' ? Number(mappingDialog.manualScore) : null,
        });

        // Update task in store list immediately (realtime)
        const idx = taskStore.tasks.findIndex(t => t.id === mappingDialog.task.id);
        if (idx !== -1) taskStore.tasks[idx] = { ...taskStore.tasks[idx], ...updated };

        toast.success('Mapping KPI berhasil disimpan.');
        mappingDialog.open = false;
    } catch (err) {
        mappingDialog.error = err.response?.data?.message || 'Gagal menyimpan mapping KPI.';
    } finally {
        mappingDialog.loading = false;
    }
}

function removeMapping(task) {
    mappingDialog.task           = task;
    mappingDialog.kpiIndicatorId = '';
    mappingDialog.manualScore    = '';
    mappingDialog.error          = '';
    mappingDialog.open           = true;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const indicatorOptions = computed(() =>
    kpiIndicators.value.map(c => ({
        value: String(c.id),
        label: c.position ? `${c.name} (${c.position.nama})` : c.name,
    }))
);

function formatDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function statusClass(status) {
    return { 'Selesai': 'badge-success', 'Dalam Proses': 'badge-info', 'Pending': 'badge-warning' }[status] || 'badge-neutral';
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR Monitoring</div>
                    <h2 class="mt-4 text-2xl font-bold md:text-3xl">Mapping Pekerjaan ke KPI</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Hubungkan setiap pekerjaan ke komponen KPI agar scoring dan ranking pegawai terbaca akurat.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:min-w-[320px]">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Belum Di-map</div>
                        <div class="mt-2 text-2xl font-bold" :class="unmappedCount > 0 ? 'text-amber-300' : 'text-white'">
                            {{ unmappedCount }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Sudah Di-map</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ mappedCount }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <input
                v-model="filterSearch"
                type="text"
                placeholder="Cari pegawai atau judul..."
                class="form-input !w-auto min-w-[200px]"
            />

            <select v-model="filterBulan" class="form-input !w-auto">
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>

            <select v-model="filterTahun" class="form-input !w-auto">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>

            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                <input v-model="filterUnmapped" type="checkbox" class="rounded" />
                Tampilkan belum di-map saja
            </label>
        </div>

        <!-- Table panel -->
        <section class="dashboard-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-base font-bold text-slate-900">
                    Pekerjaan Pegawai
                    <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {{ displayedTasks.length }}
                    </span>
                </h3>
                <div class="flex items-center gap-2">
                    <span v-if="lastUpdated" class="text-xs text-slate-400">{{ formatTime(lastUpdated) }}</span>
                    <button class="btn-secondary text-xs" :class="{ 'opacity-60': isRefreshing }" @click="autoRefresh">
                        <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': isRefreshing }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <template v-if="taskStore.isLoading">
                <div class="space-y-3 p-6">
                    <Skeleton v-for="i in 8" :key="i" class="h-16 rounded-2xl" />
                </div>
            </template>

            <div v-else-if="!displayedTasks.length" class="py-16 text-center text-sm text-slate-400">
                {{ filterUnmapped ? 'Semua pekerjaan bulan ini sudah di-map.' : 'Tidak ada data pekerjaan.' }}
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="task in displayedTasks"
                    :key="task.id"
                    class="flex flex-col gap-3 px-6 py-4 transition-colors hover:bg-slate-50 sm:flex-row sm:items-center"
                >
                    <!-- Task info -->
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <Avatar :name="task.user?.nama || '?'" size="sm" class="mt-0.5 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ task.judul }}</span>
                                <span :class="statusClass(task.status)" class="shrink-0">{{ task.status }}</span>
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ task.user?.nama || '-' }}
                                <span class="mx-1 text-slate-300">·</span>
                                {{ formatDate(task.tanggal) }}
                                <span v-if="task.deskripsi" class="mx-1 text-slate-300">·</span>
                                <span v-if="task.deskripsi" class="max-w-[260px] truncate text-slate-400">{{ task.deskripsi }}</span>
                            </div>

                            <!-- Mapped KPI chip -->
                            <div v-if="task.kpi_indicator" class="mt-1.5 flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 shrink-0 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                                <span class="text-xs font-medium text-green-700">{{ task.kpi_indicator.name }}</span>
                                <span v-if="task.kpi_indicator.position" class="text-xs text-slate-400">({{ task.kpi_indicator.position.nama }})</span>
                            </div>
                            <div v-else class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-600">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                Belum di-map ke indikator KPI
                            </div>
                        </div>
                    </div>

                    <!-- Action -->
                    <button
                        class="btn-primary shrink-0 !px-3 !py-1.5 text-xs"
                        :class="task.kpi_indicator ? 'btn-secondary' : 'btn-primary'"
                        @click="openMapping(task)"
                    >
                        {{ task.kpi_indicator ? 'Ubah Mapping' : 'Map KPI' }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Mapping Dialog -->
        <Dialog v-model:open="mappingDialog.open" title="Mapping Pekerjaan ke KPI" class="max-w-lg">
            <template v-if="mappingDialog.task">
                <!-- Task info box -->
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ mappingDialog.task.judul }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ mappingDialog.task.user?.nama || '-' }}
                        · {{ formatDate(mappingDialog.task.tanggal) }}
                    </p>
                    <div v-if="mappingDialog.task.kpi_indicator" class="mt-2 rounded-lg bg-green-50 px-3 py-1.5 text-xs text-green-700">
                        Saat ini: <strong>{{ mappingDialog.task.kpi_indicator.name }}</strong>
                    </div>
                </div>

                <Alert v-if="mappingDialog.error" variant="danger" class="mt-3">{{ mappingDialog.error }}</Alert>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="form-label">Indikator KPI <span class="text-red-500">*</span></label>
                        <select v-model="mappingDialog.kpiIndicatorId" class="form-input">
                            <option value="">— Pilih Indikator KPI —</option>
                            <option v-for="opt in indicatorOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="loadingIndicators" class="mt-1 text-xs text-slate-400">Memuat indikator...</p>
                    </div>
                    <div>
                        <label class="form-label">
                            Skor Manual
                            <span class="text-xs text-slate-400">(opsional, 0–5)</span>
                        </label>
                        <input
                            v-model="mappingDialog.manualScore"
                            type="number" min="0" max="5" step="0.01"
                            class="form-input"
                            placeholder="Biarkan kosong untuk auto-hitung"
                        />
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button class="btn-secondary" :disabled="mappingDialog.loading" @click="mappingDialog.open = false">
                        Batal
                    </button>
                    <button class="btn-primary" :disabled="mappingDialog.loading" @click="submitMapping">
                        {{ mappingDialog.loading ? 'Menyimpan...' : 'Simpan Mapping' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
