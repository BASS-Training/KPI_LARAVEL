<script setup>
import { computed } from 'vue';
import { ArrowDown, ArrowLeft, ArrowRight, ArrowUp, ArrowUpDown, Eye, Medal } from 'lucide-vue-next';
import Skeleton from '@/components/ui/Skeleton.vue';
import KpiStatusBadge from '@/components/kpi-dashboard/KpiStatusBadge.vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    loading: Boolean,
    page: { type: Number, default: 1 },
    perPage: { type: Number, default: 8 },
    sortField: { type: String, default: 'normalized_score' },
    sortDirection: { type: String, default: 'desc' },
});

const emit = defineEmits(['sort', 'update:page', 'open-detail']);

const totalPages = computed(() => Math.max(1, Math.ceil(props.rows.length / props.perPage)));

const paginatedRows = computed(() => {
    const start = (props.page - 1) * props.perPage;
    return props.rows.slice(start, start + props.perPage);
});

function rankStyle(rank) {
    if (rank === 1) return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800';
    if (rank === 2) return 'bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    if (rank === 3) return 'bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800';
    return 'bg-slate-50 text-slate-500 border border-slate-100 dark:bg-slate-900 dark:text-slate-500 dark:border-slate-800';
}

function medalTone(rank) {
    if (rank === 1) return 'text-amber-500';
    if (rank === 2) return 'text-slate-400';
    if (rank === 3) return 'text-orange-500';
    return 'text-slate-300 dark:text-slate-600';
}

function progressColor(score) {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 60) return 'bg-amber-400';
    return 'bg-rose-500';
}

function sortIcon(field) {
    if (props.sortField !== field) return null;
    return props.sortDirection === 'asc' ? ArrowUp : ArrowDown;
}

function changePage(page) {
    if (page >= 1 && page <= totalPages.value) {
        emit('update:page', page);
    }
}

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = props.page;
    const pages = [];

    for (let index = Math.max(1, current - 1); index <= Math.min(total, current + 1); index += 1) {
        pages.push(index);
    }

    return pages;
});
</script>

<template>
    <div class="space-y-3">
        <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">KPI Leaderboard</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Ranking karyawan berdasarkan skor KPI pada periode aktif.
                    </p>
                </div>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                    {{ rows.length }} karyawan
                </span>
            </div>

            <div class="grid grid-cols-[2.5rem_1fr_1fr_1fr_1fr_5rem] gap-0 border-b border-slate-100 bg-slate-50/60 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-500">
                <div>#</div>
                <div>Nama</div>
                <button
                    type="button"
                    class="flex items-center gap-1 hover:text-slate-600 dark:hover:text-slate-300"
                    @click="$emit('sort', 'role')"
                >
                    Role
                    <component :is="sortIcon('role') ?? ArrowUpDown" class="h-3 w-3" />
                </button>
                <button
                    type="button"
                    class="flex items-center gap-1 hover:text-slate-600 dark:hover:text-slate-300"
                    @click="$emit('sort', 'normalized_score')"
                >
                    KPI Score
                    <component :is="sortIcon('normalized_score') ?? ArrowUpDown" class="h-3 w-3" />
                </button>
                <div>Status</div>
                <div class="text-right">Aksi</div>
            </div>

            <template v-if="loading">
                <div v-for="index in perPage" :key="index" class="border-b border-slate-100 px-5 py-3.5 last:border-0 dark:border-slate-800">
                    <Skeleton class="h-12 rounded-xl" />
                </div>
            </template>

            <template v-else-if="paginatedRows.length">
                <div
                    v-for="row in paginatedRows"
                    :key="row.user?.id"
                    class="grid cursor-pointer grid-cols-[2.5rem_1fr_1fr_1fr_1fr_5rem] items-center gap-0 border-b border-slate-100 px-5 py-3.5 transition-colors last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/40"
                    @click="$emit('open-detail', row.user?.id)"
                >
                    <div>
                        <span
                            v-if="row.rank <= 3"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800"
                            :title="`Rank #${row.rank}`"
                        >
                            <Medal :class="['h-4 w-4', medalTone(row.rank)]" />
                        </span>
                        <span
                            v-else
                            :class="['inline-flex h-6 w-6 items-center justify-center rounded-lg text-[11px] font-bold', rankStyle(row.rank)]"
                        >
                            #{{ row.rank }}
                        </span>
                    </div>

                    <div class="flex min-w-0 items-center gap-3 pr-3">
                        <div
                            :class="[
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold',
                                row.rank === 1 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' :
                                row.rank === 2 ? 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' :
                                row.rank === 3 ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400' :
                                'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            {{ row.user?.nama?.slice(0, 1)?.toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ row.user?.nama }}
                            </p>
                            <p class="truncate text-xs text-slate-400 dark:text-slate-500">
                                {{ row.user?.email ?? row.user?.nip ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="pr-3 text-sm text-slate-600 dark:text-slate-400">
                        {{ row.role?.name ?? row.user?.role_ref?.name ?? row.user?.jabatan ?? '-' }}
                    </div>

                    <div class="space-y-1.5 pr-3">
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ row.normalized_score }}
                            </span>
                            <span class="text-xs text-slate-400">/100</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                :class="['h-full rounded-full transition-all duration-700', progressColor(Number(row.normalized_score))]"
                                :style="{ width: `${Math.max(0, Math.min(100, row.normalized_score))}%` }"
                            />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <KpiStatusBadge :score="Number(row.normalized_score)" />
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">Grade {{ row.grade }}</p>
                    </div>

                    <div class="text-right">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-blue-800 dark:hover:bg-blue-950/40 dark:hover:text-blue-400"
                            @click.stop="$emit('open-detail', row.user?.id)"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            Detail
                        </button>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="flex flex-col items-center justify-center gap-3 px-5 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Tidak ada data KPI</p>
                        <p class="mt-1 text-xs text-slate-400">Coba ubah filter periode atau role yang dipilih.</p>
                    </div>
                </div>
            </template>
        </div>

        <div v-if="!loading && totalPages > 1" class="flex items-center justify-between px-1">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Halaman <span class="font-semibold text-slate-700 dark:text-slate-300">{{ page }}</span>
                dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ totalPages }}</span>
                | <span class="font-semibold text-slate-700 dark:text-slate-300">{{ rows.length }}</span> total
            </p>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="pagination-btn"
                    :disabled="page <= 1"
                    @click="changePage(page - 1)"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                </button>
                <button
                    v-for="pageNumber in visiblePages"
                    :key="pageNumber"
                    type="button"
                    :class="['pagination-btn', pageNumber === page ? 'pagination-btn--active' : '']"
                    @click="changePage(pageNumber)"
                >
                    {{ pageNumber }}
                </button>
                <button
                    type="button"
                    class="pagination-btn"
                    :disabled="page >= totalPages"
                    @click="changePage(page + 1)"
                >
                    <ArrowRight class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
