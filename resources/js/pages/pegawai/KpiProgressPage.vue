<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useKpiColor } from '@/composables/useKpiColor';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import ScoreBadge from '@/components/shared/ScoreBadge.vue';
import api from '@/services/api';

const auth = useAuthStore();
const { getPredikat, getProgressClass, formatPct } = useKpiColor();

// ── Filter ─────────────────────────────────────────────────────────────────────
const filterTahun = ref(new Date().getFullYear());
const years = [new Date().getFullYear() - 1, new Date().getFullYear(), new Date().getFullYear() + 1];

const months = [
    'Januari','Februari','Maret','April','Mei','Juni',
    'Juli','Agustus','September','Oktober','November','Desember',
];

// ── Data ───────────────────────────────────────────────────────────────────────
const isLoading    = ref(false);
const monthlyData  = ref([]); // Array of { bulan, kpi, reports }

async function fetchYear() {
    isLoading.value = true;
    monthlyData.value = [];
    try {
        // Fetch KPI for each month in parallel
        const results = await Promise.allSettled(
            Array.from({ length: 12 }, (_, i) =>
                api.get('/kpi-reports', {
                    params: { bulan: i + 1, tahun: filterTahun.value, per_page: 100 },
                }).then(r => ({ bulan: i + 1, reports: r.data.data?.items ?? r.data.data ?? [] }))
            )
        );
        monthlyData.value = results.map((res, i) => {
            if (res.status === 'fulfilled') return res.value;
            return { bulan: i + 1, reports: [] };
        });
    } finally {
        isLoading.value = false;
    }
}

onMounted(fetchYear);
watch(filterTahun, fetchYear);
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(fetchYear, { interval: 60_000 });

// ── Computed ───────────────────────────────────────────────────────────────────
const monthSummaries = computed(() => {
    return monthlyData.value.map(({ bulan, reports }) => {
        if (!reports.length) return { bulan, label: months[bulan - 1], count: 0, avgPct: null, score: null };
        const avgPct = reports.reduce((s, r) => s + (r.persentase ?? 0), 0) / reports.length;
        // Estimate KPI score from avg % (simple: 5 brackets)
        const score = avgPct >= 90 ? 5 : avgPct >= 75 ? 4 : avgPct >= 60 ? 3 : avgPct >= 40 ? 2 : 1;
        return { bulan, label: months[bulan - 1], count: reports.length, avgPct: Math.round(avgPct * 10) / 10, score };
    });
});

const activeMonths = computed(() => monthSummaries.value.filter(m => m.count > 0));

const overallStats = computed(() => {
    const active = activeMonths.value;
    if (!active.length) return { months: 0, totalReports: 0, avgPct: null };
    const totalReports = active.reduce((s, m) => s + m.count, 0);
    const avgPct = Math.round((active.reduce((s, m) => s + (m.avgPct ?? 0), 0) / active.length) * 10) / 10;
    return { months: active.length, totalReports, avgPct };
});

// ── Selected month detail ──────────────────────────────────────────────────────
const selectedBulan = ref(new Date().getMonth() + 1);
const selectedMonthData = computed(() =>
    monthlyData.value.find(m => m.bulan === selectedBulan.value)
);
const selectedReports = computed(() => selectedMonthData.value?.reports ?? []);

function selectMonth(bulan) {
    selectedBulan.value = bulan;
}

function formatDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

// ── Chart bar heights ──────────────────────────────────────────────────────────
const maxAvgPct = computed(() =>
    Math.max(...monthSummaries.value.map(m => m.avgPct ?? 0), 1)
);

function barHeight(pct) {
    if (pct === null) return 0;
    return Math.max(4, Math.round((pct / Math.max(maxAvgPct.value, 100)) * 100));
}

