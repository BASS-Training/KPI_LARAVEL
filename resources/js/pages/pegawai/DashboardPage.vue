<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
    Activity, AlertCircle, ArrowRight, BarChart2, CheckCircle2,
    ChevronRight, Clock, RefreshCw, Star, TrendingUp, User, Zap,
} from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import { useKpiStore } from '@/stores/kpi';
import { useTaskStore } from '@/stores/task';
import { useKpiColor } from '@/composables/useKpiColor';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const auth = useAuthStore();
const kpiStore = useKpiStore();
const taskStore = useTaskStore();
const router = useRouter();
const { getPredikat } = useKpiColor();

const user = computed(() => auth.user);
const myKpi = computed(() => kpiStore.myKpi);
const recentTasks = computed(() => taskStore.tasks.slice(0, 5));
const ranking = computed(() => kpiStore.ranking.slice(0, 5));
const currentUserId = computed(() => auth.user?.id);

const now = new Date();
const bulanLabel = now.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

const predikat = computed(() => {
    if (!myKpi.value) return { label: '-', color: 'default' };
    return getPredikat(myKpi.value.total_score ?? 0);
});

const myRank = computed(() => {
    if (!currentUserId.value) return null;
    return kpiStore.ranking.find((item) => item.user_id === currentUserId.value) || null;
});

// Metric cards — mirrors analytics style with big bold numbers
const metricCards = computed(() => {
    const score = myKpi.value?.total_score ?? 0;
    return [
        {
            label: 'Nilai KPI',
            value: score,
            sub: predikat.value.label,
            color: score >= 80 ? 'emerald' : score >= 60 ? 'amber' : 'rose',
            icon: Activity,
        },
        {
            label: 'Total Pekerjaan',
            value: myKpi.value?.task_count ?? 0,
            sub: 'bulan ini',
            color: 'blue',
            icon: BarChart2,
        },
        {
            label: 'Selesai',
            value: myKpi.value?.done_count ?? 0,
            sub: myKpi.value?.task_count ? `${Math.round(((myKpi.value.done_count ?? 0) / myKpi.value.task_count) * 100)}% selesai` : '—',
            color: 'emerald',
            icon: CheckCircle2,
        },
        {
            label: 'Ranking',
            value: myRank.value ? `#${myRank.value.rank}` : '-',
            sub: 'dibanding pegawai lain',
            color: 'violet',
            icon: Star,
        },
    ];
});

const problemItems = computed(() => [
    {
        label: 'Delay',
        value: myKpi.value?.delay_count ?? 0,
        color: (myKpi.value?.delay_count ?? 0) > 0 ? 'rose' : 'emerald',
        icon: Clock,
    },
    {
        label: 'Error',
        value: myKpi.value?.error_count ?? 0,
        color: (myKpi.value?.error_count ?? 0) > 0 ? 'rose' : 'emerald',
        icon: AlertCircle,
    },
    {
        label: 'Komplain',
        value: myKpi.value?.complaint_count ?? 0,
        color: (myKpi.value?.complaint_count ?? 0) > 0 ? 'amber' : 'emerald',
        icon: Zap,
    },
]);

const statusColorMap = {
    Selesai: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
    'Dalam Proses': 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
    Pending: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
};

async function fetchAll() {
    await Promise.all([
        kpiStore.fetchMyKpi(),
        kpiStore.fetchRanking(),
        taskStore.fetchTasks(),
    ]);
}

