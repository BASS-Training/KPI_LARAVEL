<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAnalyticsStore } from '@/stores/analytics';
import { useAuthStore } from '@/stores/auth';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import LineChart from '@/components/charts/LineChart.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { downloadFile } from '@/services/api';

const store = useAnalyticsStore();
const authStore = useAuthStore();

const yearOptions = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const selectedYear = ref(new Date().getFullYear());

async function applyFilter() {
    store.setFilter('tahun', selectedYear.value);
    await store.fetchAll();
}

onMounted(() => applyFilter());
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(applyFilter, { interval: 30_000 });

// ── Chart data — all computed so they react to store changes ─────────────────

const trendChart = computed(() => {
    const raw = store.trend;
    if (!raw?.labels?.length) return { labels: [], datasets: [] };
    return {
        labels: raw.labels,
        datasets: (raw.datasets ?? []).map(ds => ({
            label: ds.label,
            data:  ds.data,
            color: ds.type === 'percentage' ? '#3b82f6' : '#10b981',
            fill:  ds.type === 'percentage',
        })),
    };
});

const departmentChart = computed(() => {
    const raw = store.perDepartment;
    if (!raw?.labels?.length) return { labels: [], datasets: [] };
    return {
        labels: raw.labels,
        datasets: (raw.datasets ?? []).map((ds, i) => ({
            label: ds.label,
            data:  ds.data,
            color: ['#6366f1', '#10b981'][i] ?? '#6366f1',
        })),
    };
});

const distributionChart = computed(() => {
    const raw = store.distribution?.report_based;
    if (!raw?.labels?.length) return { labels: [], data: [], colors: [] };
    const colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'];
    return {
        labels: raw.labels,
        data:   raw.data,
        colors: raw.labels.map((_, i) => colors[i] ?? '#94a3b8'),
    };
});

const taskDistChart = computed(() => {
    const raw = store.distribution?.task_based;
    if (!raw?.labels?.length) return { labels: [], data: [], colors: [] };
    const colorMap = {
        'Baik Sekali': '#22c55e', 'Baik': '#3b82f6',
        'Cukup': '#f59e0b', 'Kurang': '#ef4444', 'Buruk': '#dc2626',
    };
    return {
        labels: raw.labels,
        data:   raw.data,
        colors: raw.labels.map(l => colorMap[l] ?? '#94a3b8'),
    };
});

// Chart keys — change when data changes so Chart.js reinitialises
const trendKey        = computed(() => [
    trendChart.value.labels.join(','),
    trendChart.value.datasets.map((ds) => ds.data.join(',')).join('|'),
].join('::'));
const departmentKey     = computed(() => [
    departmentChart.value.labels.join(','),
    departmentChart.value.datasets.map((ds) => ds.data.join(',')).join('|'),
].join('::'));
const distributionKey = computed(() => distributionChart.value.data.join(','));
const taskDistKey     = computed(() => taskDistChart.value.data.join(','));

let realtimeChannelName = null;
let realtimeRefreshTimer = null;

function scheduleRealtimeRefresh() {
    window.clearTimeout(realtimeRefreshTimer);
    realtimeRefreshTimer = window.setTimeout(() => {
        refresh();
    }, 250);
}

function bindRealtimeRefresh() {
    const role = authStore.user?.role;

    if (!window.Echo || !['admin', 'hr_manager', 'direktur'].includes(role)) {
        return;
    }

    realtimeChannelName = `kpi.role.${role}`;
    window.Echo.private(realtimeChannelName)
        .listen('.kpi.updated', scheduleRealtimeRefresh);
}

function unbindRealtimeRefresh() {
    window.clearTimeout(realtimeRefreshTimer);

    if (realtimeChannelName && window.Echo) {
        window.Echo.leave(realtimeChannelName);
    }

    realtimeChannelName = null;
}

onMounted(bindRealtimeRefresh);
onUnmounted(unbindRealtimeRefresh);

async function exportKpiCsv() {
    await downloadFile('/export/reports/csv', {
        params: { tahun: selectedYear.value },
        fallbackFilename: `laporan-kpi-${selectedYear.value}.csv`,
    });
}

