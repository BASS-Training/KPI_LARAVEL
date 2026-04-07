<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <div :class="cn('rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900', $props.class)">
        <slot />
    </div>
</template>
