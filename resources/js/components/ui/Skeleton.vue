<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <div :class="cn('animate-pulse rounded-md bg-slate-200 dark:bg-slate-700', $props.class)" />
</template>
