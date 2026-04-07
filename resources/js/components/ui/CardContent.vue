<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: { type: String, default: '' } });
</script>
<template>
    <div :class="cn('px-5 pb-5', $props.class)"><slot /></div>
</template>
