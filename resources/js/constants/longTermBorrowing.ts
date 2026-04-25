import type { BuildingCode, WeekdayOption } from '@/types';

export const LONG_TERM_BUILDING_ORDER: BuildingCode[] = ['BCB', 'BGC', 'BRA', 'AIA'];

export const LONG_TERM_BUILDING_LABELS: Record<BuildingCode, string> = {
    BCB: '跨領域',
    BGC: '綜三館',
    BRA: '科研大樓',
    AIA: '資訊大樓',
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
