<script setup>
import { computed } from 'vue';
import { useKpiColor } from '@/composables/useKpiColor';

const props = defineProps({
    label: { type: String, required: true },
    score: { type: Number, required: true },
    maxScore: { type: Number, default: 5 },
});

const { getColorClass, getTextColorClass } = useKpiColor();

// Persentase lebar bar (nilai dinamis → inline style diizinkan)
const widthPercent = computed(() => Math.min((props.score / props.maxScore) * 100, 100));

const barColor = computed(() => getColorClass(props.score));
const textColor = computed(() => getTextColorClass(props.score));
</script>

<template>
    <div class="flex items-center gap-3">
        <!-- Label komponen -->
        <span class="w-32 shrink-0 truncate text-xs text-slate-600 dark:text-slate-400">{{ label }}</span>

        <!-- Bar track -->
        <div class="flex-1 rounded-full bg-slate-100 dark:bg-slate-800" style="height: 6px;">
            <div
                :class="['rounded-full transition-all duration-500', barColor]"
                :style="{ width: widthPercent + '%', height: '6px' }"
            />
        </div>

        <!-- Skor -->
        <span :class="['w-10 shrink-0 text-right text-xs font-medium', textColor]">
            {{ score }}/{{ maxScore }}
        </span>
    </div>
</template>
