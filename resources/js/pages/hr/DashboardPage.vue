<script setup>
import { computed, onMounted } from 'vue';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiStore } from '@/stores/kpi';
import { useTaskStore } from '@/stores/task';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import StatCard from '@/components/shared/StatCard.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Select from '@/components/ui/Select.vue';
import Input from '@/components/ui/Input.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import api from '@/services/api';

const empStore = useEmployeeStore();
const kpiStore = useKpiStore();
const taskStore = useTaskStore();
const toast = useToast();

const kpiComponents = computed(() => componentState.items);
const unmappedTasks = computed(() => componentState.unmappedTasks);
const topRanking = computed(() => kpiStore.ranking.slice(0, 8));

const componentState = {
    items: [],
    unmappedTasks: [],
    loadingComponents: false,
    loadingUnmapped: false,
};

const stats = computed(() => {
    const ranking = kpiStore.ranking;
    const average = ranking.length
        ? Math.round((ranking.reduce((sum, item) => sum + item.kpi_score, 0) / ranking.length) * 10) / 10
        : 0;

    return {
        totalEmployees: empStore.total,
        avgScore: average,
        unmapped: componentState.unmappedTasks.length,
        lowScore: ranking.filter((item) => item.kpi_score < 3).length,
    };
});

const mappingDialog = {
    open: false,
    task: null,
    kpiComponentId: '',
    manualScore: '',
    loading: false,
    error: '',
};

const componentOptions = computed(() =>
    kpiComponents.value.map((item) => ({
        value: String(item.id),
        label: `${item.objectives} (${item.jabatan})`,
    })),
);

onMounted(async () => {
    await Promise.all([
        empStore.fetchEmployees(),
        kpiStore.fetchRanking(),
        loadComponents(),
        loadUnmappedTasks(),
    ]);
});

async function loadComponents() {
    componentState.loadingComponents = true;
    try {
        const { data: response } = await api.get('/kpi-components', { params: { per_page: 100 } });
        componentState.items = response.data?.items || [];
    } finally {
        componentState.loadingComponents = false;
    }
}

async function loadUnmappedTasks() {
    componentState.loadingUnmapped = true;
    try {
        const today = new Date();
        const { data: response } = await api.get('/tasks', {
            params: {
                bulan: today.getMonth() + 1,
                tahun: today.getFullYear(),
                per_page: 200,
            },
        });

        componentState.unmappedTasks = (response.data?.items || []).filter((item) => !item.kpi_component);
    } finally {
        componentState.loadingUnmapped = false;
    }
}

function openMapping(task) {
    mappingDialog.task = task;
    mappingDialog.kpiComponentId = '';
    mappingDialog.manualScore = '';
    mappingDialog.error = '';
    mappingDialog.open = true;
}

async function submitMapping() {
    if (!mappingDialog.kpiComponentId) {
        mappingDialog.error = 'Pilih komponen KPI terlebih dahulu.';
        return;
    }

    mappingDialog.loading = true;
    mappingDialog.error = '';

    try {
        await taskStore.mapKpi(mappingDialog.task.id, {
            kpi_component_id: Number(mappingDialog.kpiComponentId),
            manual_score: mappingDialog.manualScore ? Number(mappingDialog.manualScore) : null,
        });

        toast.success('Mapping KPI berhasil disimpan.');
        mappingDialog.open = false;
        await Promise.all([loadUnmappedTasks(), kpiStore.fetchRanking()]);
    } catch (error) {
        mappingDialog.error = error.userMessage || 'Gagal menyimpan mapping KPI.';
    } finally {
        mappingDialog.loading = false;
    }
}

