<template>
    <AdminLayout title="總覽">
        <div class="flex flex-col gap-6 max-w-[1600px] mx-auto">

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
                <div class="bg-a-surface rounded-xl border border-a-border-card p-5 flex flex-col gap-4 h-80">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-4 bg-blue-500 rounded-full"></div>
                            <h3 class="text-sm font-semibold text-a-text-body">各教室借用次數</h3>
                        </div>
                        <span class="rounded-md bg-blue-500/10 border border-blue-500/20 px-2.5 py-1 text-xs text-blue-400 font-medium">
                            {{ currentSemester ?? '無學期' }}
                        </span>
                    </div>
                    <div class="flex-1 min-h-0">
                        <BarChart
                            :labels="bookingsPerRoomChart.labels"
                            :data="bookingsPerRoomChart.data"
                            bar-color="rgba(59, 130, 246, 0.7)"
                            label="借用次數"
                        />
                    </div>
                </div>

                <!-- 圖表 2: 各問題發生次數比例 -->
                <div class="bg-a-surface rounded-xl border border-a-border-card p-5 flex flex-col gap-4 h-80">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-4 bg-orange-500 rounded-full"></div>
                            <h3 class="text-sm font-semibold text-a-text-body">各問題發生次數比例</h3>
                        </div>
                        <span class="rounded-md bg-orange-500/10 border border-orange-500/20 px-2.5 py-1 text-xs text-orange-400 font-medium">分類</span>
                    </div>
                    <div class="flex-1 min-h-0">
                        <DoughnutChart
                            :labels="reasonChart.labels"
                            :data="reasonChart.data"
                        />
                    </div>
                </div>

                <!-- 圖表 3: 各教室問題發生次數 -->
                <div class="bg-a-surface rounded-xl border border-a-border-card p-5 flex flex-col gap-4 h-80">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-4 bg-red-500 rounded-full"></div>
                            <h3 class="text-sm font-semibold text-a-text-body">各教室問題發生次數</h3>
                        </div>
                        <span class="rounded-md bg-red-500/10 border border-red-500/20 px-2.5 py-1 text-xs text-red-400 font-medium">
                            {{ currentSemester ?? '無學期' }}
                        </span>
                    </div>
                    <div class="flex-1 min-h-0">
                        <BarChart
                            :labels="problemsPerRoomChart.labels"
                            :data="problemsPerRoomChart.data"
                            bar-color="rgba(239, 68, 68, 0.7)"
                            label="問題次數"
                        />
                    </div>
                </div>

                <!-- 圖表 4: 各時段借用熱度 -->
                <div class="bg-a-surface rounded-xl border border-a-border-card p-5 flex flex-col gap-4 h-80">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
                            <h3 class="text-sm font-semibold text-a-text-body">各時段借用熱度</h3>
                        </div>
                        <span class="rounded-md bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 text-xs text-emerald-400 font-medium">
                            {{ currentSemester ?? '無學期' }}
                        </span>
                    </div>
                    <div class="flex-1 min-h-0">
                        <BarChart
                            :labels="slotPopularityChart.labels"
                            :data="slotPopularityChart.data"
                            bar-color="rgba(16, 185, 129, 0.7)"
                            label="借用次數"
                        />
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { toRefs } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import MetricCard from '@/components/admin/MetricCard.vue';
import BarChart from '@/components/admin/BarChart.vue';
import DoughnutChart from '@/components/admin/DoughnutChart.vue';

interface ChartData {
    labels: string[];
    data: number[];
}

const props = defineProps<{
    activeClassroomsCount?: number;
    totalBookingsCount?: number;
    pendingBookingsCount?: number;
    currentSemester?: string | null;
    bookingsPerRoomChart: ChartData;
    reasonChart: ChartData;
    problemsPerRoomChart: ChartData;
    slotPopularityChart: ChartData;
}>();

const {
    activeClassroomsCount,
    totalBookingsCount,
    pendingBookingsCount,
} = toRefs(props);
</script>
