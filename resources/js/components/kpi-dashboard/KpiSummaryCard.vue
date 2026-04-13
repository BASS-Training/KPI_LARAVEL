<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    description: { type: String, default: '' },
    icon: { type: Object, required: true },
    tone: { type: String, default: 'default' },
    chip: { type: String, default: '' },
    // Optional: pass a numeric 0-100 to show a ring progress
    progress: { type: Number, default: null },
});

const toneConfig = computed(() => ({
    default: {
        wrap: 'bg-white border-slate-200 dark:bg-slate-900 dark:border-slate-800',
        iconWrap: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        chip: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        ring: 'stroke-slate-400',
        bar: 'bg-slate-300',
    },
    success: {
        wrap: 'bg-white border-emerald-100 dark:bg-slate-900 dark:border-emerald-900/40',
        iconWrap: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
        chip: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400',
        ring: 'stroke-emerald-500',
        bar: 'bg-emerald-500',
    },
    warning: {
        wrap: 'bg-white border-amber-100 dark:bg-slate-900 dark:border-amber-900/40',
        iconWrap: 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
        chip: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400',
        ring: 'stroke-amber-500',
        bar: 'bg-amber-500',
    },
    danger: {
        wrap: 'bg-white border-rose-100 dark:bg-slate-900 dark:border-rose-900/40',
        iconWrap: 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
        chip: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400',
        ring: 'stroke-rose-500',
        bar: 'bg-rose-500',
    },
    info: {
        wrap: 'bg-white border-blue-100 dark:bg-slate-900 dark:border-blue-900/40',
        iconWrap: 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
        chip: 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400',
        ring: 'stroke-blue-500',
        bar: 'bg-blue-500',
    },
}[props.tone] ?? {}));

// SVG ring values (r=18, circumference ≈ 113.1)
const CIRC = 113.1;
const ringOffset = computed(() => {
    if (props.progress === null) return CIRC;
    return CIRC - Math.max(0, Math.min(100, props.progress)) / 100 * CIRC;
});

const isNumericValue = computed(() => typeof props.value === 'number' || !Number.isNaN(Number(props.value)));
</script>

<template>
    <div
        :class="cn(
            'group relative overflow-hidden rounded-2xl border p-5 shadow-sm transition-all duration-200',
            'hover:-translate-y-0.5 hover:shadow-md',
            toneConfig.wrap,
        )"
    >
        <!-- Top row: title + icon ─────────────────────────────────────────── -->
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                {{ title }}
            </p>

            <!-- Icon or ring -->
            <div class="relative shrink-0">
                <!-- Radial ring when progress is provided -->
                <template v-if="progress !== null">
                    <svg class="h-12 w-12 -rotate-90" viewBox="0 0 44 44" fill="none">
                        <circle cx="22" cy="22" r="18" stroke-width="3.5" class="stroke-slate-100 dark:stroke-slate-800" />
                        <circle
                            cx="22" cy="22" r="18" stroke-width="3.5"
                            stroke-linecap="round"
                            :class="toneConfig.ring"
                            :stroke-dasharray="CIRC"
                            :stroke-dashoffset="ringOffset"
                            style="transition: stroke-dashoffset 0.7s ease"
                        />
                    </svg>
                    <div
                        :class="cn(
                            'absolute inset-0 flex items-center justify-center',
                            toneConfig.iconWrap.replace('bg-', 'text-').replace(' text-', ' text-'),
                        )"
                    >
                        <component :is="icon" class="h-4 w-4" />
                    </div>
                </template>

                <!-- Plain icon box when no ring -->
                <div
                    v-else
                    :class="cn('flex h-10 w-10 items-center justify-center rounded-xl', toneConfig.iconWrap)"
                >
                    <component :is="icon" class="h-4 w-4" />
                </div>
            </div>
        </div>

        <!-- Value ─────────────────────────────────────────────────────────── -->
        <div class="mt-3">
            <div
                :class="[
                    'font-bold tracking-tight text-slate-900 dark:text-white',
                    isNumericValue ? 'text-3xl' : 'truncate text-xl',
                ]"
            >
                {{ value ?? '—' }}
            </div>
        </div>

        <!-- Progress bar ─────────────────────────────────────────────────── -->
        <div v-if="progress !== null" class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
            <div
                :class="cn('h-full rounded-full transition-all duration-700', toneConfig.bar)"
                :style="{ width: `${Math.max(0, Math.min(100, progress))}%` }"
            />
        </div>

        <!-- Description ─────────────────────────────────────────────────── -->
        <p class="mt-3 text-sm leading-5 text-slate-500 dark:text-slate-400 line-clamp-2">
            {{ description }}
        </p>

        <!-- Chip ─────────────────────────────────────────────────────────── -->
        <div v-if="chip" class="mt-3">
            <span :class="cn('inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold', toneConfig.chip)">
                <span :class="cn('h-1.5 w-1.5 rounded-full', toneConfig.bar)" />
                {{ chip }}
            </span>
        </div>
    </div>
</template>
