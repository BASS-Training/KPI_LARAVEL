<script setup>
const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    badge: { type: String, default: '' },
    color: {
        type: String,
        default: 'default',
        // default | success | warning | danger | info
    },
    icon: { type: String, default: '' },
});

const valueColorMap = {
    default: 'text-slate-900',
    success: 'text-green-700',
    warning: 'text-amber-700',
    danger: 'text-red-700',
    info: 'text-blue-700',
};

const badgeClassMap = {
    default: 'badge-neutral',
    success: 'badge-success',
    warning: 'badge-warning',
    danger: 'badge-danger',
    info: 'badge-info',
};
</script>

<template>
    <div class="stat-card">
        <div class="stat-label">{{ label }}</div>
        <div class="mt-3 flex items-end justify-between gap-3">
            <div :class="['text-3xl font-bold leading-none', valueColorMap[color]]">
                {{ value }}
            </div>
            <div v-if="badge" :class="badgeClassMap[color]">
                {{ badge }}
            </div>
            <div v-else-if="sub" class="text-xs text-slate-500 pb-1">{{ sub }}</div>
        </div>
        <div v-if="badge && sub" class="mt-2 text-xs text-slate-500">{{ sub }}</div>
    </div>
</template>
