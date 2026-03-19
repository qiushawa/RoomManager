<template>
    <div
        class="animate-fade-in flex w-full flex-col overflow-hidden rounded border border-a-border-card bg-a-surface shadow-sm select-none"
    >
        <table class="flex h-full w-full flex-col">
            <thead class="shrink-0 border-b border-a-border bg-a-surface-2">
                <tr class="flex w-full">
                    <th
                        class="flex w-12 shrink-0 items-center justify-center border-r border-a-divider py-0.5 text-[9px] text-a-text-muted"
                    >
                        節次
                    </th>
                    <th
                        v-for="(day, index) in weekDates"
                        :key="index"
                        class="flex flex-1 flex-col items-center justify-center border-r border-a-divider py-0.5 last:border-r-0"
                    >
                        <div class="text-[10px] font-bold text-a-text-2">
                            星期{{ day.dayName }}
                        </div>
                        <div class="text-[8px] leading-tight text-danger">
                            {{ formatShortDate(day.fullDate) }}
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody
                class="no-scrollbar flex min-h-0 w-full flex-1 flex-col overflow-y-auto"
            >
                <tr
                    v-for="period in periods"
                    :key="period.code"
                    class="flex min-h-[28px] w-full flex-1 border-b border-a-divider transition-colors last:border-b-0"
                >
                    <td
                        class="flex w-12 shrink-0 flex-col items-center justify-center border-r border-a-divider bg-a-surface-2 px-0.5"
                    >
                        <span class="text-[10px] font-bold text-a-text-2">{{ formatPeriodLabel(period.code ?? '') }}</span>
                    </td>

                    <td
                        v-for="(day, dIndex) in weekDates"
                        :key="dIndex"
                        class="relative flex-1 border-r border-a-divider p-0 last:border-r-0"
                    >
                        <!-- 佔用或選取狀態格子 -->
                        <div
                            class="absolute inset-0 flex items-center justify-center text-xs text-white"
                            :class="[
                                getStatusClass(day.fullDate, period.code ?? period.label ?? '')
                            ]"
                        >
                            <span
                                v-if="isSelected(day.fullDate, period.code ?? period.label ?? '')"
                                class="text-sm font-bold text-white"
                            >
                                ✓
                            </span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { useScheduleStatus } from '@/composables';
import type { HighlightInfo, OccupiedData, Period, SelectedSlot, WeekDate } from '@/types';
import { formatPeriodLabel } from '@/utils';

const props = withDefaults(
    defineProps<{
        weekDates: WeekDate[];
        periods: Period[];
        occupiedData?: OccupiedData;
        selectedSlots?: SelectedSlot[];
        highlightInfo?: HighlightInfo | null;
    }>(),
    {
        occupiedData: () => ({}),
        selectedSlots: () => [],
        highlightInfo: null,
    },
);

const formatShortDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    const month = date.getMonth() + 1;
    const day = date.getDate();
    return `${month}/${day}`;
};

const { getStatusClass, isSelected } = useScheduleStatus({
    occupiedData: () => props.occupiedData,
    selectedSlots: () => props.selectedSlots,
    defaultClass: 'bg-a-surface-hover',
    selectedClass: 'bg-green-500 bg-opacity-80 shadow-inner',
});
</script>
