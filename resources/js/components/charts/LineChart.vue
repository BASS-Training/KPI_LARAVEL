<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    labels:   { type: Array,  default: () => [] },
    datasets: { type: Array,  default: () => [] },
    title:    { type: String, default: '' },
    height:   { type: Number, default: 260 },
    yLabel:   { type: String, default: '' },
    secondaryYLabel: { type: String, default: '' },
    yMax: { type: Number, default: null },
    showLegend: { type: Boolean, default: true },
    legendPosition: { type: String, default: 'top' },
    animationDuration: { type: Number, default: 900 },
    delayStep: { type: Number, default: 48 },
});

const PALETTE = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((ds, i) => ({
        label:           ds.label ?? `Dataset ${i + 1}`,
        data:            ds.data ?? [],
        borderColor:     ds.color ?? PALETTE[i % PALETTE.length],
        backgroundColor: (ds.color ?? PALETTE[i % PALETTE.length]) + '20',
        tension:         0.4,
        fill:            ds.fill ?? false,
        yAxisID:         ds.yAxisID ?? 'y',
        pointRadius:     4,
        pointHoverRadius: 6,
        borderWidth:     2,
        spanGaps:        true,
    })),
}));

const usesSecondaryAxis = computed(() => props.datasets.some((ds) => ds.yAxisID === 'y1'));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    layout: { padding: { top: 2, right: 4, bottom: 0, left: 0 } },
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
                duration: Math.max(300, Math.round(props.animationDuration * 0.7)),
            },
        },
    },
    plugins: {
        legend: {
            display: props.showLegend,
            position: props.legendPosition,
            align: 'end',
            labels: {
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 14,
                font: { size: 11 },
            },
        },
        title: {
            display: !!props.title,
            text: props.title,
            font: { size: 13, weight: 'semibold' },
            padding: { bottom: 12 },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    const v = ctx.parsed.y;
                    if (v === null || v === undefined) return `${ctx.dataset.label}: -`;
                    return `${ctx.dataset.label}: ${v}`;
                },
            },
        },
    },
    scales: {
        x: {
            grid: { color: '#f1f5f9' },
            ticks: { font: { size: 11 } },
        },
        y: {
            grid: { color: '#f1f5f9' },
            ticks: { font: { size: 11 } },
            suggestedMax: props.yMax ?? undefined,
            title: {
                display: !!props.yLabel,
                text:    props.yLabel,
                font:    { size: 11 },
            },
            beginAtZero: true,
        },
        y1: {
            display: usesSecondaryAxis.value,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: { font: { size: 11 }, precision: 0 },
            title: {
                display: usesSecondaryAxis.value && !!props.secondaryYLabel,
                text: props.secondaryYLabel,
                font: { size: 11 },
            },
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
