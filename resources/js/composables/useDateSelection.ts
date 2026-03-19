/**
 * 日期選擇相關 Composable
 * 管理基準日期、週日期計算、導航等
 */

import type { WeekDate } from '@/types';
import { formatDateForDisplay, formatDateToYYYYMMDD, getWeekDates } from '@/utils';
import { API_ENDPOINTS } from '@/constants';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

export interface UseDateSelectionOptions {
    initialDate: string;
    targetRoomCode: () => string | null;
    onDateChange?: () => void;
}

export function useDateSelection(options: UseDateSelectionOptions) {
    const { initialDate, targetRoomCode, onDateChange } = options;

    // 基準日期
    const baseDate = ref<Date>(new Date(initialDate));

    // 當前日期 YYYY-MM-DD 格式
    const currentDateYYYYMMDD = computed(() => formatDateToYYYYMMDD(baseDate.value));

    // 顯示用日期格式
    const displayDate = computed(() => formatDateForDisplay(baseDate.value));

    // 一週的日期資訊
    const weekDates = computed<WeekDate[]>(() => {
        const dates = getWeekDates(baseDate.value);
        return dates.map((d) => ({
            date: d.date,
            dayName: d.dayName,
            fullDate: d.fullDate,
        }));
    });

    // 監聽外部傳入的日期變化
    const watchFiltersDate = (getDate: () => string) => {
        watch(
            getDate,
            (newDateStr) => {
                baseDate.value = new Date(newDateStr);
            }
        );
    };

    // 切換週
    const changeWeek = (offset: number) => {
        const newDate = new Date(baseDate.value);
        newDate.setDate(newDate.getDate() + offset * 7);
        baseDate.value = newDate;
        fetchData();
    };

    // 重設為今天
    const resetToToday = () => {
        baseDate.value = new Date();
        fetchData();
    };

    // 更新日期
    const updateDate = (dateStr: string) => {
        if (!dateStr) return;
        baseDate.value = new Date(dateStr);
        onDateChange?.();
        fetchData();
    };

    // 向後端請求資料
    const fetchData = () => {
        const roomCode = targetRoomCode();
        if (!roomCode) return;

        const dateStr = formatDateToYYYYMMDD(baseDate.value);

        router.get(
            API_ENDPOINTS.home,
            {
                room_code: roomCode,
                date: dateStr,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['allOccupiedData', 'filters'],
                replace: true,
            }
        );
    };

    return {
        baseDate,
        currentDateYYYYMMDD,
        displayDate,
        weekDates,
        watchFiltersDate,
        changeWeek,
        resetToToday,
        updateDate,
        fetchData,
    };
}
