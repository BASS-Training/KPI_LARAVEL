<script setup>
defineProps({
    eyebrow: { type: String, default: 'Filter' },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    resultText: { type: String, default: '' },
});
</script>

<template>
    <section class="filter-panel">
        <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="section-heading">{{ eyebrow }}</p>
                <h2 v-if="title" class="mt-2 text-lg font-semibold text-slate-950">{{ title }}</h2>
                <p v-if="description" class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    {{ description }}
                </p>
            </div>
            <div v-if="resultText || $slots.summary" class="filter-panel-summary">
                <slot name="summary">{{ resultText }}</slot>
            </div>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <slot />
        </div>
    </section>
</template>