function formatDate(value) {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR Dashboard</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Monitoring KPI dan progres operasional</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Kelola data pegawai, mapping KPI, dan kualitas penyelesaian pekerjaan dari satu dashboard yang lebih rapi dan formal.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[360px]">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Total Pegawai</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ stats.totalEmployees }}</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Rata-rata KPI</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ stats.avgScore }}</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <StatCard label="Total Pegawai" :value="stats.totalEmployees" />
            <StatCard label="Rata-rata KPI" :value="stats.avgScore" :color="stats.avgScore >= 4 ? 'success' : stats.avgScore >= 3 ? 'warning' : 'danger'" />
            <StatCard label="Belum Di-map" :value="stats.unmapped" :color="stats.unmapped > 0 ? 'warning' : 'success'" />
            <StatCard label="Nilai Rendah" :value="stats.lowScore" :color="stats.lowScore > 0 ? 'danger' : 'default'" />
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="section-heading">Prioritas HR</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">Tugas yang belum dihubungkan ke KPI</h3>
                    <p class="mt-1 text-sm text-slate-500">Segera mapping agar scoring bulanan dan ranking pegawai tetap akurat.</p>
                </div>

                <div class="p-6">
                    <template v-if="componentState.loadingUnmapped">
                        <div class="space-y-3">
                            <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                        </div>
                    </template>
                    <template v-else-if="unmappedTasks.length">
                        <div class="space-y-3">
                            <div v-for="task in unmappedTasks" :key="task.id" class="data-row">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <Avatar :name="task.user?.nama || '?'" size="sm" />
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-900">{{ task.judul }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ task.user?.nama || '-' }} · {{ formatDate(task.tanggal) }}
                                        </div>
                                    </div>
                                </div>
                                <button class="btn-primary" @click="openMapping(task)">Map KPI</button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="py-12 text-center text-sm text-slate-400">
                        Semua pekerjaan bulan ini sudah memiliki mapping KPI.
                    </div>
                </div>
            </section>

            <section class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="section-heading">Master KPI</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">Komponen aktif</h3>
                </div>

                <div class="p-6">
                    <template v-if="componentState.loadingComponents">
                        <div class="space-y-3">
                            <Skeleton v-for="i in 4" :key="i" class="h-14 rounded-2xl" />
                        </div>
                    </template>
                    <template v-else-if="kpiComponents.length">
                        <div class="space-y-3">
                            <div v-for="item in kpiComponents.slice(0, 6)" :key="item.id" class="data-row">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ item.objectives }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ item.jabatan }} · {{ item.tipe }} · Bobot {{ item.bobot }}
                                    </div>
                                </div>
                                <span :class="item.is_active ? 'badge-success' : 'badge-neutral'">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <section class="dashboard-panel mt-5 overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Peringkat</p>
                <h3 class="mt-2 text-xl font-bold text-slate-900">Ranking KPI seluruh pegawai</h3>
            </div>

            <div class="p-6">
                <template v-if="kpiStore.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 8" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>
                <template v-else-if="topRanking.length">
                    <div class="space-y-3">
                        <div v-for="item in topRanking" :key="item.user_id" class="data-row">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">
                                    #{{ item.rank }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ item.position }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-base font-bold text-slate-900">{{ item.kpi_score }}</div>
                                <div class="text-[11px] text-slate-500">nilai KPI</div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="py-12 text-center text-sm text-slate-400">
                    Data ranking belum tersedia.
                </div>
            </div>
        </section>

        <Dialog v-model:open="mappingDialog.open" title="Mapping KPI" class="max-w-lg">
            <template v-if="mappingDialog.task">
                <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <div class="font-semibold text-slate-900">{{ mappingDialog.task.judul }}</div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ mappingDialog.task.user?.nama || '-' }} · {{ formatDate(mappingDialog.task.tanggal) }}
                    </div>
                </div>

                <Alert v-if="mappingDialog.error" variant="danger" class="mb-4">{{ mappingDialog.error }}</Alert>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Komponen KPI</label>
                        <Select v-model="mappingDialog.kpiComponentId" :options="componentOptions" placeholder="Pilih komponen KPI" />
                    </div>
                    <div>
                        <label class="form-label">Skor Manual</label>
                        <Input v-model="mappingDialog.manualScore" type="number" min="0" step="0.01" placeholder="Opsional" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button class="btn-secondary" :disabled="mappingDialog.loading" @click="mappingDialog.open = false">Batal</button>
                    <button class="btn-primary" :disabled="mappingDialog.loading" @click="submitMapping">
                        {{ mappingDialog.loading ? 'Menyimpan...' : 'Simpan Mapping' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
