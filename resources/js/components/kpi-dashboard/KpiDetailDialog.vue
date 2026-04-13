<script setup>
import { computed, ref } from 'vue';
import { Activity, BarChart3, Target, TrendingUp } from 'lucide-vue-next';
import Dialog from '@/components/ui/Dialog.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Tabs from '@/components/ui/Tabs.vue';

const props = defineProps({
    open: Boolean,
    loading: Boolean,
    detail: { type: Object, default: null },
});

defineEmits(['update:open']);

const activeTab = ref('breakdown');
const tabs = [
    { value: 'breakdown', label: 'Breakdown Indikator' },
    { value: 'summary', label: 'Ringkasan' },
];

const indicators = computed(() => props.detail?.breakdown ?? []);

// Score ring (r=38, circumference ≈ 238.8)
const CIRC = 238.8;
const scoreRingOffset = computed(() => {
    const score = Number(props.detail?.normalized_score ?? 0);
    return CIRC - (Math.min(100, score) / 100) * CIRC;
});

function ringColor(score) {
    if (score >= 80) return '#10b981';   // emerald-500
    if (score >= 60) return '#f59e0b';   // amber-400
    return '#f43f5e';                    // rose-500
}

function progressColor(score) {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 60) return 'bg-amber-400';
    return 'bg-rose-500';
}

function gradeColor(grade) {
    const map = { A: 'text-emerald-600 dark:text-emerald-400', B: 'text-blue-600 dark:text-blue-400', C: 'text-amber-600 dark:text-amber-400', D: 'text-orange-600 dark:text-orange-400', E: 'text-rose-600 dark:text-rose-400' };
    return map[grade] ?? 'text-slate-600 dark:text-slate-400';
}
</script>

<template>
    <Dialog
        :open="open"
        title=""
        description=""
        class="max-w-3xl rounded-2xl border-slate-200 bg-white p-0 dark:border-slate-800 dark:bg-slate-950"
        @update:open="$emit('update:open', $event)"
    >
        <!-- Header ─────────────────────────────────────────────────────── -->
        <div class="border-b border-slate-200/70 px-6 py-5 dark:border-slate-800">
            <template v-if="loading">
                <Skeleton class="h-5 w-48 rounded-lg" />
                <Skeleton class="mt-2 h-3 w-64 rounded-lg" />
            </template>
            <template v-else-if="detail">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Name + period -->
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ detail.user?.nama }}
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ detail.role?.name ?? detail.user?.jabatan ?? '—' }}
                            &bull;
                            {{ detail.period_start }} – {{ detail.period_end }}
                        </p>
                    </div>

                    <!-- Score ring + grade ─────────────────────────────── -->
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <svg class="h-[72px] w-[72px] -rotate-90" viewBox="0 0 88 88" fill="none">
                                <circle cx="44" cy="44" r="38" stroke-width="7" class="stroke-slate-100 dark:stroke-slate-800" />
                                <circle
                                    cx="44" cy="44" r="38" stroke-width="7"
                                    stroke-linecap="round"
                                    :stroke="ringColor(Number(detail.normalized_score))"
                                    :stroke-dasharray="CIRC"
                                    :stroke-dashoffset="scoreRingOffset"
                                    style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4,0,0.2,1)"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-bold leading-none text-slate-900 dark:text-white">
                                    {{ detail.normalized_score }}
                                </span>
                                <span class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">score</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Grade</div>
                            <div :class="['text-3xl font-black', gradeColor(detail.grade)]">
                                {{ detail.grade }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Body ──────────────────────────────────────────────────────── -->
        <div class="px-6 py-5">
            <Tabs v-model="activeTab" :tabs="tabs">
                <template #default="{ value }">

                    <!-- ── Loading ─────────────────────────────────────── -->
                    <div v-if="loading" class="space-y-3">
                        <Skeleton v-for="i in 3" :key="i" class="h-28 rounded-2xl" />
                    </div>

                    <!-- ── Summary tab ────────────────────────────────── -->
                    <div v-else-if="value === 'summary' && detail" class="grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="stat in [
                                { label: 'Normalized', value: detail.normalized_score, icon: Activity, color: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-50 dark:bg-blue-950/30' },
                                { label: 'Raw Score', value: detail.raw_score, icon: BarChart3, color: 'text-purple-600 dark:text-purple-400', bg: 'bg-purple-50 dark:bg-purple-950/30' },
                                { label: 'Indikator', value: indicators.length, icon: Target, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/30' },
                            ]"
                            :key="stat.label"
                            :class="['flex items-center gap-3 rounded-2xl border border-slate-100 p-4 dark:border-slate-800', stat.bg]"
                        >
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-xl bg-white dark:bg-slate-900', stat.color]">
                                <component :is="stat.icon" class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    {{ stat.label }}
                                </div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stat.value }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Breakdown tab ──────────────────────────────── -->
                    <div v-else-if="value === 'breakdown' && detail" class="space-y-3">
                        <div
                            v-for="ind in indicators"
                            :key="ind.indicator_id"
                            class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 transition-colors hover:border-slate-200 hover:bg-white dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-slate-700 dark:hover:bg-slate-900"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <!-- Left: name + desc + weight -->
                                <div class="min-w-0 space-y-1">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ ind.name }}</p>
                                    <p v-if="ind.description" class="text-xs leading-5 text-slate-500 dark:text-slate-400">
                                        {{ ind.description }}
                                    </p>
                                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                        <TrendingUp class="h-3 w-3" />
                                        Bobot {{ ind.weight }}
                                    </span>
                                </div>

                                <!-- Right: target / actual / score -->
                                <div class="grid min-w-[180px] grid-cols-3 gap-2 rounded-xl border border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                                    <div class="text-center">
                                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Target</div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ ind.target_value }}</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Aktual</div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ ind.actual_value }}</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Score</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ ind.score }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress bar with % label ──────────────── -->
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                    <span>Achievement</span>
                                    <span>{{ ind.achievement_ratio }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div
                                        :class="['h-full rounded-full transition-all duration-700', progressColor(Number(ind.achievement_ratio))]"
                                        :style="{ width: `${Math.max(0, Math.min(100, ind.achievement_ratio))}%` }"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-if="!indicators.length" class="py-8 text-center text-sm text-slate-400">
                            Belum ada indikator untuk periode ini.
                        </div>
                    </div>

                </template>
            </Tabs>
        </div>
    </Dialog>
</template>
