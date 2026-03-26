<template>
    <AppLayout>
        <!-- 左側邊欄 -->
        <template #left-sidebar>
            <ClassroomNavigator :buildings="buildings" :active-room-code="targetRoom?.code"
                :is-overview-active="viewMode === 'overview'" @select-overview="selectOverview" @select-room="selectRoom"
                class="flex" width="medium" />
        </template>

        <!-- 主內容區 -->
        <template #main>
            <div v-if="viewMode === 'overview'" class="relative flex h-full flex-1 flex-col">
                <header
                    class="z-10 flex h-[88px] shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm"
                >
                    <div>
                        <span class="text-xs tracking-wider text-gray-400 uppercase">overview</span>
                        <h2 class="text-2xl font-bold text-primary">
                             {{footerDateDisplay}} 教室使用總覽
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
                    <div class="flex flex-1 flex-col overflow-hidden bg-gradient-to-br from-white to-slate-50 p-4">
                        <ClassroomOverviewGrid
                            class="h-full"
                            :date="currentDateYYYYMMDD"
                            :day-name="currentDayName"
                            :rooms="allRooms"
                            :periods="periods"
                            :occupied-data="overviewOccupiedData"
                            :selected-room-code="targetRoom?.code"
                            :model-value="selectedSlots"
                            theme="light"
                            @update:model-value="selectedSlots = $event"
                            @update:selected-room-code="handleOverviewRoomChange"
                        />
                    </div>
                </div>

                <ScheduleToolbar
                    class="relative z-20 shrink-0 border-t border-gray-200 bg-white"
                    :current-step="currentStep"
                    :selected-count="selectedSlots.length"
                    :formatted-date="footerDateDisplay"
                    :current-date-string="currentDateYYYYMMDD"
                    navigation-mode="day"
                    @change-week="changeWeek"
                    @reset-today="resetToToday"
                    @update-date="updateDate"
                    @next-step="nextStep"
                    @prev-step="currentStep = 1"
                    @submit="submitForm"
                />
            </div>

            <WelcomeGuide v-else-if="!targetRoom" class="overflow-y-auto" />

            <div v-else class="relative flex h-full flex-1 flex-col">
                <header
                    class="z-10 flex h-[88px] shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
                    <div>
                        <span class="text-xs tracking-wider text-gray-400 uppercase">{{ targetRoom.code }}</span>
                        <h2 class="text-2xl font-bold text-primary">
                            {{ targetRoom.name }}
                        </h2>
                    </div>
                    <!-- 關閉按鈕: 跳至首頁 -->
                    <button @click="resetSelection"
                        class="flex items-center gap-1 text-sm text-gray-400 transition-colors hover:text-red-500">
                        <span class="text-lg">×</span> 關閉
                    </button>
                </header>

                <div class="flex flex-1 overflow-hidden">
                    <!-- 左側：ScheduleGrid 區域 -->
                    <div class="flex flex-1 flex-col overflow-hidden bg-gradient-to-br from-white to-slate-50 p-4">
                        <ScheduleGrid class="h-full" :week-dates="weekDates" :periods="periods"
                            :occupied-data="occupiedData" :highlight-info="highlightInfo" theme="light"
                            :allow-cross-date-selection="true"
                            v-model="selectedSlots" />
                    </div>
                </div>

                <ScheduleToolbar class="relative z-20 shrink-0 border-t border-gray-200 bg-white"
                    :current-step="currentStep" :selected-count="selectedSlots.length"
                    :formatted-date="footerDateDisplay" :current-date-string="currentDateYYYYMMDD" navigation-mode="week"
                    @change-week="changeWeek" @reset-today="resetToToday" @update-date="updateDate"
                    @next-step="nextStep" @prev-step="currentStep = 1" @submit="submitForm" />
            </div>
        </template>

        <!-- 右側邊欄 -->
        <template #right-sidebar>
            <SelectionSummaryPanel v-if="viewMode === 'overview' || targetRoom" :room="targetRoom" :selected-slots="selectedSlots"
                @next-step="nextStep" width="medium" />
        </template>

        <!-- 額外元素 -->
        <template #extra>
            <BookingProgressStepper v-if="SHOW_STEP_PROGRESS_VERTICAL" class="flex" :target-room="targetRoom"
                :current-step="currentStep" :selected-count="selectedSlots.length" />

            <!-- 借用須知彈出視窗 -->
            <GuidelinesModal :show="showGuidelinesModal" @close="showGuidelinesModal = false"
                @confirm="onGuidelinesConfirmed" />

            <!-- 申請表單彈出視窗 -->
            <BookingFormModal :show="showBookingFormModal" :target-room="targetRoom" :selected-slots="selectedSlots"
                :form="applicantForm" @close="showBookingFormModal = false"
                @update:form="Object.assign(applicantForm, $event)" @submit="submitForm" />

            <BookingFeedbackModal
                :show="showFeedbackModal"
                :title="feedbackTitle"
                :message="feedbackMessage"
                :type="feedbackType"
                @close="closeFeedbackModal"
            />
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// --- 佈局元件 ---
import { AppLayout } from '@/layouts';

