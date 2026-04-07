<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        // default | success | warning | danger | outline
    },
    class: { type: String, default: '' },
});

const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';
const variants = {
    default: 'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    success: 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    warning: 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    danger: 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    outline: 'border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-300',
};

const classes = computed(() => cn(base, variants[props.variant], props.class));
</script>

<template>
    <span :class="classes"><slot /></span>
</template>
