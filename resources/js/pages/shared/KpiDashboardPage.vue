<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Activity, Medal, RefreshCw, ShieldAlert, TrendingUp, Users } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import KpiDetailDialog from '@/components/kpi-dashboard/KpiDetailDialog.vue';
import KpiFilters from '@/components/kpi-dashboard/KpiFilters.vue';
import KpiLeaderboardTable from '@/components/kpi-dashboard/KpiLeaderboardTable.vue';
import KpiSummaryCard from '@/components/kpi-dashboard/KpiSummaryCard.vue';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiDashboardStore } from '@/stores/kpiDashboard';

const props = defineProps({
    persona: { type: String, default: 'HR Control Center' },
});

const employeeStore = useEmployeeStore();
const dashboardStore = useKpiDashboardStore();

const search = ref('');
const currentPage = ref(1);
const sortState = reactive({ field: 'normalized_score', direction: 'desc' });
const detailDialogOpen = ref(false);
const isEchoLive = ref(false);

const localFilters = ref({
    period: dashboardStore.filters.period,
    roleId: dashboardStore.filters.roleId,
    employeeId: dashboardStore.filters.employeeId,
});

let echoTimer = null;

const summary = computed(() => dashboardStore.summary ?? {
    average_kpi: 0,
    employee_count: 0,
    top_performer: null,
    low_performer: null,
});

const roleOptions = computed(() => {
    const unique = new Map();

    employeeStore.employees.forEach((employee) => {
        const key = employee.role_id ?? employee.role_ref?.id;
        const label = employee.role_ref?.name ?? employee.role ?? employee.jabatan;

        if (key && label && !unique.has(String(key))) {
            unique.set(String(key), { value: String(key), label });
        }
    });

    return [{ value: '', label: 'Semua role' }, ...unique.values()];
});

const employeeOptions = computed(() => {
    const filtered = employeeStore.employees
        .filter((employee) => !localFilters.value.roleId || String(employee.role_id) === String(localFilters.value.roleId))
        .map((employee) => ({ value: String(employee.id), label: employee.nama }));

    return [{ value: '', label: 'Semua karyawan' }, ...filtered];
});

const normalizedRows = computed(() => (
    (dashboardStore.ranking ?? []).map((row, index) => ({
        ...row,
        rank: row.rank ?? index + 1,
        normalized_score: Number(row.normalized_score ?? 0),
        raw_score: Number(row.raw_score ?? 0),
    }))
));

const filteredRows = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    let rows = normalizedRows.value.filter((row) => {
        const employeeMatch = !localFilters.value.employeeId || String(row.user?.id) === String(localFilters.value.employeeId);
        const searchMatch = !keyword
            || row.user?.nama?.toLowerCase().includes(keyword)
            || row.role?.name?.toLowerCase().includes(keyword)
            || row.user?.jabatan?.toLowerCase().includes(keyword);

        return employeeMatch && searchMatch;
    });

    rows = [...rows].sort((a, b) => {
        const aValue = sortState.field === 'role' ? (a.role?.name ?? '') : a[sortState.field];
        const bValue = sortState.field === 'role' ? (b.role?.name ?? '') : b[sortState.field];

        if (typeof aValue === 'string' || typeof bValue === 'string') {
            const left = String(aValue ?? '').toLowerCase();
            const right = String(bValue ?? '').toLowerCase();

            if (left < right) return sortState.direction === 'asc' ? -1 : 1;
            if (left > right) return sortState.direction === 'asc' ? 1 : -1;

            return 0;
        }

        return sortState.direction === 'asc'
            ? Number(aValue) - Number(bValue)
            : Number(bValue) - Number(aValue);
    });

    return rows;
});

const chartSeries = computed(() => ({
    labels: dashboardStore.trend.map((point) => point.label),
    average: dashboardStore.trend.map((point) => point.average),
    employees: dashboardStore.trend.map((point) => point.employees),
}));

const avgKpi = computed(() => Number(summary.value.average_kpi ?? 0));

const teamPerformanceChart = computed(() => {
    const topRows = filteredRows.value.slice(0, 8);

    return {
        labels: topRows.map((row) => row.user?.nama ?? '-'),
        datasets: [
            {
                label: 'KPI Score',
                data: topRows.map((row) => row.normalized_score),
                color: '#2563eb',
            },
        ],
    };
});

const statusDistributionChart = computed(() => {
    const buckets = filteredRows.value.reduce((result, row) => {
        if (row.normalized_score >= 80) result.good += 1;
        else if (row.normalized_score >= 60) result.average += 1;
        else result.bad += 1;

        return result;
    }, { good: 0, average: 0, bad: 0 });

    return {
        labels: ['Good', 'Average', 'Bad'],
        data: [buckets.good, buckets.average, buckets.bad],
        colors: ['#22c55e', '#f59e0b', '#ef4444'],
    };
});

