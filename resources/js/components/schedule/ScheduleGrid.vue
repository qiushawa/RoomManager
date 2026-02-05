<template>
    <div
        class="animate-fade-in flex w-full flex-col overflow-hidden rounded border border-gray-400 bg-white shadow-sm select-none"
    >
        <table class="flex h-full w-full flex-col">
            <thead class="shrink-0 border-b border-gray-400 bg-gray-50">
                <tr class="flex w-full">
                    <th
                        class="flex w-24 shrink-0 items-center justify-center border-r border-gray-300 py-2 text-xs text-gray-500"
                    >
                        節次
                    </th>
                    <th
                        v-for="(day, index) in weekDates"
                        :key="index"
                        class="flex flex-1 flex-col items-center justify-center border-r border-gray-300 py-2 last:border-r-0"
                    >
                        <div class="text-sm font-bold text-gray-700">
                            星期{{ day.dayName }}
                        </div>
                        <div class="text-[11px] leading-tight text-danger">
                            ({{ formatHeaderDate(day.fullDate) }})
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
                    class="flex min-h-[40px] w-full flex-1 border-b border-gray-300 transition-colors last:border-b-0 hover:bg-blue-50/30"
                >
                    <td
                        class="flex w-24 shrink-0 flex-col items-center justify-center border-r border-gray-300 bg-gray-50 px-1"
                    >
                        <span class="text-sm font-bold text-gray-700">{{ formatPeriodLabel(period.label) }}</span>
                        <span v-if="period.start_time && period.end_time" class="text-[10px] text-gray-400">
                            {{ formatTime(period.start_time) }}~{{ formatTime(period.end_time) }}
                        </span>
                    </td>

                    <td
                        v-for="(day, dIndex) in weekDates"
                        :key="dIndex"
                        class="relative flex-1 border-r border-gray-300 p-0 last:border-r-0"
                    >
                        <!-- 佔用狀態格子 -->
                        <div
                            v-if="getOccupiedStatus(day.fullDate, period.code)"
                            class="absolute inset-0 flex items-center justify-center text-xs text-white"
                            :class="[
                                getStatusClass(getOccupiedStatus(day.fullDate, period.code)),
                                isHighlighted(day.fullDate, period.code) ? 'ring-2 ring-offset-1 ring-orange-500 animate-pulse-once' : ''
                            ]"
                        >
                        </div>

                        <!-- 可選格子 -->
                        <label
                            v-else
                            class="group absolute inset-0 flex cursor-pointer items-center justify-center"
                        >
                            <input
                                type="checkbox"
                                :checked="isSelected(day.fullDate, period.code)"
                                @change="toggleSlotRange(day, period)"
                                class="sr-only"
                            />

                            <div
                                class="flex h-full w-full items-center justify-center transition-all duration-200"
                                :class="
                                    isSelected(day.fullDate, period.code)
                                        ? 'bg-opacity-80 bg-success shadow-inner'
                                        : ''
                                "
                            >
                                <span
                                    v-if="isSelected(day.fullDate, period.code)"
                                    class="text-lg font-bold text-white"
                                    >✓</span
                                >
                            </div>

                            <div
                                class="pointer-events-none absolute inset-0 bg-blue-100 opacity-0 transition-opacity group-hover:opacity-30"
                            ></div>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
/**
 * ScheduleGrid - 週課表時段選擇表格
 *
 * 顯示一週的時段表格，支援：
 * - 時段佔用狀態顯示（課程、已借出、審核中等）
 * - 可選時段的點擊選取
 * - 範圍選取（自動填充連續時段）
 * - 高亮效果（用於顯示剛提交的時段）
 */
import { STATUS_COLORS } from '@/constants';
import type { HighlightInfo, OccupiedData, OccupiedStatus, Period, SelectedSlot, WeekDate } from '@/types';
import { formatHeaderDate, formatPeriodLabel, formatSlotLabel, formatTime } from '@/utils';

const props = withDefaults(
    defineProps<{
        /** 一週的日期資訊 */
        weekDates: WeekDate[];
        /** 時段清單 */
        periods: Period[];
        /** 佔用資料，格式為 { 日期: { 節次代碼: 狀態 } } */
        occupiedData?: OccupiedData;
        /** 已選取的時段（v-model） */
        modelValue?: SelectedSlot[];
        /** 高亮資訊（用於醒目提示剛申請的時段） */
        highlightInfo?: HighlightInfo | null;
    }>(),
    {
        occupiedData: () => ({}),
        modelValue: () => [],
        highlightInfo: null,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: SelectedSlot[]): void;
}>();

const getOccupiedStatus = (dateStr: string, code: string): OccupiedStatus | null => {
    return props.occupiedData?.[dateStr]?.[code] ?? null;
};

const getStatusClass = (status: OccupiedStatus | null): string => {
    if (!status) return STATUS_COLORS.default;
    return STATUS_COLORS[status] || STATUS_COLORS.default;
};

const isHighlighted = (dateStr: string, code: string): boolean => {
    if (!props.highlightInfo) return false;
    return props.highlightInfo.date === dateStr && props.highlightInfo.slots.includes(code);
};

const isSelected = (dateStr: string, periodCode: string): boolean => {
    return props.modelValue.some(
        (s) => s.date === dateStr && s.period === periodCode,
    );
};

// 範圍選取
const toggleSlotRange = (day: WeekDate, period: Period) => {
    const currentSlots = [...props.modelValue];
    const targetDate = day.fullDate;
    const targetCode = period.code;

    // 跨日檢查
    if (currentSlots.length > 0 && currentSlots[0].date !== targetDate) {
        const newSelection: SelectedSlot[] = [
            {
                date: targetDate,
                period: targetCode,
                id: period.id,
                label: formatSlotLabel(day.fullDate, day.dayName, period.label),
            },
        ];
        emit('update:modelValue', newSelection);
        return;
    }

    // 準備索引
    const targetIndex = props.periods.findIndex((p) => p.code === targetCode);
    const currentIndices = currentSlots
        .map((s) => props.periods.findIndex((p) => p.code === s.period))
        .sort((a, b) => a - b);

    let newMin = -1;
    let newMax = -1;

    if (currentIndices.includes(targetIndex)) {
        if (currentIndices.length === 1) {
            emit('update:modelValue', []);
            return;
        }
        const minIndex = currentIndices[0];
        const maxIndex = currentIndices[currentIndices.length - 1];

        if (targetIndex === minIndex || targetIndex === maxIndex) {
            newMin = targetIndex;
            newMax = targetIndex;
        } else {
            emit('update:modelValue', []);
            return;
        }
    } else {
        if (currentIndices.length === 0) {
            newMin = targetIndex;
            newMax = targetIndex;
        } else {
            const minIndex = currentIndices[0];
            const maxIndex = currentIndices[currentIndices.length - 1];
            newMin = Math.min(minIndex, targetIndex);
            newMax = Math.max(maxIndex, targetIndex);
        }
    }

    const newSelection: SelectedSlot[] = [];
    for (let i = newMin; i <= newMax; i++) {
        const p = props.periods[i];
        if (!getOccupiedStatus(targetDate, p.code)) {
            newSelection.push({
                date: targetDate,
                period: p.code,
                id: p.id,
                label: formatSlotLabel(day.fullDate, day.dayName, p.label),
            });
        }
    }

    emit('update:modelValue', newSelection);
};
</script>
