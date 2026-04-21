<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    tone: { type: String, default: 'dark' },
});
</script>

<template>
    <section :class="['page-hero', tone === 'light' ? 'page-hero-light' : '']">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <div v-if="eyebrow" class="page-hero-meta">{{ eyebrow }}</div>
                <h1 class="mt-4 text-2xl font-bold leading-tight tracking-tight md:text-3xl">
                    {{ title }}
                </h1>
                <p v-if="description" class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </section>
</template>