const topHighlights = computed(() => filteredRows.value.slice(0, 3));

const insightMetrics = computed(() => {
    const rows = filteredRows.value;
    const highPerformers = rows.filter((row) => row.normalized_score >= 80).length;
    const needsAttention = rows.filter((row) => row.normalized_score < 60).length;
    const trendLength = dashboardStore.trend.length;
    const latestAverage = trendLength ? Number(dashboardStore.trend[trendLength - 1]?.average ?? 0) : 0;
    const previousAverage = trendLength > 1 ? Number(dashboardStore.trend[trendLength - 2]?.average ?? 0) : 0;
    const momentum = trendLength >= 2 ? latestAverage - previousAverage : 0;

    return { highPerformers, needsAttention, momentum };
});

const summaryCards = computed(() => [
    {
        title: 'Total Karyawan',
        value: summary.value.employee_count,
        description: 'Karyawan yang masuk kalkulasi KPI pada periode aktif.',
        icon: Users,
        tone: 'info',
        chip: props.persona,
        progress: null,
    },
    {
        title: 'Rata-rata KPI',
        value: avgKpi.value,
        description: 'Rerata skor KPI tim dengan normalisasi maksimum 100.',
        icon: Activity,
        tone: avgKpi.value >= 80 ? 'success' : avgKpi.value >= 60 ? 'warning' : 'danger',
        chip: 'Live score',
        progress: avgKpi.value,
    },
    {
        title: 'Top Performer',
        value: summary.value.top_performer?.user?.nama ?? '-',
        description: summary.value.top_performer
            ? `Skor ${summary.value.top_performer.normalized_score} | Grade ${summary.value.top_performer.grade}`
            : 'Belum ada data.',
        icon: Medal,
        tone: 'success',
        chip: 'Best',
        progress: summary.value.top_performer?.normalized_score ?? null,
    },
    {
        title: 'Low Performer',
        value: summary.value.low_performer?.user?.nama ?? '-',
        description: summary.value.low_performer
            ? `Skor ${summary.value.low_performer.normalized_score} | Grade ${summary.value.low_performer.grade}`
            : 'Belum ada data.',
        icon: ShieldAlert,
        tone: 'danger',
        chip: 'Alert',
        progress: summary.value.low_performer?.normalized_score ?? null,
    },
]);

function badgeClass(score) {
    if (score >= 80) return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    if (score >= 60) return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300';
}

function cardBorderClass(index) {
    if (index === 0) return 'border-amber-200/80 bg-gradient-to-br from-amber-50 to-white dark:border-amber-900/50 dark:from-amber-950/30 dark:to-slate-950';
    if (index === 1) return 'border-slate-200/80 bg-gradient-to-br from-slate-50 to-white dark:border-slate-800 dark:from-slate-900 dark:to-slate-950';
    return 'border-blue-200/80 bg-gradient-to-br from-blue-50 to-white dark:border-blue-900/50 dark:from-blue-950/20 dark:to-slate-950';
}

async function loadPage() {
    await Promise.all([
        employeeStore.fetchEmployees(),
        dashboardStore.hydrate({ period: localFilters.value.period, roleId: localFilters.value.roleId }),
    ]);
}

function attachRealtime() {
    const echo = window.Echo;

    if (!echo?.channel) return;

    echo.channel('kpi-channel').listen('.kpi.updated', async () => {
        isEchoLive.value = true;
        await dashboardStore.hydrate({ period: localFilters.value.period, roleId: localFilters.value.roleId });

        if (detailDialogOpen.value && dashboardStore.detail?.user?.id) {
            await dashboardStore.fetchUserDetail(dashboardStore.detail.user.id, {
                period: localFilters.value.period,
                roleId: localFilters.value.roleId,
            });
        }
    });
}

async function applyFilters() {
    dashboardStore.setFilter('period', localFilters.value.period);
    dashboardStore.setFilter('roleId', localFilters.value.roleId);
    dashboardStore.setFilter('employeeId', localFilters.value.employeeId);
    currentPage.value = 1;

    await dashboardStore.hydrate({ period: localFilters.value.period, roleId: localFilters.value.roleId });

    if (localFilters.value.employeeId) {
        await openDetail(localFilters.value.employeeId);
    }
}

async function openDetail(userId) {
    detailDialogOpen.value = true;
    await dashboardStore.fetchUserDetail(userId, {
        period: localFilters.value.period,
        roleId: localFilters.value.roleId,
    });
}

