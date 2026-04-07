<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <h3 :class="cn('text-sm font-medium text-slate-900 dark:text-slate-100', $props.class)"><slot /></h3>
</template>