function barColorClass(pct) {
    if (pct === null) return 'bg-slate-200';
    if (pct >= 90) return 'bg-emerald-500';
    if (pct >= 75) return 'bg-blue-500';
    if (pct >= 60) return 'bg-amber-500';
    return 'bg-red-400';
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">Progress KPI Saya</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">
                        Riwayat Progress KPI {{ filterTahun }}
                    </h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Pantau tren pencapaian KPI Anda sepanjang tahun dan lihat detail per bulan.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <select v-model="filterTahun" class="rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <span v-if="lastUpdated" class="text-[11px] text-white/50">{{ formatTime(lastUpdated) }}</span>
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
                </div>
            </div>
        </section>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
            <div class="dashboard-panel p-5">
                <p class="section-heading">Bulan Aktif</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ overallStats.months }}</p>
                <p class="mt-0.5 text-xs text-slate-400">dari 12 bulan</p>
            </div>
            <div class="dashboard-panel p-5">
                <p class="section-heading">Total Laporan</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ overallStats.totalReports }}</p>
                <p class="mt-0.5 text-xs text-slate-400">semua periode</p>
            </div>
            <div class="dashboard-panel p-5 col-span-2 md:col-span-1">
                <p class="section-heading">Rata-rata Pencapaian</p>
                <p class="mt-2 text-3xl font-bold"
                   :class="(overallStats.avgPct ?? 0) >= 80 ? 'text-emerald-600' : (overallStats.avgPct ?? 0) >= 60 ? 'text-blue-600' : 'text-amber-600'">
                    {{ overallStats.avgPct !== null ? overallStats.avgPct + '%' : '-' }}
                </p>
                <p class="mt-0.5 text-xs text-slate-400">rata-rata semua bulan aktif</p>
            </div>
        </div>

        <!-- Monthly bar chart -->
        <div class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Tren Tahunan</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Pencapaian per Bulan</h3>
            </div>

            <div v-if="isLoading" class="flex items-end gap-2 px-6 py-8">
                <Skeleton v-for="i in 12" :key="i" class="flex-1 rounded-t-md" style="height: 80px;" />
            </div>

            <div v-else class="px-6 py-6">
                <!-- Bar chart -->
                <div class="flex items-end gap-1.5 md:gap-2">
                    <div
                        v-for="m in monthSummaries"
                        :key="m.bulan"
                        class="flex flex-1 cursor-pointer flex-col items-center gap-1"
                        @click="selectMonth(m.bulan)"
                    >
                        <!-- Bar -->
                        <div class="relative w-full">
                            <div
                                class="w-full rounded-t-md transition-all duration-500"
                                :class="[
                                    barColorClass(m.avgPct),
                                    m.bulan === selectedBulan ? 'ring-2 ring-offset-1 ring-blue-400' : '',
                                ]"
                                :style="{ height: barHeight(m.avgPct) + 'px', minHeight: '4px' }"
                                :title="m.avgPct !== null ? m.avgPct + '%' : 'Tidak ada data'"
                            />
                            <!-- Hover label -->
                            <div v-if="m.avgPct !== null" class="absolute -top-6 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-medium text-slate-600">
                                {{ m.avgPct }}%
                            </div>
                        </div>
                        <!-- Month label -->
                        <span class="text-[10px] font-medium" :class="m.bulan === selectedBulan ? 'text-blue-600' : 'text-slate-500'">
                            {{ m.label.slice(0, 3) }}
                        </span>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-4 flex flex-wrap gap-3 text-[11px]">
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> ≥90%</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-blue-500"></span> ≥75%</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-500"></span> ≥60%</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-400"></span> &lt;60%</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-slate-200"></span> Tidak ada data</span>
                </div>

                <!-- Month pills -->
                <div class="mt-5 flex flex-wrap gap-2">
                    <button
                        v-for="m in monthSummaries"
                        :key="m.bulan"
                        :class="[
                            'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                            m.bulan === selectedBulan
                                ? 'bg-blue-600 text-white'
                                : m.count > 0
                                    ? 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                    : 'bg-slate-50 text-slate-400 cursor-default',
                        ]"
                        @click="m.count > 0 ? selectMonth(m.bulan) : null"
                    >
                        {{ m.label.slice(0, 3) }}
                        <span v-if="m.count > 0" class="ml-1 opacity-75">({{ m.count }})</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Selected month detail -->
        <div class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Detail Bulan</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">
                    {{ months[selectedBulan - 1] }} {{ filterTahun }}
                    <span v-if="selectedReports.length" class="ml-2 text-sm font-normal text-slate-400">
                        {{ selectedReports.length }} laporan
                    </span>
                </h3>
            </div>

            <div v-if="isLoading" class="space-y-2 p-6">
                <Skeleton v-for="i in 4" :key="i" class="h-16 rounded-lg" />
            </div>

            <div v-else-if="!selectedReports.length" class="py-16 text-center text-sm text-slate-400">
                Tidak ada laporan untuk <strong>{{ months[selectedBulan - 1] }} {{ filterTahun }}</strong>.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div v-for="r in selectedReports" :key="r.id" class="px-6 py-4 hover:bg-slate-50">
                    <div class="flex items-center gap-4">
                        <!-- Pct badge -->
                        <div
                            class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl border-2 text-[11px] font-bold"
                            :class="(r.persentase ?? 0) >= 90 ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                                   : (r.persentase ?? 0) >= 75 ? 'border-blue-200 bg-blue-50 text-blue-600'
                                   : (r.persentase ?? 0) >= 60 ? 'border-amber-200 bg-amber-50 text-amber-600'
                                   : 'border-red-200 bg-red-50 text-red-600'"
                        >
                            {{ r.persentase !== null ? Math.round(r.persentase) + '%' : '-' }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ r.kpi_indicator?.name ?? `KPI #${r.kpi_indicator_id}` }}
                                </span>
                                <ScoreBadge :score-label="r.score_label" :show-pct="false" />
                                <span
                                    :class="r.status === 'approved' ? 'badge-success' : r.status === 'rejected' ? 'badge-danger' : r.status === 'submitted' ? 'badge-info' : 'badge-neutral'"
                                    class="text-[10px]"
                                >
                                    {{ r.status }}
                                </span>
                            </div>

                            <div class="mt-2 max-w-xs">
                                <div class="mb-1 flex justify-between text-[10px] text-slate-500">
                                    <span>{{ r.nilai_aktual }} / {{ r.nilai_target ?? r.kpi_indicator?.default_target_value ?? '?' }}</span>
                                    <span>{{ formatPct(r.persentase) }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-200">
                                    <div
                                        :class="['h-1.5 rounded-full transition-all', getProgressClass(r.persentase)]"
                                        :style="{ width: Math.min(100, r.persentase ?? 0) + '%' }"
                                    />
                                </div>
                            </div>

                            <div class="mt-1.5 flex items-center gap-3 text-[11px] text-slate-400">
                                <span>{{ formatDate(r.tanggal) }}</span>
                                <span class="badge-neutral text-[10px]">{{ r.period_label }}</span>
                                <a v-if="r.file_evidence_url" :href="r.file_evidence_url" target="_blank" class="text-blue-500 hover:underline">📎 Evidence</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
