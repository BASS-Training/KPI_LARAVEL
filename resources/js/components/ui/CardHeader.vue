<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <div :class="cn('flex flex-col gap-1 p-5 pb-3', $props.class)"><slot /></div>
</template>
