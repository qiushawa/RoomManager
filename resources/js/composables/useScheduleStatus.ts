import { STATUS_COLORS } from '@/constants';
import type { OccupiedData, OccupiedStatus, SelectedSlot } from '@/types';

interface UseScheduleStatusOptions {
    occupiedData?: () => OccupiedData | undefined;
    selectedSlots?: () => SelectedSlot[] | undefined;
    selectedClass?: string;
    defaultClass?: string;
}

export function useScheduleStatus(options: UseScheduleStatusOptions = {}) {
    const getOccupiedItem = (dateStr: string, code: string) => {
        return options.occupiedData?.()?.[dateStr]?.[code] ?? null;
    };

    const getOccupiedStatus = (dateStr: string, code: string): OccupiedStatus | null => {
        const item = getOccupiedItem(dateStr, code);
        if (!item) return null;
        if (typeof item === 'string') return item as OccupiedStatus;
        return item.status as OccupiedStatus;
    };

    const isSelected = (dateStr: string, periodCode: string): boolean => {
        const selected = options.selectedSlots?.() ?? [];
        return selected.some((slot) => slot.date === dateStr && slot.period === periodCode);
    };

    const getStatusClassByStatus = (status: OccupiedStatus | null): string => {
        const fallback = options.defaultClass ?? STATUS_COLORS.default;
        if (!status) return fallback;
        return STATUS_COLORS[status] || fallback;
    };

    const getStatusClass = (dateStr: string, code: string): string => {
        if (isSelected(dateStr, code)) {
            return options.selectedClass ?? 'bg-green-500 bg-opacity-80 shadow-inner';
        }
        return getStatusClassByStatus(getOccupiedStatus(dateStr, code));
    };

    return {
        getOccupiedItem,
        getOccupiedStatus,
        isSelected,
        getStatusClass,
        getStatusClassByStatus,
    };
}
