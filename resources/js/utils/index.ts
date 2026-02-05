/**
 * 工具函式統一匯出
 */

// 日期相關
export {
    DAYS_LOOKUP,
    formatDateForDisplay,
    formatDateStringForDisplay,
    formatDateToYYYYMMDD,
    formatHeaderDate,
    formatTime,
    getWeekDates,
} from './date';
export type { WeekDateInfo } from './date';

// 時段相關
export {
    checkSlotsConsecutive,
    formatPeriodLabel,
    formatSlotLabel,
} from './period';

// 教室相關
export { findRoomByCode } from './room';
