<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiStore } from '@/stores/kpi';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import { useKpiColor } from '@/composables/useKpiColor';
import AppLayout from '@/components/layout/AppLayout.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import FilterPanel from '@/components/shared/FilterPanel.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import { downloadFile } from '@/services/api';

const empStore = useEmployeeStore();
const kpiStore = useKpiStore();
const { getPredikat } = useKpiColor();

// ── Filters ───────────────────────────────────────────────────────────────────
const selectedUserId = ref('');
const filterBulan    = ref(new Date().getMonth() + 1);
const filterTahun    = ref(new Date().getFullYear());

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

// ── Data ──────────────────────────────────────────────────────────────────────
const isLoadingKpi = ref(false);
const kpiData      = ref(null);

async function loadKpi() {
    if (!selectedUserId.value) { kpiData.value = null; return; }
    isLoadingKpi.value = true;
    try {
        const data = await kpiStore.fetchUserKpi(
            selectedUserId.value,
        );
        // The API call in the store doesn't pass bulan/tahun – call directly
        const { data: resp } = await import('@/services/api').then(m =>
            m.default.get(`/kpi/${selectedUserId.value}`, {
                params: { bulan: filterBulan.value, tahun: filterTahun.value },
            })
        );
        kpiData.value = resp.data;
    } finally {
        isLoadingKpi.value = false;
    }
}

onMounted(async () => {
    await empStore.fetchEmployees();
    if (empStore.employees.length) {
        selectedUserId.value = String(empStore.employees[0].id);
    }
});

watch([selectedUserId, filterBulan, filterTahun], loadKpi);

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(loadKpi, { interval: 30_000 });

// ── Computed ──────────────────────────────────────────────────────────────────
const selectedEmployee = computed(() =>
    empStore.employees.find(e => String(e.id) === String(selectedUserId.value))
);

const predikat = computed(() => {
    const total = kpiData.value?.total ?? 0;
    return getPredikat(total);
});

const scoreColor = (score) => {
    if (score >= 4) return 'text-green-600';
    if (score >= 3) return 'text-blue-600';
    if (score >= 2) return 'text-amber-600';
    return 'text-red-600';
};

const barColor = (score) => {
    if (score >= 4) return 'bg-green-500';
    if (score >= 3) return 'bg-blue-500';
    if (score >= 2) return 'bg-amber-500';
    return 'bg-red-500';
};

function monthLabel(m) {
    return months.find(x => x.value === m)?.label ?? m;
}

async function exportPdf() {
    if (!selectedUserId.value) return;
    await downloadFile(`/export/kpi/${selectedUserId.value}/pdf`, {
        params: { bulan: filterBulan.value, tahun: filterTahun.value },
        fallbackFilename: `kpi-${selectedUserId.value}-${filterTahun.value}-${String(filterBulan.value).padStart(2, '0')}.pdf`,
    });
}
</script>

