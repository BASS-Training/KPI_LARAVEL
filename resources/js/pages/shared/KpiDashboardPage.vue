<script setup>
import { TransitionGroup, computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Activity, Medal, RefreshCw, ShieldAlert, TrendingDown, TrendingUp, Users } from 'lucide-vue-next';
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
import { useAuthStore } from '@/stores/auth';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiDashboardStore } from '@/stores/kpiDashboard';

const props = defineProps({
    persona: { type: String, default: 'HR Control Center' },
});

const authStore = useAuthStore();
const employeeStore = useEmployeeStore();
const dashboardStore = useKpiDashboardStore();

const search = ref('');
const currentPage = ref(1);
const sortState = reactive({ field: 'normalized_score', direction: 'desc' });
const detailDialogOpen = ref(false);
const leaderboardTab = ref('top');

const localFilters = ref({
    period: dashboardStore.filters.period,
    roleId: dashboardStore.filters.roleId,
    employeeId: dashboardStore.filters.employeeId,
});

// ── Computed ──────────────────────────────────────────────────────────────────

const summary = computed(() => dashboardStore.summary ?? {
    average_kpi: 0,
    employee_count: 0,
    top_performer: null,
    low_performer: null,
});

const roleOptions = computed(() => {
    const unique = new Map();
    employeeStore.employees.forEach((employee) => {
        const key = employee.position_id;
        const label = employee.jabatan;
        if (key && label && !unique.has(String(key))) {
            unique.set(String(key), { value: String(key), label });
        }
    });
    return [{ value: '', label: 'Semua role' }, ...unique.values()];
});

const employeeOptions = computed(() => {
    const filtered = employeeStore.employees
        .filter((e) => !localFilters.value.roleId || String(e.position_id) === String(localFilters.value.roleId))
        .map((e) => ({ value: String(e.id), label: e.nama }));
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
        const empMatch = !localFilters.value.employeeId
            || String(row.user?.id) === String(localFilters.value.employeeId);
        const searchMatch = !keyword
            || row.user?.nama?.toLowerCase().includes(keyword)
            || row.role?.name?.toLowerCase().includes(keyword)
            || row.user?.jabatan?.toLowerCase().includes(keyword);
        return empMatch && searchMatch;
    });

    rows = [...rows].sort((a, b) => {
        const aVal = sortState.field === 'role' ? (a.role?.name ?? '') : a[sortState.field];
        const bVal = sortState.field === 'role' ? (b.role?.name ?? '') : b[sortState.field];
        if (typeof aVal === 'string' || typeof bVal === 'string') {
            const l = String(aVal ?? '').toLowerCase();
            const r = String(bVal ?? '').toLowerCase();
            if (l < r) return sortState.direction === 'asc' ? -1 : 1;
            if (l > r) return sortState.direction === 'asc' ? 1 : -1;
            return 0;
        }
        return sortState.direction === 'asc'
            ? Number(aVal) - Number(bVal)
            : Number(bVal) - Number(aVal);
    });
    return rows;
});

const chartSeries = computed(() => ({
    labels: dashboardStore.trend.map((p) => p.label),
    average: dashboardStore.trend.map((p) => p.average),
    employees: dashboardStore.trend.map((p) => p.employees),
}));

const trendChartKey = computed(() => [
    chartSeries.value.labels.join('|'),
    chartSeries.value.average.join('|'),
    chartSeries.value.employees.join('|'),
].join('::'));

const avgKpi = computed(() => Number(summary.value.average_kpi ?? 0));

const teamPerformanceChart = computed(() => {
    const topRows = filteredRows.value.slice(0, 8);
    return {
        labels: topRows.map((row) => row.user?.nama ?? '-'),
        datasets: [{ label: 'KPI Score', data: topRows.map((r) => r.normalized_score), color: '#2563eb' }],
    };
});

const teamPerformanceKey = computed(() => [
    teamPerformanceChart.value.labels.join('|'),
    teamPerformanceChart.value.datasets[0]?.data?.join('|') ?? '',
].join('::'));

const statusDistributionChart = computed(() => {
    const buckets = filteredRows.value.reduce(
        (acc, row) => {
            if (row.normalized_score >= 80) acc.good += 1;
            else if (row.normalized_score >= 60) acc.average += 1;
            else acc.bad += 1;
            return acc;
        },
        { good: 0, average: 0, bad: 0 },
    );
    return {
        labels: ['Good', 'Average', 'Bad'],
        data: [buckets.good, buckets.average, buckets.bad],
        colors: ['#22c55e', '#f59e0b', '#ef4444'],
    };
});

