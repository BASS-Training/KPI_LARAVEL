<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useKpiStore } from '@/stores/kpi';
import { useTaskStore } from '@/stores/task';
import { useKpiColor } from '@/composables/useKpiColor';
import AppLayout from '@/components/layout/AppLayout.vue';
import StatCard from '@/components/shared/StatCard.vue';
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
    if (!myKpi.value) {
        return { label: '-', color: 'default' };
    }

    return getPredikat(myKpi.value.total_score ?? 0);
});

const myRank = computed(() => {
    if (!currentUserId.value) {
        return null;
    }

    return kpiStore.ranking.find((item) => item.user_id === currentUserId.value) || null;
});

const problemSummary = computed(() => [
    { label: 'Delay', value: myKpi.value?.delay_count ?? 0, badge: 'badge-danger' },
    { label: 'Error', value: myKpi.value?.error_count ?? 0, badge: 'badge-danger' },
    { label: 'Komplain', value: myKpi.value?.complaint_count ?? 0, badge: 'badge-warning' },
]);

const statusBadgeMap = {
    Selesai: 'badge-success',
    'Dalam Proses': 'badge-info',
    Pending: 'badge-warning',
};

onMounted(async () => {
    await Promise.all([
        kpiStore.fetchMyKpi(),
        kpiStore.fetchRanking(),
        taskStore.fetchTasks(),
    ]);
});

