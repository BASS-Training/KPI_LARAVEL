<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    labels:      { type: Array,   default: () => [] },
    datasets:    { type: Array,   default: () => [] },
    title:       { type: String,  default: '' },
    height:      { type: Number,  default: 260 },
    horizontal:  { type: Boolean, default: false },
    yLabel:      { type: String,  default: '' },
    stacked:     { type: Boolean, default: false },
    showLegend:  { type: Boolean, default: true },
    xMax:         { type: Number,  default: null },
    animationDuration: { type: Number, default: 850 },
    delayStep: { type: Number, default: 32 },
});

const PALETTE = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((ds, i) => ({
        label:           ds.label ?? `Dataset ${i + 1}`,
        data:            ds.data ?? [],
        backgroundColor: ds.color ?? PALETTE[i % PALETTE.length],
        borderRadius:    6,
        borderSkipped:   false,
        maxBarThickness: props.horizontal ? 22 : 48,
    })),
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: props.horizontal ? 'y' : 'x',
    interaction: { mode: 'index', intersect: false },
    layout: { padding: { top: 2, right: 8, bottom: 0, left: 0 } },
    animation: {
        duration: props.animationDuration,
        easing: 'easeOutQuart',
        delay(context) {
            if (context.type !== 'data' || context.mode === 'resize') {
                return 0;
            }

            return context.dataIndex * props.delayStep;
        },
    },
    transitions: {
        active: {
            animation: {
                duration: Math.max(250, Math.round(props.animationDuration * 0.7)),
            },
        },
    },
    plugins: {
        legend: {
            display: props.showLegend,
            position: 'top',
            labels: { boxWidth: 12, font: { size: 12 } },
        },
        title: {
            display: !!props.title,
            text: props.title,
            font: { size: 13, weight: 'semibold' },
            padding: { bottom: 12 },
        },
    },
    scales: {
        x: {
            stacked: props.stacked,
            grid: { display: !props.horizontal, color: '#f1f5f9' },
            ticks: { font: { size: 11 } },
            suggestedMax: props.horizontal ? (props.xMax ?? undefined) : undefined,
            title: {
                display: props.horizontal && !!props.yLabel,
                text: props.yLabel,
                font: { size: 11 },
            },
            beginAtZero: props.horizontal,
        },
        y: {
            stacked: props.stacked,
            grid: { display: props.horizontal, color: '#f1f5f9' },
            ticks: { autoSkip: false, font: { size: 11 } },
            title: {
                display: !props.horizontal && !!props.yLabel,
                text:    props.yLabel,
                font:    { size: 11 },
            },
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
