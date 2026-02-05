/**
 * 高亮效果相關 Composable
 * 管理剛申請時段的高亮顯示
 */

import type { HighlightInfo } from '@/types';
import { ref } from 'vue';

/** 高亮持續時間 (毫秒) */
const HIGHLIGHT_DURATION = 5000;

export function useHighlight(initialInfo: HighlightInfo | null = null) {
    const highlightInfo = ref<HighlightInfo | null>(initialInfo);

    // 如果有初始高亮，設定自動清除計時器
    if (highlightInfo.value) {
        setTimeout(() => {
            highlightInfo.value = null;
        }, HIGHLIGHT_DURATION);
    }

    // 檢查特定格子是否高亮
    const isHighlighted = (dateStr: string, code: string): boolean => {
        if (!highlightInfo.value) return false;
        return (
            highlightInfo.value.date === dateStr &&
            highlightInfo.value.slots.includes(code)
        );
    };

    // 設定高亮
    const setHighlight = (info: HighlightInfo) => {
        highlightInfo.value = info;
        setTimeout(() => {
            highlightInfo.value = null;
        }, HIGHLIGHT_DURATION);
    };

    // 清除高亮
    const clearHighlight = () => {
        highlightInfo.value = null;
    };

    return {
        highlightInfo,
        isHighlighted,
        setHighlight,
        clearHighlight,
    };
}