function formatDate(value) {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">Dashboard Individu</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">
                        Ringkasan KPI {{ user?.nama || 'Pegawai' }}
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Pantau skor KPI, progres pekerjaan, dan indikator kualitas kerja Anda untuk periode {{ bulanLabel }}.
                    </p>
                </div>

                <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[380px]">
                    <div class="rounded-xl border border-white/12 bg-white/10 p-3.5 backdrop-blur-sm">
                        <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-white/55">Nilai KPI</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ myKpi?.total_score ?? '-' }}</div>
                        <div class="mt-0.5 text-[11px] text-white/55">Skor bulan ini</div>
                    </div>
                    <div class="rounded-xl border border-white/12 bg-white/10 p-3.5 backdrop-blur-sm">
                        <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-white/55">Predikat</div>
                        <div class="mt-2 text-xl font-bold text-white">{{ predikat.label }}</div>
                        <div class="mt-0.5 text-[11px] text-white/55">Evaluasi performa</div>
                    </div>
                    <div class="rounded-xl border border-white/12 bg-white/10 p-3.5 backdrop-blur-sm">
                        <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-white/55">Ranking</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ myRank ? `#${myRank.rank}` : '-' }}</div>
                        <div class="mt-0.5 text-[11px] text-white/55">Dibanding pegawai lain</div>
                    </div>
                </div>
            </div>
        </section>

        <template v-if="kpiStore.isLoading && taskStore.isLoading">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <Skeleton v-for="i in 6" :key="i" class="h-28 rounded-[24px]" />
            </div>
        </template>

        <template v-else>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <StatCard label="Total Pekerjaan" :value="myKpi?.task_count ?? 0" sub="bulan ini" />
                <StatCard label="Selesai" :value="myKpi?.done_count ?? 0" badge="Tuntas" color="success" />
                <StatCard label="Nilai KPI" :value="myKpi?.total_score ?? 0" :badge="predikat.label" :color="predikat.color" />
                <StatCard label="Delay" :value="myKpi?.delay_count ?? 0" :color="(myKpi?.delay_count ?? 0) > 0 ? 'danger' : 'success'" />
                <StatCard label="Error" :value="myKpi?.error_count ?? 0" :color="(myKpi?.error_count ?? 0) > 0 ? 'danger' : 'success'" />
                <StatCard label="Komplain" :value="myKpi?.complaint_count ?? 0" :color="(myKpi?.complaint_count ?? 0) > 0 ? 'warning' : 'success'" />
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.35fr_0.95fr]">
                <section class="dashboard-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <p class="section-heading">Performa Saya</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-900">Komponen penilaian KPI</h3>
                        <p class="mt-1 text-sm text-slate-500">Lihat bobot dan skor tiap komponen KPI yang menjadi dasar evaluasi bulanan.</p>
                    </div>

                    <div class="p-6">
                        <template v-if="kpiStore.isLoading">
                            <div class="space-y-3">
                                <Skeleton v-for="i in 4" :key="i" class="h-16 rounded-2xl" />
                            </div>
                        </template>
                        <template v-else-if="myKpi?.components?.length">
                            <div class="space-y-3">
                                <div v-for="component in myKpi.components" :key="component.id" class="data-row">
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-slate-900">{{ component.name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Bobot {{ Math.round((component.bobot ?? 0) * 100) }}% dari total KPI
                                        </div>
                                    </div>
                                    <div class="w-full max-w-[220px]">
                                        <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                                            <span>Skor</span>
                                            <span class="font-semibold text-slate-800">{{ component.score ?? 0 }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-200">
                                            <div
                                                class="h-2 rounded-full transition-all duration-500"
                                                :class="(component.score ?? 0) >= 4 ? 'bg-emerald-500' : (component.score ?? 0) >= 3 ? 'bg-blue-500' : (component.score ?? 0) >= 2 ? 'bg-amber-500' : 'bg-red-500'"
                                                :style="{ width: Math.min(100, ((component.score ?? 0) / 5) * 100) + '%' }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="py-10 text-center text-sm text-slate-400">
                            Belum ada komponen KPI yang terhubung.
                        </div>
                    </div>
                </section>

                <div class="space-y-5">
                    <section class="dashboard-panel overflow-hidden">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <p class="section-heading">Kontrol Cepat</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-900">Aksi pekerjaan</h3>
                        </div>
                        <div class="space-y-3 p-6">
                            <button class="btn-primary w-full" @click="router.push('/pekerjaan')">Input pekerjaan baru</button>
                            <button class="btn-secondary w-full" @click="router.push('/pekerjaan')">Lihat dan edit riwayat pekerjaan</button>
                        </div>
                    </section>

                    <section class="dashboard-panel overflow-hidden">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <p class="section-heading">Kualitas Kerja</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-900">Indikator masalah</h3>
                        </div>
                        <div class="space-y-3 p-6">
                            <div v-for="item in problemSummary" :key="item.label" class="data-row">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ item.label }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Jumlah kejadian pada periode aktif</div>
                                </div>
                                <span :class="item.badge">{{ item.value }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="dashboard-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="section-heading">Aktivitas</p>
                            <h3 class="mt-2 text-xl font-bold text-slate-900">Pekerjaan terbaru</h3>
                        </div>
                        <button class="text-sm font-semibold text-[var(--primary-light)]" @click="router.push('/pekerjaan')">Kelola semua</button>
                    </div>

                    <div class="p-6">
                        <template v-if="taskStore.isLoading">
                            <div class="space-y-3">
                                <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                            </div>
                        </template>
                        <template v-else-if="recentTasks.length">
                            <div class="space-y-3">
                                <div v-for="task in recentTasks" :key="task.id" class="data-row">
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-slate-900">{{ task.judul }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ formatDate(task.tanggal) }} · {{ task.jenis_pekerjaan || '-' }}
                                        </div>
                                    </div>
                                    <span :class="statusBadgeMap[task.status] || 'badge-neutral'">{{ task.status }}</span>
                                </div>
                            </div>
                        </template>
                        <div v-else class="py-10 text-center text-sm text-slate-400">
                            Belum ada pekerjaan pada bulan ini.
                        </div>
                    </div>
                </section>

                <section class="dashboard-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <p class="section-heading">Ranking</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-900">Posisi terbaik bulan ini</h3>
                    </div>

                    <div class="p-6">
                        <template v-if="kpiStore.isLoading">
                            <div class="space-y-3">
                                <Skeleton v-for="i in 5" :key="i" class="h-14 rounded-2xl" />
                            </div>
                        </template>
                        <template v-else-if="ranking.length">
                            <div class="space-y-3">
                                <div
                                    v-for="item in ranking"
                                    :key="item.user_id"
                                    class="data-row"
                                    :class="item.user_id === currentUserId ? '!border-blue-200 !bg-blue-50' : ''"
                                >
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
                                        <div class="text-[11px] text-slate-500">nilai KPI</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="py-10 text-center text-sm text-slate-400">
                            Ranking belum tersedia.
                        </div>
                    </div>
                </section>
            </div>
        </template>
    </AppLayout>
</template>