onMounted(fetchAll);
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(fetchAll, { interval: 30_000 });

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function scoreBarColor(score) {
    if (score >= 4) return 'bg-emerald-500';
    if (score >= 3) return 'bg-blue-500';
    if (score >= 2) return 'bg-amber-500';
    return 'bg-rose-500';
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-6 shadow-sm dark:border-slate-800">
            <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-blue-500/20 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-8 left-8 h-36 w-36 rounded-full bg-emerald-500/15 blur-3xl" />

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-xl">
                    <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white/75">
                        Dashboard Individu
                    </span>
                    <h1 class="mt-3 text-2xl font-bold text-white md:text-3xl">
                        Halo, {{ user?.nama ?? 'Pegawai' }}
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-white/65">
                        Pantau skor KPI, progres pekerjaan, dan indikator kualitas kerja Anda untuk periode {{ bulanLabel }}.
                    </p>
                </div>

                <div class="flex items-center gap-3 self-start sm:self-auto">
                    <span v-if="lastUpdated" class="text-[11px] text-white/50">
                        {{ formatTime(lastUpdated) }}
                    </span>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-white/70 transition hover:bg-white/20 hover:text-white"
                        :class="{ 'animate-spin': isRefreshing }"
                        title="Refresh data"
                        @click="refresh"
                    >
                        <RefreshCw class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </section>

        <!-- Analytics-style metric cards (big bold numbers) -->
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <template v-if="kpiStore.isLoading">
                <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-2xl" />
            </template>
            <template v-else>
                <div
                    v-for="card in metricCards"
                    :key="card.label"
                    class="group relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900"
                    :class="{
                        'border-blue-100 dark:border-blue-900/40':   card.color === 'blue',
                        'border-emerald-100 dark:border-emerald-900/40': card.color === 'emerald',
                        'border-amber-100 dark:border-amber-900/40': card.color === 'amber',
                        'border-rose-100 dark:border-rose-900/40':   card.color === 'rose',
                        'border-violet-100 dark:border-violet-900/40': card.color === 'violet',
                    }"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ card.label }}</p>
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                            :class="{
                                'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400':       card.color === 'blue',
                                'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400': card.color === 'emerald',
                                'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400':   card.color === 'amber',
                                'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400':       card.color === 'rose',
                                'bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400': card.color === 'violet',
                            }"
                        >
                            <component :is="card.icon" class="h-4 w-4" />
                        </div>
                    </div>
                    <p
                        class="mt-2 text-3xl font-bold tabular-nums tracking-tight"
                        :class="{
                            'text-blue-600 dark:text-blue-400':       card.color === 'blue',
                            'text-emerald-600 dark:text-emerald-400': card.color === 'emerald',
                            'text-amber-600 dark:text-amber-400':     card.color === 'amber',
                            'text-rose-600 dark:text-rose-400':       card.color === 'rose',
                            'text-violet-600 dark:text-violet-400':   card.color === 'violet',
                        }"
                    >
                        {{ card.value }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ card.sub }}</p>
                </div>
            </template>
        </div>

        <!-- Main content grid -->
        <div class="grid gap-6 xl:grid-cols-[1.4fr_0.95fr]">

            <!-- KPI Components -->
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Performa Saya</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Komponen penilaian KPI</h3>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Bobot dan skor tiap komponen evaluasi bulanan.</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        <Activity class="h-5 w-5" />
                    </div>
                </div>
                <div class="p-6">
                    <template v-if="kpiStore.isLoading">
                        <div class="space-y-3">
                            <Skeleton v-for="i in 4" :key="i" class="h-16 rounded-2xl" />
                        </div>
                    </template>
                    <template v-else-if="myKpi?.components?.length">
                        <div class="space-y-3">
                            <div
                                v-for="component in myKpi.components"
                                :key="component.id"
                                class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ component.name }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        Bobot {{ Math.round((component.bobot ?? 0) * 100) }}%
                                    </div>
                                </div>
                                <div class="w-36 shrink-0">
                                    <div class="mb-1.5 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                                        <span>Skor</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ component.score ?? 0 }}</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                        <div
                                            class="h-2 rounded-full transition-all duration-700"
                                            :class="scoreBarColor(component.score ?? 0)"
                                            :style="{ width: Math.min(100, ((component.score ?? 0) / 5) * 100) + '%' }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex h-40 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-700">
                        Belum ada komponen KPI yang terhubung.
                    </div>
                </div>
            </div>

            <!-- Right column -->
            <div class="space-y-6">

                <!-- Quick actions -->
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Kontrol Cepat</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Aksi pekerjaan</h3>
                    </div>
                    <div class="space-y-2 p-5">
                        <button
                            class="flex w-full items-center justify-between rounded-2xl bg-blue-600 px-4 py-3 text-left text-sm font-semibold text-white transition hover:bg-blue-700 active:scale-[0.98]"
                            @click="router.push('/pekerjaan')"
                        >
                            <span>Input pekerjaan baru</span>
                            <ArrowRight class="h-4 w-4 opacity-70" />
                        </button>
                        <button
                            class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                            @click="router.push('/pekerjaan')"
                        >
                            <span>Lihat dan edit riwayat</span>
                            <ChevronRight class="h-4 w-4 opacity-50" />
                        </button>
                    </div>
                </div>

                <!-- Problem indicators -->
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Kualitas Kerja</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Indikator masalah</h3>
                    </div>
                    <div class="grid grid-cols-3 gap-3 p-5">
                        <div
                            v-for="item in problemItems"
                            :key="item.label"
                            class="rounded-2xl border p-4 text-center"
                            :class="{
                                'border-emerald-100 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20': item.color === 'emerald',
                                'border-rose-100 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20':           item.color === 'rose',
                                'border-amber-100 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20':       item.color === 'amber',
                            }"
                        >
                            <component
                                :is="item.icon"
                                class="mx-auto mb-2 h-5 w-5"
                                :class="{
                                    'text-emerald-500': item.color === 'emerald',
                                    'text-rose-500':    item.color === 'rose',
                                    'text-amber-500':   item.color === 'amber',
                                }"
                            />
                            <div
                                class="text-2xl font-bold tabular-nums"
                                :class="{
                                    'text-emerald-700 dark:text-emerald-300': item.color === 'emerald',
                                    'text-rose-700 dark:text-rose-300':       item.color === 'rose',
                                    'text-amber-700 dark:text-amber-300':     item.color === 'amber',
                                }"
                            >{{ item.value }}</div>
                            <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ item.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom row: recent tasks + ranking -->
        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">

            <!-- Recent tasks -->
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Aktivitas</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Pekerjaan terbaru</h3>
                    </div>
                    <button
                        class="flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-600 dark:border-slate-700 dark:text-slate-400 dark:hover:text-blue-400"
                        @click="router.push('/pekerjaan')"
                    >
                        Kelola semua
                        <ArrowRight class="h-3 w-3" />
                    </button>
                </div>
                <div class="p-6">
                    <template v-if="taskStore.isLoading">
                        <div class="space-y-3">
                            <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                        </div>
                    </template>
                    <template v-else-if="recentTasks.length">
                        <div class="space-y-2">
                            <div
                                v-for="task in recentTasks"
                                :key="task.id"
                                class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ task.judul }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatDate(task.tanggal) }} · {{ task.jenis_pekerjaan || '-' }}
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold"
                                    :class="statusColorMap[task.status] ?? 'bg-slate-50 text-slate-500 border-slate-200'"
                                >
                                    {{ task.status }}
                                </span>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex h-40 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-700">
                        Belum ada pekerjaan pada bulan ini.
                    </div>
                </div>
            </div>

            <!-- Ranking -->
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Ranking</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Posisi terbaik bulan ini</h3>
                </div>
                <div class="p-5">
                    <template v-if="kpiStore.isLoading">
                        <div class="space-y-2">
                            <Skeleton v-for="i in 5" :key="i" class="h-14 rounded-2xl" />
                        </div>
                    </template>
                    <template v-else-if="ranking.length">
                        <div class="space-y-2">
                            <div
                                v-for="item in ranking"
                                :key="item.user_id"
                                class="flex items-center gap-3 rounded-2xl border px-3 py-2.5 transition"
                                :class="item.user_id === currentUserId
                                    ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30'
                                    : 'border-slate-100 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-800/40'"
                            >
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                                    :class="item.rank === 1
                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'
                                        : item.rank === 2
                                            ? 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                            : item.rank === 3
                                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400'
                                                : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                                >
                                    {{ item.rank }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="truncate text-sm font-semibold"
                                        :class="item.user_id === currentUserId
                                            ? 'text-blue-700 dark:text-blue-300'
                                            : 'text-slate-900 dark:text-slate-100'"
                                    >
                                        {{ item.name }}
                                        <span v-if="item.user_id === currentUserId" class="ml-1 text-[10px] font-normal text-blue-500">(Anda)</span>
                                    </div>
                                    <div class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">{{ item.position }}</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="text-sm font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ item.kpi_score }}</div>
                                    <div class="text-[10px] text-slate-400">KPI</div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex h-40 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-700">
                        Ranking belum tersedia.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
