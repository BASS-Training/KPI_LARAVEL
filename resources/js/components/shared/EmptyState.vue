<script setup>
defineProps({
    title: { type: String, default: 'Tidak ada data' },
    description: { type: String, default: 'Data belum tersedia untuk filter aktif.' },
    actionLabel: { type: String, default: '' },
});

defineEmits(['action']);
</script>

<template>
    <div class="empty-state">
        <div class="empty-state-icon">
            <slot name="icon">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M4 19V5m0 14h16" />
                    <path d="M8 15v-4m4 4V8m4 7v-6" />
                </svg>
            </slot>
        </div>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ title }}</h3>
        <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">{{ description }}</p>
        <button v-if="actionLabel" class="btn-secondary mt-5" @click="$emit('action')">
            {{ actionLabel }}
        </button>
    </div>
</template>
