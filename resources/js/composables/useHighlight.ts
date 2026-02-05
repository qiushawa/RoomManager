/**
 * 高亮效果相關 Composable
 * 管理剛申請時段的高亮顯示
 */

import type { HighlightInfo } from '@/types';
import { onUnmounted, ref, watch } from 'vue';

/** 高亮持續時間 (毫秒) */
const HIGHLIGHT_DURATION = 5000;

export function useHighlight(initialInfo: HighlightInfo | null = null) {
    const highlightInfo = ref<HighlightInfo | null>(initialInfo);

    // 計時器 ID，用於清理
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    // 排程自動清除高亮
    const scheduleAutoClear = () => {
        // 如果沒有高亮資訊，不需要設定計時器
        if (!highlightInfo.value) return;

        // 清除之前的計時器（避免重複）
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
        }

        // 設定新的計時器
        timeoutId = setTimeout(() => {
            highlightInfo.value = null;
            timeoutId = null;
        }, HIGHLIGHT_DURATION);
    };

    // 監聽 highlightInfo 變化，自動排程清除
    watch(
        highlightInfo,
        () => {
            scheduleAutoClear();
        },
        { immediate: true }
    );

    // 組件卸載時清理計時器，避免記憶體洩漏
    onUnmounted(() => {
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
        }
    });

    // 檢查特定格子是否高亮
    const isHighlighted = (dateStr: string, code: string): boolean => {
        if (!highlightInfo.value) return false;
        return (
            highlightInfo.value.date === dateStr &&
            highlightInfo.value.slots.includes(code)
        );
    };

    // 設定高亮（watch 會自動處理計時器）
    const setHighlight = (info: HighlightInfo) => {
        highlightInfo.value = info;
    };

    // 清除高亮
    const clearHighlight = () => {
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
            timeoutId = null;
        }
        highlightInfo.value = null;
    };

    return {
        highlightInfo,
        isHighlighted,
        setHighlight,
        clearHighlight,
    };
}
