<script setup>
import { computed, onMounted } from 'vue';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiColor } from '@/composables/useKpiColor';
import { useKpiStore } from '@/stores/kpi';
import AppLayout from '@/components/layout/AppLayout.vue';
import StatCard from '@/components/shared/StatCard.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const empStore = useEmployeeStore();
const kpiStore = useKpiStore();
const { getPredikat } = useKpiColor();

const ranking = computed(() => kpiStore.ranking);
const topFive = computed(() => ranking.value.slice(0, 5));
const bottomThree = computed(() => [...ranking.value].slice(-3).reverse());

const stats = computed(() => {
    const average = ranking.value.length
        ? Math.round((ranking.value.reduce((sum, item) => sum + item.kpi_score, 0) / ranking.value.length) * 10) / 10
        : 0;

    return {
        totalEmployees: empStore.total,
        avgScore: average,
        goodCount: ranking.value.filter((item) => item.kpi_score >= 4).length,
        warningCount: ranking.value.filter((item) => item.kpi_score < 3).length,
    };
});

const distribution = computed(() => {
    const buckets = [
        { label: 'Baik Sekali', color: 'bg-emerald-600', match: (score) => score >= 5 },
        { label: 'Baik', color: 'bg-blue-600', match: (score) => score >= 4 && score < 5 },
        { label: 'Cukup', color: 'bg-amber-500', match: (score) => score >= 3 && score < 4 },
        { label: 'Kurang/Buruk', color: 'bg-red-500', match: (score) => score < 3 },
    ];

    return buckets.map((bucket) => ({
        ...bucket,
        count: ranking.value.filter((item) => bucket.match(item.kpi_score)).length,
    }));
});

const maxDistribution = computed(() =>
    Math.max(...distribution.value.map((item) => item.count), 1),
);

onMounted(async () => {
    await Promise.all([
        empStore.fetchEmployees(),
        kpiStore.fetchRanking(),
    ]);
});
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">Executive Dashboard</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Ringkasan performa organisasi</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Tinjau distribusi nilai KPI, pegawai dengan performa tertinggi, dan unit yang perlu perhatian lebih lanjut.
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
            <StatCard label="Predikat Baik+" :value="stats.goodCount" color="success" />
            <StatCard label="Perlu Perhatian" :value="stats.warningCount" :color="stats.warningCount > 0 ? 'danger' : 'default'" />
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[0.9fr_1.1fr]">
            <section class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="section-heading">Distribusi Nilai</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">Komposisi predikat pegawai</h3>
                </div>

                <div class="space-y-4 p-6">
                    <template v-if="kpiStore.isLoading">
                        <Skeleton v-for="i in 4" :key="i" class="h-10 rounded-2xl" />
                    </template>
                    <template v-else>
                        <div v-for="item in distribution" :key="item.label" class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-800">{{ item.label }}</span>
                                <span class="text-slate-500">{{ item.count }} pegawai</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-200">
                                <div
                                    :class="['h-2 rounded-full transition-all duration-500', item.color]"
                                    :style="{ width: ((item.count / maxDistribution) * 100) + '%' }"
                                />
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="section-heading">Ranking Utama</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">Top performer bulan ini</h3>
                </div>

                <div class="p-6">
                    <template v-if="kpiStore.isLoading">
                        <div class="space-y-3">
                            <Skeleton v-for="i in 5" :key="i" class="h-14 rounded-2xl" />
                        </div>
                    </template>
                    <template v-else-if="topFive.length">
                        <div class="space-y-3">
                            <div v-for="item in topFive" :key="item.user_id" class="data-row">
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
                                    <div class="mt-1">
                                        <span class="badge-info">{{ getPredikat(item.kpi_score).label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <section class="dashboard-panel mt-5 overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Evaluasi</p>
                <h3 class="mt-2 text-xl font-bold text-slate-900">Pegawai yang memerlukan perhatian</h3>
            </div>

            <div class="p-6">
                <template v-if="kpiStore.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 3" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>
                <template v-else-if="bottomThree.length">
                    <div class="space-y-3">
                        <div v-for="item in bottomThree" :key="item.user_id" class="data-row">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-sm font-bold text-red-600">
                                    #{{ item.rank }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ item.position }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-base font-bold text-slate-900">{{ item.kpi_score }}</div>
                                <div class="mt-1">
                                    <span :class="item.kpi_score < 3 ? 'badge-danger' : 'badge-warning'">
                                        {{ getPredikat(item.kpi_score).label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="py-10 text-center text-sm text-slate-400">
                    Belum ada data evaluasi.
                </div>
            </div>
        </section>
    </AppLayout>
</template>