function handleSort(field) {
    if (sortState.field === field) {
        sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortState.field = field;
    sortState.direction = field === 'role' ? 'asc' : 'desc';
}

watch(search, () => {
    currentPage.value = 1;
});

watch(filteredRows, (rows) => {
    const maxPage = Math.max(1, Math.ceil(rows.length / 8));

    if (currentPage.value > maxPage) {
        currentPage.value = maxPage;
    }
});

watch(() => localFilters.value.roleId, () => {
    if (!employeeOptions.value.some((option) => option.value === localFilters.value.employeeId)) {
        localFilters.value.employeeId = '';
    }
});

onMounted(async () => {
    echoTimer = setInterval(() => {
        isEchoLive.value = window.Echo?.connector?.pusher?.connection?.state === 'connected';
    }, 3000);

    await loadPage();
    attachRealtime();
});

onUnmounted(() => {
    if (echoTimer) clearInterval(echoTimer);
});

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(loadPage, { interval: 45_000 });
</script>

<template>
    <AppLayout>
        <section class="relative overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-6 shadow-sm dark:border-slate-800">
            <div class="pointer-events-none absolute -right-12 -top-10 h-44 w-44 rounded-full bg-blue-500/20 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-10 left-10 h-40 w-40 rounded-full bg-emerald-500/15 blur-3xl" />

            <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white/75">
                            {{ persona }}
                        </span>
                        <span
                            :class="[
                                'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-semibold',
                                isEchoLive
                                    ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300'
                                    : 'border-amber-400/30 bg-amber-400/10 text-amber-300',
                            ]"
                        >
                            <span :class="['h-2 w-2 rounded-full', isEchoLive ? 'animate-pulse bg-emerald-400' : 'bg-amber-400']" />
                            {{ isEchoLive ? 'Realtime active' : 'Polling fallback' }}
                        </span>
                    </div>

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl">
                        KPI Dashboard
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-7 text-white/65">
                        Monitor score KPI, leaderboard karyawan, dan perubahan performa tim secara realtime dalam satu tampilan analytics.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[420px]">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">Momentum</div>
                        <div class="mt-2 flex items-center gap-2 text-white">
                            <TrendingUp class="h-4 w-4 text-emerald-300" />
                            <span class="text-2xl font-semibold">{{ insightMetrics.momentum > 0 ? '+' : '' }}{{ insightMetrics.momentum.toFixed(1) }}</span>
                        </div>
                        <div class="mt-1 text-xs text-white/55">vs periode sebelumnya</div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">High vs alert</div>
                        <div class="mt-2 flex items-end gap-2 text-white">
                            <span class="text-2xl font-semibold">{{ insightMetrics.highPerformers }}</span>
                            <span class="pb-1 text-xs text-white/45">high</span>
                            <span class="text-white/25">/</span>
                            <span class="text-xl font-semibold text-rose-200">{{ insightMetrics.needsAttention }}</span>
                            <span class="pb-1 text-xs text-white/45">alert</span>
                        </div>
                        <div class="mt-1 text-xs text-white/55">skor 80 ke atas dan di bawah 60</div>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">Last sync</div>
                            <div class="mt-2 text-sm font-medium text-white/80">
                                {{ lastUpdated ? formatTime(lastUpdated) : '-' }}
                            </div>
                            <div class="mt-1 text-xs text-white/50">refresh otomatis 45 detik</div>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white/75 transition hover:bg-white/15 hover:text-white"
                            :class="{ 'animate-spin': isRefreshing }"
                            title="Refresh data"
                            @click="refresh"
                        >
                            <RefreshCw class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-5">
            <KpiFilters
                v-model="localFilters"
                :role-options="roleOptions"
                :employee-options="employeeOptions"
                :search="search"
                @update:search="search = $event"
                @apply="applyFilters"
            />
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiSummaryCard
                v-for="card in summaryCards"
                :key="card.title"
                :title="card.title"
                :value="card.value"
                :description="card.description"
                :icon="card.icon"
                :tone="card.tone"
                :chip="card.chip"
                :progress="card.progress"
            />
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.6fr_0.9fr]">
            <Card class="overflow-hidden rounded-[28px] border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Trend analytics</div>
                            <CardTitle class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">KPI trend 6 bulan</CardTitle>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Rata-rata KPI dan jumlah karyawan aktif dalam periode yang dipilih.
                            </p>
                        </div>
                        <Badge>Realtime chart</Badge>
                    </div>
                </CardHeader>
                <CardContent class="pt-4">
                    <template v-if="dashboardStore.isLoadingTrend">
                        <Skeleton class="h-72 rounded-2xl" />
                    </template>
                    <template v-else-if="!chartSeries.labels.length">
                        <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada data trend KPI.
                        </div>
                    </template>
                    <LineChart
                        v-else
                        :labels="chartSeries.labels"
                        :datasets="[
                            { label: 'Avg KPI', data: chartSeries.average, color: '#2563eb', fill: true },
                            { label: 'Karyawan aktif', data: chartSeries.employees, color: '#10b981' },
                        ]"
                        title=""
                        :height="285"
                        y-label="Score"
                    />
                </CardContent>
            </Card>

            <Card class="overflow-hidden rounded-[28px] border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Leaderboard pulse</div>
                    <CardTitle class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">Top performers</CardTitle>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Snapshot performer terbaik berdasarkan filter aktif.
                    </p>
                </CardHeader>
                <CardContent class="space-y-3 pt-4">
                    <template v-if="dashboardStore.isLoadingDashboard">
                        <Skeleton v-for="index in 3" :key="index" class="h-24 rounded-2xl" />
                    </template>
                    <template v-else-if="topHighlights.length">
                        <button
                            v-for="(row, index) in topHighlights"
                            :key="row.user?.id"
                            type="button"
                            :class="['w-full rounded-2xl border p-4 text-left transition hover:-translate-y-0.5 hover:shadow-sm', cardBorderClass(index)]"
                            @click="openDetail(row.user?.id)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Rank {{ row.rank }}</div>
                                    <div class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">{{ row.user?.nama }}</div>
                                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ row.role?.name ?? row.user?.role_ref?.name ?? row.user?.jabatan ?? '-' }}
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-white/80 px-3 py-2 text-right shadow-sm dark:bg-slate-900/70">
                                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ row.normalized_score }}</div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">score</div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span :class="['rounded-full border px-2.5 py-1 text-xs font-semibold', badgeClass(row.normalized_score)]">
                                    Grade {{ row.grade }}
                                </span>
                                <span class="text-xs text-slate-400">Klik untuk detail</span>
                            </div>
                        </button>
                    </template>
                    <template v-else>
                        <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada leaderboard untuk filter ini.
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_1fr]">
            <Card class="overflow-hidden rounded-[28px] border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Team performance</div>
                            <CardTitle class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">Skor tim teratas</CardTitle>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Perbandingan cepat untuk 8 karyawan dengan score tertinggi.
                            </p>
                        </div>
                        <Badge variant="outline">Bar chart</Badge>
                    </div>
                </CardHeader>
                <CardContent class="pt-4">
                    <template v-if="dashboardStore.isLoadingDashboard">
                        <Skeleton class="h-72 rounded-2xl" />
                    </template>
                    <template v-else-if="!teamPerformanceChart.labels.length">
                        <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada data performa tim.
                        </div>
                    </template>
                    <BarChart
                        v-else
                        :labels="teamPerformanceChart.labels"
                        :datasets="teamPerformanceChart.datasets"
                        :height="285"
                        y-label="KPI Score"
                    />
                </CardContent>
            </Card>

            <Card class="overflow-hidden rounded-[28px] border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Score distribution</div>
                    <CardTitle class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">Sebaran status KPI</CardTitle>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Komposisi performer baik, cukup, dan perlu perhatian.
                    </p>
                </CardHeader>
                <CardContent class="pt-4">
                    <template v-if="dashboardStore.isLoadingDashboard">
                        <Skeleton class="h-72 rounded-2xl" />
                    </template>
                    <template v-else-if="!filteredRows.length">
                        <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada data distribusi.
                        </div>
                    </template>
                    <div v-else class="space-y-5">
                        <DoughnutChart
                            :labels="statusDistributionChart.labels"
                            :data="statusDistributionChart.data"
                            :colors="statusDistributionChart.colors"
                            :height="220"
                        />
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Good</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ statusDistributionChart.data[0] }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400" />
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Average</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ statusDistributionChart.data[1] }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Bad</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ statusDistributionChart.data[2] }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="mt-5">
            <KpiLeaderboardTable
                :rows="filteredRows"
                :loading="dashboardStore.isLoadingDashboard"
                :page="currentPage"
                :sort-field="sortState.field"
                :sort-direction="sortState.direction"
                @sort="handleSort"
                @update:page="currentPage = $event"
                @open-detail="openDetail"
            />
        </div>

        <KpiDetailDialog
            v-model:open="detailDialogOpen"
            :loading="dashboardStore.isLoadingDetail"
            :detail="dashboardStore.detail"
        />
    </AppLayout>
</template>
