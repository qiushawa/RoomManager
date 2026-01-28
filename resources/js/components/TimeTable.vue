<template>
    <div
        class="animate-fade-in flex flex-col overflow-hidden rounded border border-gray-400 bg-white shadow-sm select-none"
    >
        <table class="flex h-full w-full flex-col">
            <thead class="shrink-0 border-b border-gray-400 bg-gray-50">
                <tr class="flex w-full">
                    <th
                        class="flex w-12 shrink-0 items-center justify-center border-r border-gray-300 py-2 text-xs text-gray-500"
                    >
                        節
                    </th>
                    <th
                        v-for="(day, index) in weekDates"
                        :key="index"
                        class="flex flex-1 flex-col items-center justify-center border-r border-gray-300 py-2 last:border-r-0"
                    >
                        <div class="leading-tight font-bold text-[#d9534f]">
                            {{ day.date }}
                        </div>
                        <div class="text-[10px] leading-tight text-gray-400">
                            {{ day.dayName }}
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
                        class="flex w-12 shrink-0 items-center justify-center border-r border-gray-300 bg-gray-50 text-sm font-bold text-gray-600"
                    >
                        {{ period.label }}
                    </td>

                    <td
                        v-for="(day, dIndex) in weekDates"
                        :key="dIndex"
                        class="relative flex-1 border-r border-gray-300 p-0 last:border-r-0"
                    >
                        <div
                            v-if="checkOccupied(day.fullDate, period.code)"
                            class="absolute inset-0 flex items-center justify-center bg-[#5cb85c]/90 text-xs text-white"
                        ></div>

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
                                        ? 'bg-opacity-80 bg-[#6c8ebf] shadow-inner'
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
import type { OccupiedData, Period, SelectedSlot, WeekDate } from '@/types';

const props = withDefaults(
    defineProps<{
        weekDates: WeekDate[];
        periods: Period[];
        occupiedData?: OccupiedData;
        modelValue?: SelectedSlot[];
    }>(),
    {
        occupiedData: () => ({}),
        modelValue: () => [],
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: SelectedSlot[]): void;
}>();

const checkOccupied = (dateStr: string, code: string): boolean => {
    return props.occupiedData?.[dateStr]?.includes(code) ?? false;
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
                label: `${day.date}日(${day.dayName}) 第${period.label}節`,
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
        if (!checkOccupied(targetDate, p.code)) {
            newSelection.push({
                date: targetDate,
                period: p.code,
                label: `${day.date}日(${day.dayName}) 第${p.label}節`,
            });
        }
    }

    emit('update:modelValue', newSelection);
};
</script>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
