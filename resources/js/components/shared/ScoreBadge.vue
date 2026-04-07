<script setup>
import { computed } from 'vue';
import { useKpiColor } from '@/composables/useKpiColor';

const props = defineProps({
    // Pass either percentage OR scoreLabel, not both
    percentage:  { type: [Number, String], default: null },
    scoreLabel:  { type: String,  default: null }, // excellent | good | average | bad
    showPct:     { type: Boolean, default: true  },
    size:        { type: String,  default: 'sm' }, // sm | md
});

const { getPredikatPercentage, getBadgeClass } = useKpiColor();

const resolved = computed(() => {
    if (props.scoreLabel) {
        const labelMap = {
            excellent: { label: 'Excellent', pct: null },
            good:      { label: 'Good',      pct: null },
            average:   { label: 'Average',   pct: null },
            bad:       { label: 'Bad',       pct: null },
        };
        return {
            label: labelMap[props.scoreLabel]?.label ?? props.scoreLabel,
            badgeClass: getBadgeClass(props.scoreLabel),
            scoreLabel: props.scoreLabel,
        };
    }

    if (props.percentage !== null && props.percentage !== undefined) {
        const p = getPredikatPercentage(props.percentage);
        return {
            label: p.label,
            badgeClass: getBadgeClass(p.scoreLabel),
            scoreLabel: p.scoreLabel,
        };
    }

    return { label: '-', badgeClass: 'badge-neutral', scoreLabel: null };
});

const displayText = computed(() => {
    if (props.showPct && props.percentage !== null && props.percentage !== undefined) {
        return `${resolved.value.label} · ${Number(props.percentage).toFixed(1)}%`;
    }
    return resolved.value.label;
});
</script>

<template>
    <span :class="[resolved.badgeClass, size === 'md' ? 'text-xs px-2.5 py-0.5' : '']">
        {{ displayText }}
    </span>
</template>
