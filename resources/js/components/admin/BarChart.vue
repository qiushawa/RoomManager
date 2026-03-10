<template>
    <Bar :data="chartData" :options="chartOptions" />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { useAdminTheme } from '@/composables/useAdminTheme';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const { isDark } = useAdminTheme();

const props = withDefaults(
    defineProps<{
        labels: string[];
        data: number[];
        barColor?: string;
        label?: string;
    }>(),
    {
        barColor: 'rgba(59, 130, 246, 0.7)',
        label: '',
    },
);

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.data,
            backgroundColor: props.barColor,
            borderRadius: 4,
            maxBarThickness: 40,
        },
    ],
}));

const chartOptions = computed(() => {
    const tickColor = isDark.value ? '#94a3b8' : '#64748b';
    const gridColor = isDark.value ? 'rgba(100, 116, 139, 0.15)' : 'rgba(148, 163, 184, 0.2)';
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                titleColor: '#e2e8f0',
                bodyColor: '#e2e8f0',
                borderColor: 'rgba(100, 116, 139, 0.3)',
                borderWidth: 1,
                cornerRadius: 6,
                padding: 10,
            },
        },
        scales: {
            x: {
                ticks: { color: tickColor, font: { size: 11 } },
                grid: { display: false },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: tickColor,
                    font: { size: 11 },
                    stepSize: 1,
                    precision: 0,
                },
                grid: { color: gridColor },
                border: { display: false },
            },
        },
    };
});
</script>