const statusDistributionKey = computed(() => statusDistributionChart.value.data.join('|'));

const topHighlights = computed(() => filteredRows.value.slice(0, 3));
const topFiveRows = computed(() => filteredRows.value.slice(0, 5));
const bottomFiveRows = computed(() => [...filteredRows.value].slice(-5).reverse());
const leaderboardMiniRows = computed(() => (
    leaderboardTab.value === 'bottom' ? bottomFiveRows.value : topFiveRows.value
));

const insightMetrics = computed(() => {
    const rows = filteredRows.value;
    const highPerformers = rows.filter((r) => r.normalized_score >= 80).length;
    const needsAttention = rows.filter((r) => r.normalized_score < 60).length;
    const tLen = dashboardStore.trend.length;
    const latest = tLen ? Number(dashboardStore.trend[tLen - 1]?.average ?? 0) : 0;
    const prev = tLen > 1 ? Number(dashboardStore.trend[tLen - 2]?.average ?? 0) : 0;
    const momentum = tLen >= 2 ? latest - prev : 0;
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

// ── Helpers ───────────────────────────────────────────────────────────────────

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

// ── Data loading ──────────────────────────────────────────────────────────────

async function loadPage() {
    await Promise.all([
        employeeStore.fetchEmployees(),
        dashboardStore.hydrate({ period: localFilters.value.period, roleId: localFilters.value.roleId }),
    ]);
}

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

// ── Watches ───────────────────────────────────────────────────────────────────

watch(filteredRows, (rows) => {
    const maxPage = Math.max(1, Math.ceil(rows.length / 8));
    if (currentPage.value > maxPage) currentPage.value = maxPage;
    if (!rows.length) { leaderboardTab.value = 'top'; return; }
    if (leaderboardTab.value === 'bottom' && rows.length <= 5) leaderboardTab.value = 'top';
});

watch(() => localFilters.value.employeeId, () => {
    if (localFilters.value.employeeId) leaderboardTab.value = 'top';
});

watch(() => localFilters.value.roleId, () => {
    if (!employeeOptions.value.some((o) => o.value === localFilters.value.employeeId)) {
        localFilters.value.employeeId = '';
    }
});

watch(search, () => { currentPage.value = 1; });

// ── Actions ───────────────────────────────────────────────────────────────────

async function applyFilters() {
    dashboardStore.setFilter('period', localFilters.value.period);
    dashboardStore.setFilter('roleId', localFilters.value.roleId);
    dashboardStore.setFilter('employeeId', localFilters.value.employeeId);
    currentPage.value = 1;
    await dashboardStore.hydrate({ period: localFilters.value.period, roleId: localFilters.value.roleId });
    if (localFilters.value.employeeId) await openDetail(localFilters.value.employeeId);
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

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
    refresh();
    bindRealtimeRefresh();
});

onUnmounted(unbindRealtimeRefresh);

// Auto-refresh every 30 s — serves as the "realtime" polling mechanism
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(loadPage, { interval: 30_000 });
</script>

<template>
    <AppLayout>
        <!-- ── Hero ─────────────────────────────────────────────────────────── -->
        <section class="relative overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-6 shadow-sm dark:border-slate-800">
            <div class="pointer-events-none absolute -right-12 -top-10 h-44 w-44 rounded-full bg-blue-500/20 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-10 left-10 h-40 w-40 rounded-full bg-emerald-500/15 blur-3xl" />

            <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white/75">
                            {{ persona }}
                        </span>
                        <!-- Polling realtime indicator -->
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold text-emerald-300">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                            Auto-refresh 30 dtk
                        </span>
                    </div>

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl">
                        KPI Dashboard
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-7 text-white/65">
                        Monitor score KPI, leaderboard karyawan, dan perubahan performa tim dalam satu tampilan analytics.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[440px]">
                    <!-- Momentum -->
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">Momentum</div>
                        <div class="mt-2 flex items-center gap-2 text-white">
                            <component
                                :is="insightMetrics.momentum >= 0 ? TrendingUp : TrendingDown"
                                :class="['h-4 w-4', insightMetrics.momentum >= 0 ? 'text-emerald-300' : 'text-rose-300']"
                            />
                            <span class="text-2xl font-semibold tabular-nums">
                                {{ insightMetrics.momentum > 0 ? '+' : '' }}{{ insightMetrics.momentum.toFixed(1) }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-white/55">vs periode sebelumnya</div>
                    </div>

                    <!-- High vs Alert -->
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">High vs alert</div>
                        <div class="mt-2 flex items-end gap-2 text-white">
                            <span class="text-2xl font-semibold tabular-nums text-emerald-200">{{ insightMetrics.highPerformers }}</span>
                            <span class="pb-1 text-xs text-white/45">high</span>
                            <span class="text-white/25">/</span>
                            <span class="text-xl font-semibold tabular-nums text-rose-200">{{ insightMetrics.needsAttention }}</span>
                            <span class="pb-1 text-xs text-white/45">alert</span>
                        </div>
                        <div class="mt-1 text-xs text-white/55">skor ≥80 dan &lt;60</div>
                    </div>

                    <!-- Last sync + refresh -->
                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">Last sync</div>
                            <div class="mt-2 text-sm font-medium text-white/80">
                                {{ lastUpdated ? formatTime(lastUpdated) : '-' }}
                            </div>
                            <div class="mt-1 text-xs text-white/50">polling aktif</div>
                        </div>
                        <button
                            type="button"
                            class="ml-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white/75 transition hover:bg-white/15 hover:text-white"
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

        <!-- ── Filters ───────────────────────────────────────────────────────── -->
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

        <!-- ── Summary cards ─────────────────────────────────────────────────── -->
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

        <!-- ── Trend + Top performers ────────────────────────────────────────── -->
        <div class="mt-5 grid gap-5 xl:grid-cols-[1.45fr_0.95fr]">
            <!-- KPI Trend line chart -->
            <Card class="overflow-hidden rounded-2xl border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Trend analytics</div>
                            <CardTitle class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">KPI trend 6 bulan</CardTitle>
                            <p class="mt-1 max-w-xl text-xs leading-5 text-slate-500 dark:text-slate-400">
                                Rata-rata KPI dan jumlah karyawan aktif dalam periode yang dipilih.
                            </p>
                        </div>
                        <Badge class="shrink-0">Trend chart</Badge>
                    </div>
                </CardHeader>
                <CardContent class="px-5 py-4">
                    <template v-if="dashboardStore.isLoadingTrend">
                        <Skeleton class="h-64 rounded-2xl" />
                    </template>
                    <template v-else-if="!chartSeries.labels.length">
                        <div class="flex h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada data trend KPI.
                        </div>
                    </template>
                    <LineChart
                        v-else
                        :key="trendChartKey"
                        :labels="chartSeries.labels"
                        :datasets="[
                            { label: 'Avg KPI', data: chartSeries.average, color: '#2563eb', fill: true },
                            { label: 'Karyawan aktif', data: chartSeries.employees, color: '#10b981', yAxisID: 'y1' },
                        ]"
                        title=""
                        :height="250"
                        y-label="Score"
                        secondary-y-label="Karyawan"
                        :y-max="100"
                        legend-position="bottom"
                        :animation-duration="1150"
                        :delay-step="56"
                    />
                </CardContent>
            </Card>

            <!-- Top performers + Mini Top5/Bottom5 leaderboard -->
            <Card class="overflow-hidden rounded-[28px] border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Leaderboard</div>
                            <CardTitle class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">Top performers</CardTitle>
                        </div>
                        <Badge :variant="leaderboardTab === 'top' ? 'success' : 'danger'">
                            {{ leaderboardTab === 'top' ? 'Top 5' : 'Bottom 5' }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Snapshot performer terbaik dan terendah berdasarkan filter aktif.
                    </p>
                </CardHeader>
                <CardContent class="space-y-3 pt-4">
                    <template v-if="dashboardStore.isLoadingDashboard">
                        <Skeleton v-for="i in 3" :key="i" class="h-24 rounded-2xl" />
                    </template>
                    <template v-else-if="topHighlights.length">
                        <!-- Top 3 cards -->
                        <div class="grid gap-3">
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
                                        <div class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ row.normalized_score }}</div>
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
                        </div>

                        <!-- Mini Top 5 / Bottom 5 tabs -->
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Mini leaderboard</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">Top 5 &amp; Bottom 5</div>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                    live
                                </span>
                            </div>

                            <!-- Tab buttons -->
                            <div class="mb-3 flex gap-2">
                                <button
                                    type="button"
                                    :class="[
                                        'flex-1 rounded-xl border py-1.5 text-xs font-semibold transition',
                                        leaderboardTab === 'top'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400',
                                    ]"
                                    @click="leaderboardTab = 'top'"
                                >
                                    Top 5
                                </button>
                                <button
                                    type="button"
                                    :class="[
                                        'flex-1 rounded-xl border py-1.5 text-xs font-semibold transition',
                                        leaderboardTab === 'bottom'
                                            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300'
                                            : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400',
                                    ]"
                                    @click="leaderboardTab = 'bottom'"
                                >
                                    Bottom 5
                                </button>
                            </div>

                            <!-- Rows with transition animation -->
                            <TransitionGroup tag="div" name="slide-fade" class="space-y-2">
                                <button
                                    v-for="(row, idx) in leaderboardMiniRows"
                                    :key="`${leaderboardTab}-${row.user?.id}`"
                                    type="button"
                                    :class="[
                                        'flex w-full items-center justify-between rounded-2xl border px-3 py-2.5 text-left transition',
                                        leaderboardTab === 'top'
                                            ? 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-900 dark:hover:bg-emerald-950/20'
                                            : 'border-slate-200 bg-white hover:border-rose-200 hover:bg-rose-50/50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-rose-900 dark:hover:bg-rose-950/20',
                                    ]"
                                    @click="openDetail(row.user?.id)"
                                >
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span
                                            :class="[
                                                'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[11px] font-bold',
                                                idx === 0 && leaderboardTab === 'top'
                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'
                                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                                            ]"
                                        >{{ row.rank }}</span>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ row.user?.nama }}</div>
                                            <div class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                {{ row.role?.name ?? row.user?.role_ref?.name ?? row.user?.jabatan ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-3 shrink-0 text-right">
                                        <div
                                            :class="[
                                                'text-sm font-bold tabular-nums',
                                                row.normalized_score >= 80 ? 'text-emerald-600 dark:text-emerald-400'
                                                    : row.normalized_score >= 60 ? 'text-amber-600 dark:text-amber-400'
                                                    : 'text-rose-600 dark:text-rose-400',
                                            ]"
                                        >{{ row.normalized_score }}</div>
                                        <div class="text-[10px] text-slate-400">score</div>
                                    </div>
                                </button>
                            </TransitionGroup>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada leaderboard untuk filter ini.
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>

        <!-- ── Team bar + Doughnut ────────────────────────────────────────────── -->
        <div class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_1fr]">
            <Card class="overflow-hidden rounded-2xl border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <CardHeader class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Team performance</div>
                            <CardTitle class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">Skor tim teratas</CardTitle>
                            <p class="mt-1 max-w-xl text-xs leading-5 text-slate-500 dark:text-slate-400">
                                Perbandingan cepat untuk 8 karyawan dengan score tertinggi.
                            </p>
                        </div>
                        <Badge class="shrink-0" variant="outline">Bar chart</Badge>
                    </div>
                </CardHeader>
                <CardContent class="px-5 py-4">
                    <template v-if="dashboardStore.isLoadingDashboard">
                        <Skeleton class="h-64 rounded-2xl" />
                    </template>
                    <template v-else-if="!teamPerformanceChart.labels.length">
                        <div class="flex h-64 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-800">
                            Belum ada data performa tim.
                        </div>
                    </template>
                    <BarChart
                        v-else
                        :key="teamPerformanceKey"
                        :labels="teamPerformanceChart.labels"
                        :datasets="teamPerformanceChart.datasets"
                        :height="Math.max(245, teamPerformanceChart.labels.length * 34)"
                        horizontal
                        :show-legend="false"
                        :x-max="100"
                        y-label="KPI Score"
                        :animation-duration="980"
                        :delay-step="40"
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
                            :key="statusDistributionKey"
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

        <!-- ── Full leaderboard table ─────────────────────────────────────────── -->
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

<style scoped>
.slide-fade-enter-active { transition: all 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
.slide-fade-leave-active { transition: all 0.18s ease-in; position: absolute; }
.slide-fade-enter-from   { opacity: 0; transform: translateY(-6px) scale(0.98); }
.slide-fade-leave-to     { opacity: 0; transform: translateY(4px) scale(0.97); }
.slide-fade-move         { transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1); }
</style>
