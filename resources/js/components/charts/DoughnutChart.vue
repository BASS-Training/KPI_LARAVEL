<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    Title,
} from 'chart.js';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend, Title);

const props = defineProps({
    labels: { type: Array,  default: () => [] },
    data:   { type: Array,  default: () => [] },
    colors: { type: Array,  default: () => ['#2563eb', '#10b981', '#f59e0b', '#ef4444'] },
    title:  { type: String, default: '' },
    height: { type: Number, default: 240 },
    cutout: { type: String, default: '68%' },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [{
        data:            props.data,
        backgroundColor: props.colors,
        hoverOffset:     6,
        borderWidth:     2,
        borderColor:     '#fff',
    }],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: props.cutout,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 14, font: { size: 12 } },
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
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                    return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                },
            },
        },
    },
}));
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
