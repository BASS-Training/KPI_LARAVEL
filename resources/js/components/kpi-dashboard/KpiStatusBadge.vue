<script setup>
import { computed } from 'vue';

const props = defineProps({
    score: { type: Number, default: 0 },
});

const state = computed(() => {
    if (props.score >= 80) return { label: 'Baik',    dot: 'bg-emerald-500', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800' };
    if (props.score >= 60) return { label: 'Cukup',   dot: 'bg-amber-400',   badge: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800' };
    return                        { label: 'Perlu Perbaikan', dot: 'bg-rose-500', badge: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800' };
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold',
            state.badge,
        ]"
    >
        <span :class="['h-1.5 w-1.5 rounded-full', state.dot]" />
        {{ state.label }}
    </span>
</template>
