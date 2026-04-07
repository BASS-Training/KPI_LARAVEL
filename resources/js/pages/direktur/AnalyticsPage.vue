<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAnalyticsStore } from '@/stores/analytics';
import AppLayout from '@/components/layout/AppLayout.vue';
import LineChart from '@/components/charts/LineChart.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const store = useAnalyticsStore();

const yearOptions = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const selectedYear = ref(new Date().getFullYear());

async function applyFilter() {
    store.setFilter({ year: selectedYear.value });
    await store.fetchAll();
}

onMounted(() => applyFilter());

// Chart mappers (same as HR view — reuse store data)
const trendDatasets = computed(() => {
    const data = store.trend;
    if (!data?.length) return { labels: [], datasets: [] };
    return {
        labels: data.map(d => d.bulan),
        datasets: [{
            label: 'Rata-rata Achievement (%)',
            data: data.map(d => parseFloat(d.avg_achievement ?? 0)),
            color: '#6366f1',
            fill: true,
        }],
    };
});

const divisionDatasets = computed(() => {
    const data = store.perDivision;
    if (!data?.length) return { labels: [], datasets: [] };
    return {
        labels: data.map(d => d.division_name ?? 'Tanpa Divisi'),
        datasets: [{
            label: 'Rata-rata Achievement (%)',
            data: data.map(d => parseFloat(d.avg_achievement ?? 0)),
            color: '#8b5cf6',
        }],
    };
});

const distributionChart = computed(() => {
    const data = store.distribution;
    if (!data?.length) return { labels: [], data: [], colors: [] };
    const colorMap = { excellent: '#22c55e', good: '#3b82f6', average: '#f59e0b', bad: '#ef4444' };
    const labelMap = { excellent: 'Excellent', good: 'Good', average: 'Average', bad: 'Bad' };
    return {
        labels: data.map(d => labelMap[d.score_label] ?? d.score_label),
        data: data.map(d => parseInt(d.total ?? 0)),
        colors: data.map(d => colorMap[d.score_label] ?? '#94a3b8'),
    };
});

const overviewCards = computed(() => [
    {
        label: 'Total Karyawan',
        value: store.overview?.total_employees ?? 0,
        icon: `<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>`,
        color: 'text-slate-700',
        bg: 'bg-slate-100',
    },
    {
        label: 'Avg. Achievement',
        value: store.overview?.avg_achievement ? parseFloat(store.overview.avg_achievement).toFixed(1) + '%' : '-',
        icon: `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>`,
        color: 'text-indigo-700',
        bg: 'bg-indigo-100',
    },
    {
        label: 'Score Excellent',
        value: store.overview?.excellent_count ?? 0,
        icon: `<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>`,
        color: 'text-green-700',
        bg: 'bg-green-100',
    },
    {
        label: 'Perlu Perhatian',
        value: store.overview?.bad_count ?? 0,
        icon: `<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>`,
        color: 'text-red-700',
        bg: 'bg-red-100',
    },
]);

function exportPdf(userId) {
    window.open(`/api/export/kpi/${userId}/pdf`, '_blank');
}

function exportRankingCsv() {
    window.open('/api/export/ranking/csv', '_blank');
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">Executive View</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Analytics & Insights</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Ringkasan eksekutif performa KPI organisasi — tren, distribusi, dan benchmarking antar divisi.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <select
                        v-model="selectedYear"
                        class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30"
                        @change="applyFilter"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button class="btn-primary shrink-0" @click="exportRankingCsv">Export Ranking</button>
                </div>
            </div>
        </section>

        <!-- KPI overview cards -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <template v-if="store.loadingOverview">
                <Skeleton v-for="i in 4" :key="i" class="h-28 rounded-2xl" />
            </template>
            <template v-else>
                <div
                    v-for="card in overviewCards"
                    :key="card.label"
                    class="dashboard-panel flex items-center gap-4 p-5"
                >
                    <div :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', card.bg]">
                        <svg :class="['h-5 w-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="card.icon" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
                        <p :class="['mt-0.5 text-2xl font-bold', card.color]">{{ card.value }}</p>
                    </div>
                </div>
            </template>
        </section>

        <!-- Trend + Distribution -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="dashboard-panel lg:col-span-2">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Tren Organisasi</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Achievement Rate Bulanan {{ selectedYear }}</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.loadingTrend" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <LineChart
                        v-else
                        :labels="trendDatasets.labels"
                        :datasets="trendDatasets.datasets"
                        title=""
                        y-label="Achievement (%)"
                        :height="280"
                    />
                </div>
            </div>

            <div class="dashboard-panel">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Distribusi Performa</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Proporsi Score Label</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.loadingDistribution" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <DoughnutChart
                        v-else
                        :labels="distributionChart.labels"
                        :data="distributionChart.data"
                        :colors="distributionChart.colors"
                        title=""
                        :height="240"
                    />
                </div>
            </div>
        </div>

        <!-- Division comparison -->
        <div class="dashboard-panel">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Benchmarking Divisi</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Rata-rata Achievement per Divisi</h3>
            </div>
            <div class="p-6">
                <div v-if="store.loadingPerDivision" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                <BarChart
                    v-else
                    :labels="divisionDatasets.labels"
                    :datasets="divisionDatasets.datasets"
                    title=""
                    y-label="Achievement (%)"
                    :height="300"
                />
            </div>
        </div>
    </AppLayout>
</template>