<template>
    <AppLayout>
        <PageHeader
            eyebrow="HR - Detail KPI"
            title="Detail KPI Pegawai"
            description="Tinjau profil, skor, breakdown indikator, dan performa pegawai per periode."
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <span v-if="lastUpdated" class="text-[11px] text-white/50">{{ formatTime(lastUpdated) }}</span>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white/70 transition hover:bg-white/20"
                        :class="{ 'animate-spin': isRefreshing }"
                        @click="refresh"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                        </svg>
                    </button>
                </div>
            </template>
        </PageHeader>

        <FilterPanel
            title="Filter detail pegawai"
            description="Pilih pegawai dan periode agar laporan KPI yang ditampilkan lebih spesifik."
            :result-text="selectedEmployee ? `${selectedEmployee.nama} - ${monthLabel(filterBulan)} ${filterTahun}` : 'Pilih pegawai'"
        >
            <div class="space-y-2 xl:col-span-2">
                <label class="form-label">Pegawai</label>
                <select v-model="selectedUserId" class="form-input">
                <option value="">— Pilih Pegawai —</option>
                <option v-for="emp in empStore.employees" :key="emp.id" :value="String(emp.id)">
                    {{ emp.nama }} ({{ emp.jabatan || emp.role }})
                </option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="form-label">Bulan</label>
                <select v-model="filterBulan" class="form-input">
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="form-label">Tahun</label>
                <select v-model="filterTahun" class="form-input">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <button
                v-if="selectedUserId"
                class="btn-secondary self-end text-xs"
                @click="exportPdf"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                </svg>
                Export PDF
            </button>
        </FilterPanel>

        <!-- Empty state -->
        <EmptyState
            v-if="!selectedUserId"
            title="Pilih pegawai terlebih dahulu"
            description="Gunakan filter pegawai untuk melihat profil KPI, skor total, predikat, dan breakdown indikator."
        />

        <template v-else>
            <!-- Loading -->
            <template v-if="isLoadingKpi">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <Skeleton class="h-36 rounded-2xl lg:col-span-1" />
                    <Skeleton class="h-36 rounded-2xl lg:col-span-2" />
                </div>
                <Skeleton class="mt-5 h-64 rounded-2xl" />
            </template>

            <template v-else-if="kpiData">
                <!-- Summary header -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[auto_1fr]">
                    <!-- Employee card -->
                    <div class="dashboard-panel flex items-center gap-4 p-6 lg:min-w-[260px]">
                        <Avatar :name="selectedEmployee?.nama || '?'" size="lg" />
                        <div>
                            <p class="text-lg font-bold text-slate-900">{{ kpiData.user?.nama || selectedEmployee?.nama }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">{{ kpiData.user?.jabatan || '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ kpiData.user?.departemen || '-' }}</p>
                            <p class="mt-2 text-xs font-medium text-slate-500">{{ monthLabel(filterBulan) }} {{ filterTahun }}</p>
                        </div>
                    </div>

                    <!-- Score overview -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="dashboard-panel p-5">
                            <p class="text-xs font-medium text-slate-500">Total Skor KPI</p>
                            <p :class="['mt-1 text-3xl font-bold', scoreColor(kpiData.total ?? 0)]">
                                {{ kpiData.total ?? 0 }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">dari 5.00</p>
                        </div>
                        <div class="dashboard-panel p-5">
                            <p class="text-xs font-medium text-slate-500">Predikat</p>
                            <p :class="['mt-1 text-xl font-bold', scoreColor(kpiData.total ?? 0)]">
                                {{ kpiData.predikat ?? '-' }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">penilaian bulan ini</p>
                        </div>
                        <div class="dashboard-panel p-5">
                            <p class="text-xs font-medium text-slate-500">Komponen Aktif</p>
                            <p class="mt-1 text-3xl font-bold text-slate-700">
                                {{ kpiData.components?.length ?? 0 }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">komponen KPI</p>
                        </div>
                        <div class="dashboard-panel p-5">
                            <p class="text-xs font-medium text-slate-500">Total Tugas</p>
                            <p class="mt-1 text-3xl font-bold text-slate-700">
                                {{ kpiData.components?.reduce((s, c) => s + (c.jumlah_task ?? 0), 0) ?? 0 }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">pekerjaan tercatat</p>
                        </div>
                    </div>
                </div>

                <!-- Components breakdown -->
                <div class="dashboard-panel mt-5 overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <p class="section-heading">Breakdown KPI</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Skor per Komponen</h3>
                    </div>
                    <div v-if="!kpiData.components?.length" class="py-12 text-center text-sm text-slate-400">
                        Belum ada komponen KPI yang terhubung ke pegawai ini.
                    </div>
                    <div v-else class="divide-y divide-slate-100">
                        <div
                            v-for="comp in kpiData.components"
                            :key="comp.id ?? comp.objectives"
                            class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ comp.objectives }}</span>
                                    <span class="badge-neutral text-[10px]">{{ comp.tipe }}</span>
                                    <span class="text-xs text-slate-400">Bobot {{ Math.round((comp.bobot ?? 0) * 100) }}%</span>
                                </div>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-slate-200">
                                        <div
                                            :class="['h-2 rounded-full transition-all duration-700', barColor(comp.skor ?? 0)]"
                                            :style="{ width: Math.min(100, ((comp.skor ?? 0) / 5) * 100) + '%' }"
                                        />
                                    </div>
                                    <span :class="['w-8 text-right text-sm font-bold', scoreColor(comp.skor ?? 0)]">
                                        {{ comp.skor ?? 0 }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-slate-400">
                                    {{ comp.jumlah_task ?? 0 }} pekerjaan
                                    <span v-if="comp.keterangan" class="ml-1">· {{ comp.keterangan }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <EmptyState
                v-else
                title="Data KPI gagal dimuat"
                description="Coba pilih ulang pegawai atau refresh halaman untuk memuat data KPI terbaru."
            />
        </template>
    </AppLayout>
</template>
