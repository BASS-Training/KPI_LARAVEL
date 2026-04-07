<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: { type: String, default: 'default' }, // default | danger | success | warning
    class: { type: String, default: '' },
});

const variants = {
    default: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
    danger: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300',
    success: 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300',
    warning: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300',
};

const classes = computed(() => cn('rounded-lg border p-4 text-sm', variants[props.variant], props.class));
</script>

<template>
    <div role="alert" :class="classes">
        <slot />
    </div>
</template>
