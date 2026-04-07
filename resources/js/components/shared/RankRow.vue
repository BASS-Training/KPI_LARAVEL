<script setup>
import { computed } from 'vue';
import { useKpiColor } from '@/composables/useKpiColor';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';

const props = defineProps({
    rank: { type: Object, required: true }, // { rank, name, position, kpi_score, predikat }
    highlight: { type: Boolean, default: false }, // highlight baris milik user sendiri
});

const { getPredikat } = useKpiColor();
const predikat = computed(() => props.rank.predikat || getPredikat(props.rank.kpi_score));
</script>

<template>
    <div
        :class="[
            'flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors',
            highlight ? 'bg-blue-50 dark:bg-blue-950/30' : 'hover:bg-slate-50 dark:hover:bg-slate-800/30',
        ]"
    >
        <!-- Nomor ranking -->
        <span
            :class="[
                'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-medium',
                rank.rank <= 3
                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
            ]"
        >
            {{ rank.rank }}
        </span>

        <Avatar :name="rank.name" size="sm" />

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm text-slate-800 dark:text-slate-200">{{ rank.name }}</p>
            <p class="truncate text-xs text-slate-400">{{ rank.position }}</p>
        </div>

        <div class="shrink-0 text-right">
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ rank.kpi_score }}</p>
            <Badge :variant="predikat.color" class="mt-0.5">{{ predikat.label }}</Badge>
        </div>
    </div>
</template>
