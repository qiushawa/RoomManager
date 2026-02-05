<template>
    <AppLayout>
        <!-- 左側邊欄 -->
        <template #left-sidebar>
            <ClassroomNavigator
                :buildings="buildings"
                :active-room-code="targetRoom?.code"
                @select-room="selectRoom"
                class="hidden md:flex"
            />
        </template>

        <!-- 主內容區 -->
        <template #main>
            <WelcomeGuide v-if="!targetRoom" class="overflow-y-auto" />

            <div v-else class="relative flex h-full flex-1 flex-col">
                <header
                    class="z-10 flex h-[88px] shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm"
                >
                    <div>
                        <span
                            class="text-xs tracking-wider text-gray-400 uppercase"
                            >{{ targetRoom.code }}</span
                        >
                        <h2
                            class="text-2xl font-bold text-primary"
                        >
                            {{ targetRoom.name }}
                        </h2>
                    </div>
                    <button
                        @click="resetSelection"
                        class="flex items-center gap-1 text-sm text-gray-400 transition-colors hover:text-red-500"
                    >
                        <span class="text-lg">×</span> 關閉
                    </button>
                </header>

                <div class="flex flex-1 overflow-hidden">
                    <!-- 左側：ScheduleGrid 區域 -->
                    <div
                        class="flex flex-1 flex-col overflow-hidden bg-gradient-to-br from-white to-slate-50 p-2 md:p-4"
                    >
                        <ScheduleGrid
                            class="h-full"
                            :week-dates="weekDates"
                            :periods="periods"
                            :occupied-data="occupiedData"
                            :highlight-info="highlightInfo"
                            v-model="selectedSlots"
                        />

                        <div
                            v-if="
                                selectedSlots.length > 0 &&
                                !isConsecutive
                            "
                            class="shrink-0 px-2 pt-2"
                        >
                            <div
                                class="animate-fade-in flex items-center gap-2 rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-600"
                            >
                                <span class="text-xl">⚠️</span> 請選擇連續的時段
                            </div>
                        </div>
                    </div>
                </div>

                <ScheduleToolbar
                    class="relative z-20 shrink-0 border-t border-gray-200 bg-white"
                    :current-step="currentStep"
                    :selected-count="selectedSlots.length"
                    :formatted-date="footerDateDisplay"
                    :current-date-string="currentDateYYYYMMDD"
                    @change-week="changeWeek"
                    @reset-today="resetToToday"
                    @update-date="updateDate"
                    @next-step="nextStep"
                    @prev-step="currentStep = 1"
                    @submit="submitForm"
                />
            </div>
        </template>

        <!-- 右側邊欄 -->
        <template #right-sidebar>
            <SelectionSummaryPanel
                v-if="targetRoom"
                :room="targetRoom"
                :selected-slots="selectedSlots"
                @next-step="nextStep"
            />
        </template>

        <!-- 額外元素 -->
        <template #extra>
            <BookingProgressStepper
                v-if="SHOW_STEP_PROGRESS_VERTICAL"
                class="hidden lg:flex"
                :target-room="targetRoom"
                :current-step="currentStep"
                :selected-count="selectedSlots.length"
            />

            <!-- 借用須知彈出視窗 -->
            <GuidelinesModal
                :show="showGuidelinesModal"
                @close="showGuidelinesModal = false"
                @confirm="onGuidelinesConfirmed"
            />

            <!-- 申請表單彈出視窗 -->
            <BookingFormModal
                :show="showBookingFormModal"
                :target-room="targetRoom"
                :selected-slots="selectedSlots"
                :form="applicantForm"
                @close="showBookingFormModal = false"
                @update:form="Object.assign(applicantForm, $event)"
                @submit="submitForm"
            />
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

// --- 佈局元件 ---
import { AppLayout } from '@/layouts';

// --- 常數配置 ---
import { SHOW_STEP_PROGRESS_VERTICAL } from '@/constants';

