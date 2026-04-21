<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAnalyticsStore } from '@/stores/analytics';
import { useAuthStore } from '@/stores/auth';
import { useDepartmentStore } from '@/stores/department';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import LineChart from '@/components/charts/LineChart.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { downloadFile } from '@/services/api';

const store = useAnalyticsStore();
const authStore = useAuthStore();
const departmentStore = useDepartmentStore();

const yearOptions = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const monthOptions = [
    { value: '', label: 'Semua Bulan' },
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref('');
const selectedDepartmentId = ref('');

const selectedMonthLabel = computed(() =>
    monthOptions.find((month) => month.value === selectedMonth.value)?.label ?? 'Semua Bulan'
);

const selectedDepartmentName = computed(() => {
    if (!selectedDepartmentId.value) return 'Semua Departemen';
    return departmentStore.findById(Number(selectedDepartmentId.value))?.nama ?? 'Departemen Terpilih';
});

const activeFilterLabel = computed(() => [
    selectedYear.value,
    selectedMonthLabel.value,
    selectedDepartmentName.value,
].join(' / '));

const exportParams = computed(() => ({
    tahun: selectedYear.value,
    bulan: selectedMonth.value || undefined,
    department_id: selectedDepartmentId.value || undefined,
}));

const exportSuffix = computed(() => {
    const month = selectedMonth.value ? `-${String(selectedMonth.value).padStart(2, '0')}` : '';
    const department = selectedDepartmentId.value ? `-dept-${selectedDepartmentId.value}` : '';
    return `${selectedYear.value}${month}${department}`;
});

async function applyFilter() {
    store.setFilter('tahun', selectedYear.value);
    store.setFilter('bulan', selectedMonth.value || null);
    store.setFilter('department_id', selectedDepartmentId.value || null);
    await store.fetchAll();
}

onMounted(async () => {
    await Promise.all([
        departmentStore.fetchDepartments(),
        applyFilter(),
    ]);
});

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(applyFilter, { interval: 30_000 });

const trendChart = computed(() => {
    const raw = store.trend;
    if (!raw?.labels?.length) return { labels: [], datasets: [] };

    return {
        labels: raw.labels,
        datasets: (raw.datasets ?? []).map(ds => ({
            label: ds.label,
            data: ds.data,
            color: ds.type === 'percentage' ? '#dc2626' : '#111827',
            fill: ds.type === 'percentage',
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
            data: ds.data,
            color: ['#dc2626', '#111827'][i] ?? '#dc2626',
        })),
    };
});

const distributionChart = computed(() => {
    const raw = store.distribution?.report_based;
    if (!raw?.labels?.length) return { labels: [], data: [], colors: [] };

    const colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'];
    return {
        labels: raw.labels,
        data: raw.data,
        colors: raw.labels.map((_, i) => colors[i] ?? '#94a3b8'),
    };
});

const taskDistChart = computed(() => {
    const raw = store.distribution?.task_based;
    if (!raw?.labels?.length) return { labels: [], data: [], colors: [] };

    const colorMap = {
        'Baik Sekali': '#22c55e',
        'Baik': '#3b82f6',
        'Cukup': '#f59e0b',
        'Kurang': '#ef4444',
        'Buruk': '#dc2626',
    };

    return {
        labels: raw.labels,
        data: raw.data,
        colors: raw.labels.map(l => colorMap[l] ?? '#94a3b8'),
    };
});

const trendKey = computed(() => [
    trendChart.value.labels.join(','),
    trendChart.value.datasets.map((ds) => ds.data.join(',')).join('|'),
].join('::'));
const departmentKey = computed(() => [
    departmentChart.value.labels.join(','),
    departmentChart.value.datasets.map((ds) => ds.data.join(',')).join('|'),
].join('::'));
const distributionKey = computed(() => distributionChart.value.data.join(','));
const taskDistKey = computed(() => taskDistChart.value.data.join(','));

const avgAchievement = computed(() => Number(store.overview?.avg_achievement ?? 0));
const totalReports = computed(() => Number(store.overview?.total_reports ?? 0));
const highPerformers = computed(() =>
    Number(store.overview?.excellent_count ?? 0) + Number(store.overview?.good_count ?? 0)
);
const averagePerformers = computed(() => Number(store.overview?.average_count ?? 0));
const lowPerformers = computed(() => Number(store.overview?.bad_count ?? 0));

const percentageTrendDataset = computed(() =>
    trendChart.value.datasets.find((dataset) => dataset.label?.includes('%')) ?? trendChart.value.datasets[0]
);
const trendValues = computed(() =>
    (percentageTrendDataset.value?.data ?? [])
        .filter((value) => value !== null && value !== undefined)
        .map((value) => Number(value))
);
const trendDelta = computed(() => {
    if (trendValues.value.length < 2) return null;

    const current = trendValues.value[trendValues.value.length - 1];
    const previous = trendValues.value[trendValues.value.length - 2];
    return Math.round((current - previous) * 10) / 10;
});
const trendInsight = computed(() => {
    if (trendDelta.value === null) return 'Insight akan muncul setelah minimal dua periode memiliki data.';

    const direction = trendDelta.value >= 0 ? 'naik' : 'menurun';
    return `Performance ${direction} ${Math.abs(trendDelta.value)}% dari periode sebelumnya.`;
});
const trendTone = computed(() => trendDelta.value === null ? 'neutral' : trendDelta.value >= 0 ? 'good' : 'bad');

const healthLabel = computed(() => {
    if (avgAchievement.value >= 100) return 'Excellent';
    if (avgAchievement.value >= 80) return 'Good';
    if (avgAchievement.value >= 50) return 'Average';
    return 'Bad';
});
const healthClasses = computed(() => ({
    Excellent: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    Good: 'border-blue-200 bg-blue-50 text-blue-700',
    Average: 'border-orange-200 bg-orange-50 text-orange-700',
    Bad: 'border-red-200 bg-red-50 text-red-700',
}[healthLabel.value]));

const hasTrendData = computed(() => trendChart.value.datasets.some(
    (dataset) => (dataset.data ?? []).some((value) => value !== null && value !== undefined && Number(value) > 0)
));
const hasDistributionData = computed(() => distributionChart.value.data.some((value) => Number(value) > 0));
const hasTaskDistData = computed(() => taskDistChart.value.data.some((value) => Number(value) > 0));
const hasDepartmentData = computed(() => departmentChart.value.datasets.some(
    (dataset) => (dataset.data ?? []).some((value) => Number(value) > 0)
));
const distributionLegend = computed(() =>
    distributionChart.value.labels.map((label, index) => ({
        label,
        value: distributionChart.value.data[index] ?? 0,
        color: distributionChart.value.colors[index] ?? '#94a3b8',
    }))
);

const summaryCards = computed(() => [
    {
        label: 'Total Employees',
        value: store.overview?.total_employees ?? 0,
        helper: `${store.overview?.total_departments ?? 0} departemen aktif`,
        accent: 'bg-slate-950 text-white',
        icon: 'users',
        trend: 'Coverage SDM',
        trendClass: 'text-white/70',
    },
    {
        label: 'Average Achievement',
        value: avgAchievement.value ? `${avgAchievement.value}%` : '-',
        helper: `${totalReports.value} laporan KPI`,
        accent: 'bg-white text-slate-950',
        icon: 'chart',
        trend: trendInsight.value,
        trendClass: trendTone.value === 'bad' ? 'text-red-600' : trendTone.value === 'good' ? 'text-emerald-600' : 'text-slate-500',
    },
    {
        label: 'High Achievers',
        value: highPerformers.value,
        helper: 'Excellent + Good',
        accent: 'bg-white text-slate-950',
        icon: 'up',
        trend: 'Target sehat',
        trendClass: 'text-emerald-600',
    },
    {
        label: 'Low Performance',
        value: lowPerformers.value,
        helper: `${averagePerformers.value} average, ${lowPerformers.value} bad`,
        accent: 'bg-red-600 text-white',
        icon: 'down',
        trend: lowPerformers.value > 0 ? 'Butuh review HR' : 'Tidak ada risiko besar',
        trendClass: 'text-white/80',
    },
]);

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
        params: exportParams.value,
        fallbackFilename: `laporan-kpi-${exportSuffix.value}.csv`,
    });
}

