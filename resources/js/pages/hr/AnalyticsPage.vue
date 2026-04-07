<script setup>
import { ref, onMounted } from 'vue';
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

// ── Chart data mappers ──────────────────────────────────────────────────────
const trendDatasets = () => {
    const data = store.trend;
    if (!data?.length) return { labels: [], datasets: [] };
    return {
        labels: data.map(d => d.bulan),
        datasets: [{
            label: 'Rata-rata Achievement (%)',
            data: data.map(d => parseFloat(d.avg_achievement ?? 0)),
            color: '#3b82f6',
            fill: true,
        }],
    };
};

const divisionDatasets = () => {
    const data = store.perDivision;
    if (!data?.length) return { labels: [], datasets: [] };
    return {
        labels: data.map(d => d.division_name ?? 'Tanpa Divisi'),
        datasets: [{
            label: 'Rata-rata Achievement (%)',
            data: data.map(d => parseFloat(d.avg_achievement ?? 0)),
            color: '#6366f1',
        }],
    };
};

const distributionChart = () => {
    const data = store.distribution;
    if (!data?.length) return { labels: [], data: [], colors: [] };
    const colorMap = { excellent: '#22c55e', good: '#3b82f6', average: '#f59e0b', bad: '#ef4444' };
    const labelMap = { excellent: 'Excellent', good: 'Good', average: 'Average', bad: 'Bad' };
    return {
        labels: data.map(d => labelMap[d.score_label] ?? d.score_label),
        data: data.map(d => parseInt(d.total ?? 0)),
        colors: data.map(d => colorMap[d.score_label] ?? '#94a3b8'),
    };
};

function exportKpiCsv() {
    window.open('/api/export/reports/csv', '_blank');
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
                    <div class="page-hero-meta">HR Manager</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Analytics KPI</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Visualisasi performa KPI seluruh karyawan — tren bulanan, distribusi nilai, dan perbandingan antar divisi.
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
                </div>
            </div>
        </section>

        <!-- Overview cards -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <template v-if="store.loadingOverview">
                <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-2xl" />
            </template>
            <template v-else>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Total Karyawan</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ store.overview?.total_employees ?? 0 }}</p>
                </div>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Rata-rata Achievement</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ store.overview?.avg_achievement ? parseFloat(store.overview.avg_achievement).toFixed(1) + '%' : '-' }}</p>
                </div>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Performa Baik (≥80%)</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">{{ store.overview?.good_count ?? 0 }}</p>
                </div>
                <div class="dashboard-panel p-5">
                    <p class="text-xs font-medium text-slate-500">Performa Buruk (&lt;50%)</p>
                    <p class="mt-1 text-2xl font-bold text-red-500">{{ store.overview?.bad_count ?? 0 }}</p>
                </div>
            </template>
        </section>

        <!-- Charts row 1 -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Trend line chart -->
            <div class="dashboard-panel lg:col-span-2">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Tren Bulanan</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Achievement Rate {{ selectedYear }}</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.loadingTrend" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <LineChart
                        v-else
                        :labels="trendDatasets().labels"
                        :datasets="trendDatasets().datasets"
                        title=""
                        y-label="Achievement (%)"
                        :height="260"
                    />
                </div>
            </div>

            <!-- Distribution doughnut -->
            <div class="dashboard-panel">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Distribusi Nilai</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Sebaran Score Label</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.loadingDistribution" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <DoughnutChart
                        v-else
                        :labels="distributionChart().labels"
                        :data="distributionChart().data"
                        :colors="distributionChart().colors"
                        title=""
                        :height="240"
                    />
                </div>
            </div>
        </div>

        <!-- Per-division bar chart -->
        <div class="dashboard-panel">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Perbandingan Divisi</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Rata-rata Achievement per Divisi</h3>
            </div>
            <div class="p-6">
                <div v-if="store.loadingPerDivision" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                <BarChart
                    v-else
                    :labels="divisionDatasets().labels"
                    :datasets="divisionDatasets().datasets"
                    title=""
                    y-label="Achievement (%)"
                    :height="280"
                />
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
                    <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                    </svg>
                    Export KPI Reports (CSV)
                </button>
                <button class="btn-secondary" @click="exportRankingCsv">
                    <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                    </svg>
                    Export Ranking (CSV)
                </button>
            </div>
        </div>
    </AppLayout>
</template>
