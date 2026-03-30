<template>
    <AdminLayout title="總覽">
        <div class="admin-page-container">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <MetricCard title="總預約數" :value="totalBookingsCount" color="primary">
                    <template #icon>
                        <svg class="h-6 w-6 text-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </template>
                </MetricCard>

                <MetricCard title="可用教室" :value="activeClassroomsCount" color="success">
                    <template #icon>
                        <svg class="h-6 w-6 text-success-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </MetricCard>

                <MetricCard title="待審核預約" :value="pendingBookingsCount" color="warning">
                    <template #icon>
                        <svg class="h-6 w-6 text-warning-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </MetricCard>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- 圖表 1: 各教室借用次數 -->
                <DashboardChartCard
                    title="各教室借用次數"
                    :badge-text="currentSemester ?? '無學期'"
                    accent-class="bg-blue-500"
                    badge-class="border-blue-500/20 bg-blue-500/10 text-blue-400"
                >
                    <BarChart
                        :labels="bookingsPerRoomChart.labels"
                        :data="bookingsPerRoomChart.data"
                        bar-color="rgba(59, 130, 246, 0.7)"
                        label="借用次數"
                    />
                </DashboardChartCard>

                <!-- 圖表 2: 各問題發生次數比例 -->
                <DashboardChartCard
                    title="各問題發生次數比例"
                    badge-text="分類"
                    accent-class="bg-orange-500"
                    badge-class="border-orange-500/20 bg-orange-500/10 text-orange-400"
                >
                    <DoughnutChart
                        :labels="reasonChart.labels"
                        :data="reasonChart.data"
                    />
                </DashboardChartCard>

                <!-- 圖表 3: 各教室問題發生次數 -->
                <DashboardChartCard
                    title="各教室問題發生次數"
                    :badge-text="currentSemester ?? '無學期'"
                    accent-class="bg-red-500"
                    badge-class="border-red-500/20 bg-red-500/10 text-red-400"
                >
                    <BarChart
                        :labels="problemsPerRoomChart.labels"
                        :data="problemsPerRoomChart.data"
                        bar-color="rgba(239, 68, 68, 0.7)"
                        label="問題次數"
                    />
                </DashboardChartCard>

                <!-- 圖表 4: 各時段借用熱度 -->
                <DashboardChartCard
                    title="各時段借用熱度"
                    :badge-text="currentSemester ?? '無學期'"
                    accent-class="bg-emerald-500"
                    badge-class="border-emerald-500/20 bg-emerald-500/10 text-emerald-400"
                >
                    <BarChart
                        :labels="slotPopularityChart.labels"
                        :data="slotPopularityChart.data"
                        bar-color="rgba(16, 185, 129, 0.7)"
                        label="借用次數"
                    />
                </DashboardChartCard>

            </div>

        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { toRefs } from 'vue';
import { AdminLayout } from '@/layouts';
import { BarChart, DashboardChartCard, DoughnutChart, MetricCard } from '@/components/admin';
import type { AdminChartData } from '@/types';

const props = defineProps<{
    activeClassroomsCount?: number;
    totalBookingsCount?: number;
    pendingBookingsCount?: number;
    currentSemester?: string | null;
    bookingsPerRoomChart: AdminChartData;
    reasonChart: AdminChartData;
    problemsPerRoomChart: AdminChartData;
    slotPopularityChart: AdminChartData;
}>();

const {
    activeClassroomsCount,
    totalBookingsCount,
    pendingBookingsCount,
} = toRefs(props);
</script>
