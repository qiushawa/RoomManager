/**
 * 時段相關工具函式
 */

/** 節次名稱對照表 */
const PERIOD_LABEL_MAP: Record<string, string> = {
    '一': '第一節',
    '二': '第二節',
    '三': '第三節',
    '四': '第四節',
    '五': '第五節',
    '六': '第六節',
    '七': '第七節',
    '八': '第八節',
    '九': '第九節',
    '十': '第十節',
    '十一': '第十一節',
    '十二': '第十二節',
    '十三': '第十三節',
    '十四': '第十四節',
};

/**
 * 格式化節次標籤
 */
export const formatPeriodLabel = (label: string): string => {
    if (label === '午休') return '中午午休';
    return PERIOD_LABEL_MAP[label] || label;
};

/**
 * 格式化選取時段的簡短 label
 */
export const formatSlotLabel = (
    fullDate: string,
    dayName: string,
    periodLabel: string
): string => {
    const [, m, d] = fullDate.split('-').map(Number);
    const mm = String(m).padStart(2, '0');
    const dd = String(d).padStart(2, '0');
    const periodText = periodLabel === '午休' ? '午休' : `第${periodLabel}節`;
    return `${mm}/${dd} (${dayName}) ${periodText}`;
};

/**
 * 檢查選取的時段是否連續
 */
export const checkSlotsConsecutive = (
    selectedSlots: { period: string }[],
    periods: { code: string }[]
): boolean => {
    if (selectedSlots.length <= 1) return true;

    const indexes = selectedSlots
        .map((slot) => periods.findIndex((p) => p.code === slot.period))
        .sort((a, b) => a - b);

    for (let i = 0; i < indexes.length - 1; i++) {
        if (indexes[i + 1] !== indexes[i] + 1) return false;
    }
    return true;
};
