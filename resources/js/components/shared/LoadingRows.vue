<script setup>
import Skeleton from '@/components/ui/Skeleton.vue';

defineProps({
    rows: { type: Number, default: 6 },
    height: { type: String, default: 'h-16' },
});
</script>

<template>
    <div class="space-y-3">
        <Skeleton v-for="i in rows" :key="i" :class="[height, 'rounded-lg']" />
    </div>
</template>
