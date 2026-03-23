import type { BuildingCode, WeekdayOption } from '@/types';

export const LONG_TERM_BUILDING_ORDER: BuildingCode[] = ['CB', 'GC', 'RA'];

export const LONG_TERM_BUILDING_LABELS: Record<BuildingCode, string> = {
    CB: '跨領域',
    GC: '綜三館',
    RA: '科研大樓',
};

export const LONG_TERM_WEEKDAY_OPTIONS: WeekdayOption[] = [
    { value: 1, label: '週一' },
    { value: 2, label: '週二' },
    { value: 3, label: '週三' },
    { value: 4, label: '週四' },
    { value: 5, label: '週五' },
    { value: 6, label: '週六' },
    { value: 7, label: '週日' },
];
