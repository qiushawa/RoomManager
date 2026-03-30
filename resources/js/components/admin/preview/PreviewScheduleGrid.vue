<template>
    <div
        class="animate-fade-in flex w-full flex-col overflow-hidden rounded border shadow-sm select-none"
        :class="isDark ? 'border-slate-600/80 bg-a-surface' : 'border-slate-300 bg-a-surface'"
    >
        <table class="flex h-full w-full flex-col">
            <thead
                class="shrink-0 border-b bg-a-surface-2"
                :class="isDark ? 'border-slate-600/80' : 'border-slate-300'"
            >
                <tr class="flex w-full">
                    <th
                        class="flex w-14 shrink-0 items-center justify-center border-r py-1 text-[10px] text-a-text-muted"
                        :class="isDark ? 'border-slate-600/80' : 'border-slate-300'"
                    >
                        節次
                    </th>
                    <th
                        v-for="(day, index) in weekDates"
                        :key="index"
                        class="flex flex-1 flex-col items-center justify-center border-r py-1 last:border-r-0"
                        :class="isDark ? 'border-slate-600/80' : 'border-slate-300'"
                    >
                        <div class="text-[11px] font-bold text-a-text-2">
                            星期{{ day.dayName }}
                        </div>
                        <div
                            class="text-[9px] leading-tight"
                            :class="isDark ? 'text-amber-300' : 'text-rose-600'"
                        >
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
                    class="flex min-h-[34px] w-full flex-1 border-b transition-colors last:border-b-0"
                    :class="isDark ? 'border-slate-600/70' : 'border-slate-300'"
                >
                    <td
                        class="flex w-14 shrink-0 flex-col items-center justify-center border-r bg-a-surface-2 px-1"
                        :class="isDark ? 'border-slate-600/80' : 'border-slate-300'"
                    >
                        <span class="text-[11px] font-bold text-a-text-2">{{ formatPeriodLabel(period.code ?? '') }}</span>
                    </td>

                    <td
                        v-for="(day, dIndex) in weekDates"
                        :key="dIndex"
                        class="relative flex-1 border-r p-0 last:border-r-0"
                        :class="isDark ? 'border-slate-600/80' : 'border-slate-300'"
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
                                class="text-base font-bold text-white"
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
import { useAdminTheme } from '@/composables';
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

const { isDark } = useAdminTheme();

const { getStatusClass, isSelected } = useScheduleStatus({
    occupiedData: () => props.occupiedData,
    selectedSlots: () => props.selectedSlots,
    defaultClass: 'bg-a-surface-hover',
    selectedClass: 'bg-green-500 bg-opacity-80 shadow-inner',
});
</script>
