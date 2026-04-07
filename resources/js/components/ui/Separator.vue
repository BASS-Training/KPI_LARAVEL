<script setup>
import { cn } from '@/lib/utils';
defineProps({
    orientation: { type: String, default: 'horizontal' },
    class: { type: String, default: '' },
});
</script>
<template>
    <div
        role="separator"
        :class="cn(
            'shrink-0 bg-slate-200 dark:bg-slate-700',
            orientation === 'horizontal' ? 'h-px w-full' : 'h-full w-px',
            $props.class
        )"
    />
</template>
