/**
 * 時段選取相關 Composable
 * 管理時段選取、連續性檢查等
 */

import type { Period, SelectedSlot, WeekDate } from '@/types';
import { checkSlotsConsecutive, formatSlotLabel } from '@/utils';
import { computed, ref } from 'vue';

export interface UseSlotSelectionOptions {
    periods: () => Period[];
    getOccupiedStatus: (dateStr: string, code: string) => string | null;
}

export function useSlotSelection(options: UseSlotSelectionOptions) {
    const { periods, getOccupiedStatus } = options;

    // 選取的時段
    const selectedSlots = ref<SelectedSlot[]>([]);

    // 檢查是否連續
    const isConsecutive = computed(() =>
        checkSlotsConsecutive(selectedSlots.value, periods())
    );

    // 清空選取
    const clearSlots = () => {
        selectedSlots.value = [];
    };

    // 檢查是否已選取
    const isSelected = (dateStr: string, periodCode: string): boolean => {
        return selectedSlots.value.some(
            (s) => s.date === dateStr && s.period === periodCode
        );
    };

    // 範圍選取邏輯
    const toggleSlotRange = (day: WeekDate, period: Period) => {
        const currentSlots = [...selectedSlots.value];
        const targetDate = day.fullDate;
        const targetCode = period.code;
        const periodsArray = periods();

        // 跨日檢查
        if (currentSlots.length > 0 && currentSlots[0].date !== targetDate) {
            selectedSlots.value = [{
                date: targetDate,
                period: targetCode,
                id: period.id,
                label: formatSlotLabel(day.fullDate, day.dayName, period.label),
            }];
            return;
        }

        // 準備索引
        const targetIndex = periodsArray.findIndex((p) => p.code === targetCode);
        const currentIndices = currentSlots
            .map((s) => periodsArray.findIndex((p) => p.code === s.period))
            .sort((a, b) => a - b);

        let newMin = -1;
        let newMax = -1;

        if (currentIndices.includes(targetIndex)) {
            if (currentIndices.length === 1) {
                selectedSlots.value = [];
                return;
            }
            const minIndex = currentIndices[0];
            const maxIndex = currentIndices[currentIndices.length - 1];

            if (targetIndex === minIndex || targetIndex === maxIndex) {
                newMin = targetIndex;
                newMax = targetIndex;
            } else {
                selectedSlots.value = [];
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
            const p = periodsArray[i];
            if (!getOccupiedStatus(targetDate, p.code)) {
                newSelection.push({
                    date: targetDate,
                    period: p.code,
                    id: p.id,
                    label: formatSlotLabel(day.fullDate, day.dayName, p.label),
                });
            }
        }

        selectedSlots.value = newSelection;
    };

    return {
        selectedSlots,
        isConsecutive,
        isSelected,
        clearSlots,
        toggleSlotRange,
    };
}