async function exportRankingCsv() {
    await downloadFile('/export/ranking/csv', {
        params: exportParams.value,
        fallbackFilename: `ranking-kpi-${exportSuffix.value}.csv`,
    });
}

async function exportAnalyticsPdf() {
    await downloadFile('/export/analytics/pdf', {
        params: exportParams.value,
        fallbackFilename: `analytics-kpi-${exportSuffix.value}.pdf`,
    });
}
</script>

<template>
    <AppLayout>
        <section class="overflow-hidden rounded-lg border border-slate-950 bg-slate-950 text-white shadow-[0_18px_44px_rgba(15,23,42,0.18)]">
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[1.4fr_0.8fr] lg:p-7">
                <div class="flex flex-col justify-between gap-8">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-red-500/35 bg-red-500/12 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-red-100">
                            HR KPI Command Center
                        </div>
                        <h2 class="mt-5 max-w-3xl text-3xl font-black tracking-tight text-white sm:text-4xl">
                            Analytics KPI
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/68">
                            Monitor achievement, distribusi performa, dan perbandingan departemen dalam satu dashboard HR yang ringkas dan siap dipakai untuk review.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold', healthClasses]">
                            Status: {{ healthLabel }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/8 px-3 py-1 text-xs font-semibold text-white/72">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                            Auto refresh 30 dtk
                        </span>
                        <span v-if="lastUpdated" class="text-xs text-white/45">
                            Diperbarui {{ formatTime(lastUpdated) }}
                        </span>
                    </div>
                </div>

                <div class="rounded-lg border border-white/10 bg-white/[0.06] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/45">Insight</p>
                    <div class="mt-4 flex items-start gap-3">
                        <div
                            :class="[
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                trendTone === 'bad' ? 'bg-red-500/20 text-red-200' : trendTone === 'good' ? 'bg-emerald-500/20 text-emerald-200' : 'bg-white/10 text-white/70',
                            ]"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path v-if="trendTone === 'bad'" d="m7 7 10 10M17 17V8m0 9H8"/>
                                <path v-else d="m7 17 10-10M17 7v9m0-9H8"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold leading-6 text-white">{{ trendInsight }}</p>
                            <p class="mt-1 text-xs leading-5 text-white/45">{{ activeFilterLabel }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-[1fr_1fr_1.2fr_auto]">
                <label class="space-y-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Year</span>
                    <select v-model.number="selectedYear" class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10" @change="applyFilter">
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Month</span>
                    <select v-model="selectedMonth" class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10" @change="applyFilter">
                        <option v-for="month in monthOptions" :key="month.value || 'all'" :value="month.value">{{ month.label }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Department</span>
                    <select v-model="selectedDepartmentId" class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10" @change="applyFilter">
                        <option value="">Semua Departemen</option>
                        <option v-for="department in departmentStore.departments" :key="department.id" :value="department.id">
                            {{ department.nama }}
                        </option>
                    </select>
                </label>

                <div class="flex items-end">
                    <button
                        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-slate-950 bg-slate-950 px-4 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-lg active:translate-y-0 lg:w-11"
                        :class="{ 'animate-spin': isRefreshing }"
                        title="Refresh"
                        @click="refresh"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                        </svg>
                        <span class="lg:hidden">Refresh</span>
                    </button>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <template v-if="store.isLoadingOverview">
                <Skeleton v-for="i in 4" :key="i" class="h-36 rounded-lg" />
            </template>
            <article
                v-for="card in summaryCards"
                v-else
                :key="card.label"
                :class="[
                    'group rounded-lg border p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl',
                    card.accent,
                    card.accent.includes('bg-white') ? 'border-slate-200 hover:border-slate-300' : 'border-transparent',
                ]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p :class="['text-[11px] font-black uppercase tracking-[0.13em]', card.accent.includes('bg-white') ? 'text-slate-500' : 'text-white/55']">
                            {{ card.label }}
                        </p>
                        <p class="mt-3 text-3xl font-black tracking-tight">{{ card.value }}</p>
                    </div>
                    <div :class="['flex h-10 w-10 items-center justify-center rounded-lg transition group-hover:scale-105', card.accent.includes('bg-white') ? 'bg-slate-100 text-slate-950' : 'bg-white/12 text-white']">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                            <template v-if="card.icon === 'users'">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                                <circle cx="9.5" cy="7" r="4"/>
                            </template>
                            <path v-if="card.icon === 'chart'" d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>
                            <path v-if="card.icon === 'up'" d="m7 17 10-10M17 7v9m0-9H8"/>
                            <path v-if="card.icon === 'down'" d="m7 7 10 10M17 17V8m0 9H8"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-between gap-3">
                    <span :class="['text-xs font-semibold', card.accent.includes('bg-white') ? 'text-slate-500' : 'text-white/60']">{{ card.helper }}</span>
                    <span :class="['text-right text-xs font-bold', card.trendClass]">{{ card.trend }}</span>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-red-600">Monthly Trend</p>
                        <h3 class="mt-1 text-lg font-black tracking-tight text-slate-950">Achievement Rate {{ selectedYear }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ selectedDepartmentName }}</p>
                    </div>
                    <span :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-bold', trendTone === 'bad' ? 'bg-red-50 text-red-700' : trendTone === 'good' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600']">
                        {{ trendInsight }}
                    </span>
                </div>
                <div class="p-5">
                    <Skeleton v-if="store.isLoadingTrend" class="h-72 rounded-lg" />
                    <div v-else-if="!hasTrendData" class="flex h-72 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-bold text-slate-700">Belum ada tren KPI</p>
                        <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">Data akan muncul setelah laporan KPI tersedia untuk filter ini.</p>
                    </div>
                    <LineChart v-else :key="trendKey" :labels="trendChart.labels" :datasets="trendChart.datasets" y-label="Nilai" :height="300" />
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-red-600">KPI Distribution</p>
                    <h3 class="mt-1 text-lg font-black tracking-tight text-slate-950">{{ selectedMonthLabel }}</h3>
                    <p class="mt-1 text-xs text-slate-500">Excellent, good, average, dan bad</p>
                </div>
                <div class="p-5">
                    <Skeleton v-if="store.isLoadingDistribution" class="h-64 rounded-lg" />
                    <div v-else-if="!hasDistributionData" class="flex h-64 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                        <p class="text-sm font-bold text-slate-700">Belum ada distribusi</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Tidak ada laporan pada filter ini.</p>
                    </div>
                    <template v-else>
                        <DoughnutChart :key="distributionKey" :labels="distributionChart.labels" :data="distributionChart.data" :colors="distributionChart.colors" :height="230" />
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div v-for="item in distributionLegend" :key="item.label" class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <span class="truncate text-xs font-semibold text-slate-600">{{ item.label }}</span>
                                </div>
                                <p class="mt-1 text-lg font-black text-slate-950">{{ item.value }}</p>
                            </div>
                        </div>
                    </template>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="border-b border-slate-100 p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-red-600">Department Comparison</p>
                    <h3 class="mt-1 text-lg font-black tracking-tight text-slate-950">Average Achievement per Department</h3>
                    <p class="mt-1 text-xs text-slate-500">Membandingkan persentase KPI dan skor KPI antar departemen.</p>
                </div>
                <div class="p-5">
                    <Skeleton v-if="store.isLoadingDepartment" class="h-80 rounded-lg" />
                    <div v-else-if="!hasDepartmentData" class="flex h-80 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                        <p class="text-sm font-bold text-slate-700">Belum ada data departemen</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Coba ubah filter bulan atau departemen.</p>
                    </div>
                    <BarChart v-else :key="departmentKey" :labels="departmentChart.labels" :datasets="departmentChart.datasets" y-label="Nilai" :height="320" />
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-red-600">Score Distribution</p>
                    <h3 class="mt-1 text-lg font-black tracking-tight text-slate-950">Predikat Pegawai</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ selectedMonthLabel }} / {{ selectedDepartmentName }}</p>
                </div>
                <div class="p-5">
                    <Skeleton v-if="store.isLoadingDistribution" class="h-64 rounded-lg" />
                    <div v-else-if="!hasTaskDistData" class="flex h-64 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                        <p class="text-sm font-bold text-slate-700">Belum ada skor pegawai</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Skor akan muncul setelah KPI pegawai terhitung.</p>
                    </div>
                    <DoughnutChart v-else :key="taskDistKey" :labels="taskDistChart.labels" :data="taskDistChart.data" :colors="taskDistChart.colors" :height="260" />
                </div>
            </article>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-red-600">Export Data</p>
                    <h3 class="mt-1 text-lg font-black text-slate-950">Unduh laporan</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ activeFilterLabel }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-red-700 hover:shadow-lg" @click="exportAnalyticsPdf">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/>
                        </svg>
                        PDF
                    </button>
                    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50" @click="exportKpiCsv">
                        Laporan CSV
                    </button>
                    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50" @click="exportRankingCsv">
                        Ranking CSV
                    </button>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