// --- 型別定義 ---
import type {
    Building,
    HighlightInfo,
    OccupiedData,
    Period,
    Room,
    SelectedSlot,
} from '@/types';

// --- Composables ---
import { useBookingFlow, useDateSelection, useHighlight } from '@/composables';

// --- 工具函式 ---
import { checkSlotsConsecutive, findRoomByCode, formatDateStringForDisplay } from '@/utils';

// --- 元件引用 ---
import {
    BookingFormModal,
    BookingProgressStepper,
    ClassroomNavigator,
    GuidelinesModal,
    ScheduleGrid,
    ScheduleToolbar,
    SelectionSummaryPanel,
    WelcomeGuide,
} from '@/components';

// --- Props 定義 ---
const props = defineProps<{
    buildings: Building[];
    periods: Period[];
    allOccupiedData: Record<string, OccupiedData>;
    filters: {
        date: string;
        room_code?: string;
        highlight?: HighlightInfo | null;
    };
}>();

// --- 狀態管理：教室選擇 ---
const targetRoom = ref<Room | null>(
    findRoomByCode(props.buildings, props.filters.room_code)
);

// occupiedData 根據目前選中的教室從 allOccupiedData 取值
const occupiedData = computed(() => {
    if (!targetRoom.value) return {};
    return props.allOccupiedData[targetRoom.value.code] || {};
});

// --- Composable: 日期選擇 ---
const {
    baseDate,
    currentDateYYYYMMDD,
    weekDates,
    watchFiltersDate,
    changeWeek,
    resetToToday,
    updateDate: handleDateUpdate,
} = useDateSelection({
    initialDate: props.filters.date,
    targetRoomCode: () => targetRoom.value?.code ?? null,
    onDateChange: () => {
        selectedSlots.value = [];
        bookingFlow.currentStep.value = 1;
    },
});

// 監聽 filters.date 變化
watchFiltersDate(() => props.filters.date);

// --- 時段選取狀態 (直接管理) ---
const selectedSlots = ref<SelectedSlot[]>([]);

const isConsecutive = computed(() =>
    checkSlotsConsecutive(selectedSlots.value, props.periods)
);

// --- Composable: 預約流程 ---
const bookingFlow = useBookingFlow({
    getTargetRoom: () => targetRoom.value,
    getSelectedSlots: () => selectedSlots.value,
    isConsecutive: () => isConsecutive.value,
    onReset: () => {
        targetRoom.value = null;
        selectedSlots.value = [];
    },
});

const {
    currentStep,
    showGuidelinesModal,
    showBookingFormModal,
    applicantForm,
    nextStep,
    onGuidelinesConfirmed,
    submitForm,
} = bookingFlow;

// --- Composable: 高亮效果 ---
const { highlightInfo } = useHighlight(props.filters.highlight ?? null);

// --- 計算屬性：Footer 日期顯示 ---
const footerDateDisplay = computed<string>(() => {
    if (selectedSlots.value.length > 0) {
        return formatDateStringForDisplay(selectedSlots.value[0].date);
    }
    const d = baseDate.value;
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const daysLookup = ['日', '一', '二', '三', '四', '五', '六'];
    return `${year}/${month}/${day} (${daysLookup[d.getDay()]})`;
});

// --- 操作邏輯：教室選擇 ---
const selectRoom = (room: Room) => {
    if (targetRoom.value?.id !== room.id) {
        targetRoom.value = room;
        currentStep.value = 1;
        selectedSlots.value = [];
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        url.searchParams.set('room_code', room.code);
        window.history.replaceState({}, '', url.toString());
    }
};

// --- 操作邏輯：更新日期 ---
const updateDate = (dateStr: string) => {
    handleDateUpdate(dateStr);
    selectedSlots.value = [];
    currentStep.value = 1;
};

// --- 操作邏輯：重設選擇 ---
const resetSelection = () => {
    bookingFlow.resetFlow();
};
</script>
