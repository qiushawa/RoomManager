/**
 * 日期相關工具函式
 */

/** 星期對照表 */
export const DAYS_LOOKUP = ['日', '一', '二', '三', '四', '五', '六'] as const;

/**
 * 將 Date 物件格式化為 YYYY-MM-DD 字串
 */
export const formatDateToYYYYMMDD = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

/**
 * 將 YYYY-MM-DD 格式化為顯示用格式 (YYYY/MM/DD (星期X))
 */
export const formatDateForDisplay = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}/${month}/${day} (${DAYS_LOOKUP[date.getDay()]})`;
};

/**
 * 將 YYYY-MM-DD 字串轉換為顯示格式
 */
export const formatDateStringForDisplay = (dateStr: string): string => {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dateObj = new Date(y, m - 1, d);
    return `${y}/${String(m).padStart(2, '0')}/${String(d).padStart(2, '0')} (${DAYS_LOOKUP[dateObj.getDay()]})`;
};

/**
 * 格式化表頭日期 (2026-02-02 -> 2026/2/2)
 */
export const formatHeaderDate = (fullDate: string): string => {
    const [y, m, d] = fullDate.split('-').map(Number);
    return `${y}/${m}/${d}`;
};

/**
 * 格式化時間 (移除秒數, "08:10:00" -> "08:10")
 */
export const formatTime = (time: string): string => {
    return time.substring(0, 5);
};

/**
 * 取得一週的日期資訊
 */
export interface WeekDateInfo {
    date: string;      // DD 格式
    dayName: string;   // 日/一/二...
    fullDate: string;  // YYYY-MM-DD 格式
}

export const getWeekDates = (baseDate: Date): WeekDateInfo[] => {
    const dates: WeekDateInfo[] = [];
    const startOfWeek = new Date(baseDate);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());

    for (let i = 0; i < 7; i++) {
        const d = new Date(startOfWeek);
        d.setDate(d.getDate() + i);
        dates.push({
            date: String(d.getDate()).padStart(2, '0'),
            dayName: DAYS_LOOKUP[i],
            fullDate: formatDateToYYYYMMDD(d),
        });
    }
    return dates;
};
