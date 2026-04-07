<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        // default | outline | ghost | destructive | secondary | link
    },
    size: {
        type: String,
        default: 'default',
        // default | sm | lg | icon
    },
    disabled: Boolean,
    type: { type: String, default: 'button' },
    class: { type: String, default: '' },
});

const base =
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:pointer-events-none disabled:opacity-50';

const variants = {
    default: 'bg-blue-600 text-white hover:bg-blue-700',
    outline: 'border border-slate-200 bg-white text-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
    ghost: 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
    secondary: 'bg-slate-100 text-slate-900 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100',
    destructive: 'bg-red-600 text-white hover:bg-red-700',
    link: 'text-blue-600 underline-offset-4 hover:underline p-0 h-auto',
};

const sizes = {
    default: 'h-9 px-4 py-2',
    sm: 'h-7 px-3 text-xs',
    lg: 'h-11 px-6',
    icon: 'h-9 w-9',
};

const classes = computed(() => cn(base, variants[props.variant], sizes[props.size], props.class));
</script>

<template>
    <button :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