async function exportRankingCsv() {
    await downloadFile('/export/ranking/csv', {
        params: { tahun: selectedYear.value },
        fallbackFilename: `ranking-kpi-${selectedYear.value}.csv`,
    });
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">HR Manager</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Analytics KPI</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Visualisasi performa KPI seluruh karyawan — tren bulanan, distribusi nilai, dan perbandingan antar departemen.
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold text-emerald-300">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                            Auto-refresh 30 dtk
                        </span>
                        <span v-if="lastUpdated" class="text-[11px] text-white/45">Diperbarui {{ formatTime(lastUpdated) }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white/70 transition hover:bg-white/20"
                        :class="{ 'animate-spin': isRefreshing }"
                        title="Refresh"
                        @click="refresh"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                        </svg>
                    </button>
                    <select
                        v-model="selectedYear"
                        class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/30"
                        @change="applyFilter"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Overview cards -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <template v-if="store.isLoadingOverview">
                <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-2xl" />
            </template>
            <template v-else>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Total Karyawan</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ store.overview?.total_employees ?? 0 }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ store.overview?.total_departments ?? 0 }} departemen aktif</p>
                </div>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Rata-rata Achievement</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">
                        {{ store.overview?.avg_achievement ? store.overview.avg_achievement + '%' : '-' }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ store.overview?.total_reports ?? 0 }} laporan</p>
                </div>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Performa Baik (≥80%)</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">
                        {{ (store.overview?.excellent_count ?? 0) + (store.overview?.good_count ?? 0) }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400">excellent + good</p>
                </div>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Performa Buruk (&lt;50%)</p>
                    <p class="mt-1 text-2xl font-bold text-red-500">{{ store.overview?.bad_count ?? 0 }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">perlu perhatian</p>
                </div>
            </template>
        </section>

        <!-- Trend + Report Distribution -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="dashboard-panel overflow-hidden lg:col-span-2">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Tren Bulanan</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Achievement Rate {{ selectedYear }}</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.isLoadingTrend" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        <svg class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>
                        </svg>
                        Memuat...
                    </div>
                    <div v-else-if="!trendChart.labels.length" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Belum ada data trend untuk tahun ini.
                    </div>
                    <LineChart
                        v-else
                        :key="trendKey"
                        :labels="trendChart.labels"
                        :datasets="trendChart.datasets"
                        y-label="Nilai"
                        :height="260"
                    />
                </div>
            </div>

            <div class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Distribusi Laporan KPI</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Sebaran Pencapaian</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.isLoadingDistribution" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <div v-else-if="!distributionChart.labels.length" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Belum ada data distribusi.
                    </div>
                    <DoughnutChart
                        v-else
                        :key="distributionKey"
                        :labels="distributionChart.labels"
                        :data="distributionChart.data"
                        :colors="distributionChart.colors"
                        :height="240"
                    />
                </div>
            </div>
        </div>

        <!-- Department bar chart + Task distribution -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="dashboard-panel overflow-hidden lg:col-span-2">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Perbandingan Departemen</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Rata-rata Achievement per Departemen</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.isLoadingDepartment" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <div v-else-if="!departmentChart.labels.length" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Belum ada data departemen.
                    </div>
                    <BarChart
                        v-else
                        :key="departmentKey"
                        :labels="departmentChart.labels"
                        :datasets="departmentChart.datasets"
                        y-label="Nilai"
                        :height="280"
                    />
                </div>
            </div>

            <div class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Distribusi Skor KPI</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Predikat Pegawai</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.isLoadingDistribution" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <div v-else-if="!taskDistChart.labels.length" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Belum ada data.
                    </div>
                    <DoughnutChart
                        v-else
                        :key="taskDistKey"
                        :labels="taskDistChart.labels"
                        :data="taskDistChart.data"
                        :colors="taskDistChart.colors"
                        :height="240"
                    />
                </div>
            </div>
        </div>

        <!-- Export -->
        <div class="dashboard-panel">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Export Data</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Unduh Laporan</h3>
            </div>
            <div class="flex flex-wrap gap-3 p-6">
                <button class="btn-secondary" @click="exportKpiCsv">
                    <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                    </svg>
                    Export Laporan KPI (CSV)
                </button>
                <button class="btn-secondary" @click="exportRankingCsv">
                    <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                    </svg>
                    Export Ranking (CSV)
                </button>
            </div>
        </div>
    </AppLayout>
</template>