// --- 常數配置 ---
import { SHOW_STEP_PROGRESS_VERTICAL } from '@/constants';

// --- 型別定義 ---
import type {
    HomePageProps,
    OccupiedData,
    Room,
    SelectedSlot,
} from '@/types';

// --- Composables ---
import { useBookingFlow, useDateSelection, useHighlight } from '@/composables';

// --- 工具函式 ---
import { findRoomByCode, formatDateStringForDisplay } from '@/utils';

// --- 元件引用 ---
import {
    BookingFeedbackModal,
    BookingFormModal,
    BookingProgressStepper,
    ClassroomOverviewGrid,
    ClassroomNavigator,
    GuidelinesModal,
    ScheduleGrid,
    ScheduleToolbar,
    SelectionSummaryPanel,
    WelcomeGuide,
} from '@/components';

// --- Props 定義 ---
const props = defineProps<HomePageProps>();
const page = usePage();
const daysLookup = ['日', '一', '二', '三', '四', '五', '六'];
const viewMode = ref<'overview' | 'room'>('room');

// --- 狀態管理：教室選擇 ---
const targetRoom = ref<Room | null>(
    findRoomByCode(props.buildings, props.filters.room_code)
);

// occupiedData 根據目前選中的教室從 allOccupiedData 取值
const occupiedData = computed(() => {
    if (!targetRoom.value) return {};
    return props.allOccupiedData[targetRoom.value.code] || {};
});

const allRooms = computed<Room[]>(() => props.buildings.flatMap((building) => building.rooms));
const currentDayName = computed<string>(() => daysLookup[baseDate.value.getDay()]);
const overviewOccupiedData = computed<OccupiedData>(() => {
    const dateKey = currentDateYYYYMMDD.value;
    const data: OccupiedData = {};
    allRooms.value.forEach((room) => {
        data[room.code] = props.allOccupiedData[room.code]?.[dateKey] || {};
    });
    return data;
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
    fetchData,
} = useDateSelection({
    initialDate: props.filters.date,
    targetRoomCode: () => (viewMode.value === 'room' ? (targetRoom.value?.code ?? null) : null),
    navigationMode: () => (viewMode.value === 'overview' ? 'day' : 'week'),
    onDateChange: () => {
        selectedSlots.value = [];
        bookingFlow.currentStep.value = 1;
    },
});

// 監聽 filters.date 變化
watchFiltersDate(() => props.filters.date);

// --- 時段選取狀態 (直接管理) ---
const selectedSlots = ref<SelectedSlot[]>([]);

// --- Composable: 預約流程 ---
const bookingFlow = useBookingFlow({
    getTargetRoom: () => targetRoom.value,
    getSelectedSlots: () => selectedSlots.value,
    onReset: () => {
        targetRoom.value = null;
        selectedSlots.value = [];
    },
    onSubmitSuccess: () => {
        selectedSlots.value = [];
        currentStep.value = 1;
    },
});

const {
    currentStep,
    showGuidelinesModal,
    showBookingFormModal,
    showFeedbackModal,
    feedbackTitle,
    feedbackMessage,
    feedbackType,
    applicantForm,
    nextStep,
    closeFeedbackModal,
    openFeedbackModal,
    onGuidelinesConfirmed,
    submitForm,
} = bookingFlow;

watch(
    () => page.props.flash?.success,
    (successMessage) => {
        if (!successMessage || typeof successMessage !== 'string') return;
        openFeedbackModal('申請已送出', successMessage, 'success');
    },
    { immediate: true },
);

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
    return `${year}/${month}/${day} (${daysLookup[d.getDay()]})`;
});

const syncRoomCodeInUrl = (roomCode: string | null) => {
    if (typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    if (roomCode) {
        url.searchParams.set('room_code', roomCode);
    } else {
        url.searchParams.delete('room_code');
    }
    window.history.replaceState({}, '', url.toString());
};

const selectOverview = () => {
    viewMode.value = 'overview';
    targetRoom.value = null;
    currentStep.value = 1;
    selectedSlots.value = [];
    syncRoomCodeInUrl(null);
    fetchData();
};

// --- 操作邏輯：教室選擇 ---
const selectRoom = (room: Room) => {
    viewMode.value = 'room';
    if (targetRoom.value?.id !== room.id) {
        targetRoom.value = room;
        currentStep.value = 1;
        selectedSlots.value = [];
    }
    syncRoomCodeInUrl(room.code);
};

const handleOverviewRoomChange = (roomCode: string | null) => {
    if (!roomCode) {
        targetRoom.value = null;
        syncRoomCodeInUrl(null);
        currentStep.value = 1;
        return;
    }

    const room = allRooms.value.find((item) => item.code === roomCode) || null;
    targetRoom.value = room;
    syncRoomCodeInUrl(room?.code ?? null);
    currentStep.value = 1;
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
    viewMode.value = 'room';
    syncRoomCodeInUrl(null);
};
</script>
