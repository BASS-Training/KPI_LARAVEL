<script setup>
import { FileText, Clock, Paperclip, TrendingUp } from 'lucide-vue-next';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const iconMap = [FileText, Clock, Paperclip, TrendingUp];

const colorSchemes = [
    {
        border: 'border-blue-100 dark:border-blue-900/40',
        icon:   'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
        value:  'text-blue-600 dark:text-blue-400',
    },
    {
        border: 'border-amber-100 dark:border-amber-900/40',
        icon:   'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
        value:  'text-amber-600 dark:text-amber-400',
    },
    {
        border: 'border-emerald-100 dark:border-emerald-900/40',
        icon:   'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
        value:  'text-emerald-600 dark:text-emerald-400',
    },
    {
        border: 'border-violet-100 dark:border-violet-900/40',
        icon:   'bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400',
        value:  'text-violet-600 dark:text-violet-400',
    },
];
</script>

<template>
    <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="(item, index) in items"
            :key="item.label"
            class="group relative overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900"
            :class="colorSchemes[index % colorSchemes.length].border"
        >
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    {{ item.label }}
                </p>
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                    :class="colorSchemes[index % colorSchemes.length].icon"
                >
                    <component :is="iconMap[index % iconMap.length]" class="h-4 w-4" />
                </div>
            </div>

            <p
                class="mt-3 text-3xl font-bold tabular-nums tracking-tight"
                :class="colorSchemes[index % colorSchemes.length].value"
            >
                {{ item.value }}
            </p>

            <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ item.hint }}</p>
        </article>
    </section>
</template>
