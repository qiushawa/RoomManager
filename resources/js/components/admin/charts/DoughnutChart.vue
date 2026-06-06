<template>
    <Doughnut :data="chartData" :options="chartOptions" />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { useAdminTheme } from '@/composables';

ChartJS.register(ArcElement, Tooltip, Legend);

const { isDark } = useAdminTheme();

const props = defineProps<{
    labels: string[];
    data: number[];
}>();

const COLORS = [
    'rgba(239, 68, 68, 0.75)',
    'rgba(249, 115, 22, 0.75)',
    'rgba(234, 179, 8, 0.75)',
    'rgba(34, 197, 94, 0.75)',
    'rgba(59, 130, 246, 0.75)',
    'rgba(168, 85, 247, 0.75)',
    'rgba(236, 72, 153, 0.75)',
    'rgba(20, 184, 166, 0.75)',
];

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.data,
            backgroundColor: COLORS.slice(0, props.labels.length),
            borderColor: isDark.value ? 'rgba(30, 41, 59, 0.8)' : 'rgba(255, 255, 255, 0.8)',
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '55%',
    plugins: {
        legend: {
            position: 'right' as const,
            labels: {
                color: isDark.value ? '#cbd5e1' : '#334155',
                font: { size: 11 },
                padding: 12,
                usePointStyle: true,
                pointStyleWidth: 8,
            },
        },
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
}));
</script>
