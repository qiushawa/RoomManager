<template>
    <div
        class="animate-fade-in flex w-full flex-col overflow-hidden rounded shadow-sm select-none"
        :class="[
            isDarkTheme
                ? 'border-slate-600 bg-slate-900'
                : 'border-gray-400 bg-white'
        ]"
    >
        <table class="flex h-full w-full flex-col">
            <thead
                class="shrink-0 border-b"
                :class="isDarkTheme ? 'border-slate-600 bg-slate-800' : 'border-gray-400 bg-gray-50'"
            >
                <tr class="flex w-full">
                    <th
                        class="flex w-24 shrink-0 items-center justify-center border-r py-2 text-xs"
                        :class="isDarkTheme ? 'border-slate-600 text-slate-300' : 'border-gray-300 text-gray-500'"
                    >
                        節次
                    </th>
                    <th
                        v-for="(day, index) in weekDates"
                        :key="index"
                        class="flex flex-1 flex-col items-center justify-center border-r py-2 last:border-r-0"
                        :class="isDarkTheme ? 'border-slate-600' : 'border-gray-300'"
                    >
                        <div class="text-sm font-bold" :class="isDarkTheme ? 'text-slate-100' : 'text-gray-700'">
                            星期{{ day.dayName }}
                        </div>
                        <div v-if="showHeaderDate" class="text-[11px] leading-tight text-danger">
                            ({{ formatHeaderDate(day.fullDate) }})
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody
                class="no-scrollbar flex min-h-0 w-full flex-1 flex-col overflow-y-auto"
            >
                <tr
                    v-for="(period, pIndex) in periods"
                    :key="period.code"
                    class="flex min-h-[40px] w-full flex-1 border-b transition-colors last:border-b-0"
                    :class="isDarkTheme ? 'border-slate-600 hover:bg-sky-900/20' : 'border-gray-300 hover:bg-blue-50/30'"
                >
                    <td
                        class="flex w-24 shrink-0 flex-col items-center justify-center border-r px-1"
                        :class="isDarkTheme ? 'border-slate-600 bg-slate-800' : 'border-gray-300 bg-gray-50'"
                    >
                        <span class="text-sm font-bold" :class="isDarkTheme ? 'text-slate-100' : 'text-gray-700'">{{ formatPeriodLabel(period.label) }}</span>
                        <span v-if="period.start_time && period.end_time" class="text-[10px]" :class="isDarkTheme ? 'text-slate-400' : 'text-gray-400'">
                            {{ formatTime(period.start_time) }}~{{ formatTime(period.end_time) }}
                        </span>
                    </td>

                    <td
                        v-for="(day, dIndex) in weekDates"
                        :key="dIndex"
                        class="relative flex-1 border-r p-0 last:border-r-0"
                        :class="isDarkTheme ? 'border-slate-600' : 'border-gray-300'"
                    >
                        <!-- 佔用狀態格子 -->
                        <div
                            v-if="getOccupiedStatus(day.fullDate, period.code)"
                            class="group absolute inset-0 flex items-center justify-center text-xs text-white cursor-not-allowed z-10 hover:z-[60]"
                            :class="[
                                getStatusClass(getOccupiedStatus(day.fullDate, period.code)),
                                isHighlighted(day.fullDate, period.code) ? 'ring-2 ring-offset-1 ring-orange-500 animate-pulse-once' : ''
                            ]"
                        >
                            <!-- Hover 資訊預覽元件 -->
                            <OccupiedTooltip
                                :item="getOccupiedItem(day.fullDate, period.code)"
                                :show-below="pIndex < 3"
                            />
                        </div>

                        <!-- 可選格子 -->
                        <label
                            v-else
                            :for="`slot-${day.fullDate}-${period.code}`"
                            class="group absolute inset-0 flex cursor-pointer items-center justify-center select-none"
                            @mousedown.prevent="handleMouseDown(day, period)"
                            @mouseenter="handleMouseEnter(day, period)"
                            @dragstart="preventDragDefault"
                        >
                            <input
                                type="checkbox"
                                :id="`slot-${day.fullDate}-${period.code}`"
                                :checked="isSelected(day.fullDate, period.code)"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                                @change.prevent
                            />
                            <div
                                class="flex h-full w-full items-center justify-center transition-all duration-200"
                                :class="
                                    isSelected(day.fullDate, period.code)
                                        ? 'bg-opacity-80 bg-success shadow-inner'
                                        : ''
                                "
                            >
                                <span
                                    v-if="isSelected(day.fullDate, period.code)"
                                    class="text-lg font-bold text-white"
                                    >✓</span
                                >
                            </div>

                            <div
                                class="pointer-events-none absolute inset-0 opacity-0 transition-opacity group-hover:opacity-30"
                                :class="isDarkTheme ? 'bg-sky-700/30' : 'bg-blue-100'"
                            ></div>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
