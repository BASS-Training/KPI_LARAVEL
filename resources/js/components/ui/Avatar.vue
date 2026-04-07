<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    name: { type: String, default: '' },
    size: { type: String, default: 'default' }, // sm | default | lg
    class: { type: String, default: '' },
});

// Ambil inisial dari nama (maks 2 karakter)
const initials = computed(() => {
    const parts = props.name.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return props.name.slice(0, 2).toUpperCase();
});

const sizes = {
    sm: 'h-7 w-7 text-xs',
    default: 'h-9 w-9 text-sm',
    lg: 'h-11 w-11 text-base',
};
</script>

<template>
    <div
        :class="cn(
            'flex items-center justify-center rounded-full bg-blue-100 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            sizes[size],
            $props.class
        )"
        :title="name"
    >
        {{ initials }}
    </div>
</template>
