<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <td :class="cn('px-4 py-3 text-sm text-slate-700 dark:text-slate-300', $props.class)">
        <slot />
    </td>
</template>