/**
 * ScheduleGrid - 週課表時段選擇表格
 *
 * 顯示一週的時段表格，支援：
 * - 時段佔用狀態顯示（課程、已借出、審核中等）
 * - 多選點擊（多次單選）與拖曳選取
 * - 高亮效果（用於顯示剛提交的時段）
 */
import { useScheduleStatus } from '@/composables';
import type { HighlightInfo, OccupiedData, OccupiedStatus, Period, SelectedSlot, WeekDate } from '@/types';
import { formatHeaderDate, formatPeriodLabel, formatSlotLabel, formatTime } from '@/utils';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import OccupiedTooltip from './OccupiedTooltip.vue';

const props = withDefaults(
    defineProps<{
        weekDates: WeekDate[];
        periods: Period[];
        occupiedData?: OccupiedData;
        modelValue?: SelectedSlot[];
        highlightInfo?: HighlightInfo | null;
        showHeaderDate?: boolean;
        allowCrossDateSelection?: boolean;
        theme?: 'auto' | 'light' | 'dark';
    }>(),
    {
        occupiedData: () => ({}),
        modelValue: () => [],
        highlightInfo: null,
        showHeaderDate: true,
        allowCrossDateSelection: false,
        theme: 'auto',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: SelectedSlot[]): void;
}>();

const {
    getOccupiedItem,
    getOccupiedStatus,
    getStatusClassByStatus,
    isSelected,
} = useScheduleStatus({
    occupiedData: () => props.occupiedData,
    selectedSlots: () => props.modelValue,
    defaultClass: 'bg-gray-400/90',
});

const prefersDark = ref(false);
let mediaQuery: MediaQueryList | null = null;
const handleSchemeChange = (e: MediaQueryListEvent) => {
    prefersDark.value = e.matches;
};

const isDarkTheme = computed(() => {
    if (props.theme === 'dark') return true;
    if (props.theme === 'light') return false;
    return prefersDark.value;
});

const getStatusClass = (status: OccupiedStatus | null): string => getStatusClassByStatus(status);

const isHighlighted = (dateStr: string, code: string): boolean => {
    if (!props.highlightInfo) return false;
    return props.highlightInfo.date === dateStr && props.highlightInfo.slots.includes(code);
};

// 狀態管理：拖曳選取
const isDragging = ref(false);
const dragMode = ref<'select' | 'deselect'>('select');
const currentDragDate = ref('');

const preventDragDefault = (e: DragEvent) => e.preventDefault();

const handleMouseDown = (day: WeekDate, period: Period) => {
    const targetDate = day.fullDate;
    const targetCode = period.code;

    // 清空其他日期的選取 (不允許跨日)
    let currentSlots = [...props.modelValue];
    if (!props.allowCrossDateSelection && currentSlots.length > 0 && currentSlots[0].date !== targetDate) {
        currentSlots = [];
    }

    isDragging.value = true;
    currentDragDate.value = targetDate;

    // 判斷當下是要選取還是取消選取
    const alreadySelected = isSelected(targetDate, targetCode);
    dragMode.value = alreadySelected ? 'deselect' : 'select';

    applySelection(day, period, currentSlots, dragMode.value);
};

const handleMouseEnter = (day: WeekDate, period: Period) => {
    if (!isDragging.value) return;
    if (!props.allowCrossDateSelection && currentDragDate.value !== day.fullDate) return;

    const  currentSlots = [...props.modelValue];
    applySelection(day, period, currentSlots, dragMode.value);
};

const handleMouseUp = () => {
    isDragging.value = false;
    currentDragDate.value = '';
};

// 全域監聽 mouseup 防止拖曳中在外面放開卡住
onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('mouseup', handleMouseUp);
        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        prefersDark.value = mediaQuery.matches;
        mediaQuery.addEventListener('change', handleSchemeChange);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('mouseup', handleMouseUp);
    }
    if (mediaQuery) {
        mediaQuery.removeEventListener('change', handleSchemeChange);
    }
});

const applySelection = (day: WeekDate, period: Period, currentSlots: SelectedSlot[], mode: 'select' | 'deselect') => {
    const targetDate = day.fullDate;
    const targetCode = period.code;

    if (mode === 'select') {
        // 如果還沒選過才加入
        if (!currentSlots.some(s => s.date === targetDate && s.period === targetCode)) {
            currentSlots.push({
                date: targetDate,
                period: targetCode,
                id: period.id,
                label: formatSlotLabel(day.fullDate, day.dayName, period.label),
            });
            // 根據節次順序排序 (假設 periods 是照順序來的字串或是可對齊的順序)
            currentSlots.sort((a, b) => {
                const idxA = props.periods.findIndex(p => p.code === a.period);
                const idxB = props.periods.findIndex(p => p.code === b.period);
                return idxA - idxB;
            });
        }
    } else {
        // 移除
        currentSlots = currentSlots.filter(s => !(s.date === targetDate && s.period === targetCode));
    }

    emit('update:modelValue', currentSlots);
};
</script>
