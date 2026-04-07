<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <tr :class="cn('transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50', $props.class)">
        <slot />
    </tr>
</template>
