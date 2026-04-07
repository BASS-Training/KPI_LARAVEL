<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <th :class="cn('h-10 px-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400', $props.class)">
        <slot />
    </th>
</template>
