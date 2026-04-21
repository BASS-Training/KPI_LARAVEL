<script setup>
const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
    trend: { type: [String, Number], default: '' },
    tone: { type: String, default: 'neutral' },
});

const toneMap = {
    neutral: {
        accent: 'bg-slate-900',
        value: 'text-slate-950',
        trend: 'text-slate-500 bg-slate-100',
    },
    excellent: {
        accent: 'bg-emerald-500',
        value: 'text-emerald-700',
        trend: 'text-emerald-700 bg-emerald-50',
    },
    good: {
        accent: 'bg-blue-500',
        value: 'text-blue-700',
        trend: 'text-blue-700 bg-blue-50',
    },
    average: {
        accent: 'bg-amber-500',
        value: 'text-amber-700',
        trend: 'text-amber-700 bg-amber-50',
    },
    bad: {
        accent: 'bg-red-500',
        value: 'text-red-700',
        trend: 'text-red-700 bg-red-50',
    },
};
</script>

<template>
    <article class="metric-card">
        <div :class="['metric-card-accent', toneMap[tone]?.accent || toneMap.neutral.accent]" />
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="stat-label">{{ label }}</p>
                <p :class="['mt-3 truncate text-3xl font-bold tracking-tight', toneMap[tone]?.value || toneMap.neutral.value]">
                    {{ value }}
                </p>
            </div>
            <div v-if="$slots.icon" class="metric-card-icon">
                <slot name="icon" />
            </div>
        </div>
        <div class="mt-4 flex min-h-5 items-center justify-between gap-3">
            <p v-if="hint" class="truncate text-xs text-slate-500">{{ hint }}</p>
            <span v-if="trend" :class="['shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold', toneMap[tone]?.trend || toneMap.neutral.trend]">
                {{ trend }}
            </span>
        </div>
    </article>
</template>
