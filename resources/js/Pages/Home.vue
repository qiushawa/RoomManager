<template>
    <div
        class="mx-auto flex h-screen max-w-[90vw] flex-row overflow-hidden border border-gray-200 bg-white font-sans text-gray-700"
    >
        <RoomSidebar
            :buildings="buildings"
            :active-room-code="targetRoom?.code"
            @select-room="selectRoom"
            class="hidden md:flex"
        />

        <main
            class="relative flex h-full flex-1 flex-col overflow-hidden border-r border-gray-100"
        >
            <InstructionPanel v-if="!targetRoom" class="overflow-y-auto" />

            <div v-else class="relative flex h-full flex-1 flex-col">
                <header
                    class="z-10 flex shrink-0 items-center justify-between border-b border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div>
                        <span
                            class="text-xs tracking-wider text-gray-500 uppercase"
                            >{{ targetRoom.code }}</span
                        >
                        <h2
                            class="flex items-center gap-2 text-2xl font-bold text-[#4a90e2]"
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

                <div
                    class="flex flex-1 flex-col overflow-hidden bg-white p-2 md:p-4"
                >
                    <TimeTable
                        v-if="currentStep === 1"
                        class="h-full"
                        :week-dates="weekDates"
                        :periods="periods"
                        :occupied-data="occupiedData"
                        v-model="selectedSlots"
                    />

                    <div
                        v-if="
                            currentStep === 1 &&
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

                    <div
                        v-if="currentStep === 2"
                        class="h-full overflow-y-auto p-4"
                    >
                        <BookingForm
                            :target-room="targetRoom"
                            :selected-slots="selectedSlots"
                            :form="applicantForm"
                            @update:form="Object.assign(applicantForm, $event)"
                        />
                    </div>
                </div>

                <ActionFooter
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
        </main>

        <StepProgressVertical
            class="hidden lg:flex"
            :target-room="targetRoom"
            :current-step="currentStep"
            :selected-count="selectedSlots.length"
        />
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

// --- 型別定義 ---
import type {
    ApplicantForm,
    Building,
    OccupiedData,
    Period,
    Room,
    SelectedSlot,
    Step,
    WeekDate,
} from '@/types';

// --- 元件引用 ---
import ActionFooter from '@/components/ActionFooter.vue';
import BookingForm from '@/components/BookingForm.vue';
import InstructionPanel from '@/components/InstructionPanel.vue';
import RoomSidebar from '@/components/RoomSidebar.vue';
import StepProgressVertical from '@/components/StepProgressVertical.vue'; // 引入新元件
import TimeTable from '@/components/TimeTable.vue';
// --- Props 定義 ---
const props = defineProps<{
    buildings: Building[];
    periods: Period[];
    allOccupiedData: Record<string, OccupiedData>; // 改為接收所有資料
    filters: {
        date: string;
        room_code?: string;
    };
}>();

// --- 工具函式 ---
const daysLookup = ['日', '一', '二', '三', '四', '五', '六'];

const findRoomByCode = (code?: string): Room | null => {
    if (!code) return null;
    for (const building of props.buildings) {
        const found = building.rooms.find((r) => r.code === code);
        if (found) return found;
    }
    return null;
};

// --- 狀態管理：資料與介面 ---
const baseDate = ref<Date>(new Date(props.filters.date));
// occupiedData 改為 computed，根據目前選中的教室從 allOccupiedData 取值
const occupiedData = computed(() => {
    if (!targetRoom.value) return {};
    return props.allOccupiedData[targetRoom.value.code] || {};
});

const targetRoom = ref<Room | null>(findRoomByCode(props.filters.room_code));
const currentStep = ref<Step>(1);
const selectedSlots = ref<SelectedSlot[]>([]);

// --- 狀態管理：表單 ---
const applicantForm = reactive<ApplicantForm>({
    name: '',
    identity_code: '',
    email: '',
    phone: '',
    department: '資訊工程系',
    teacher: '',
    reason: '',
});

// --- 計算屬性 ---
const currentDateYYYYMMDD = computed(() => {
    const d = baseDate.value;
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

const footerDateDisplay = computed<string>(() => {
    if (selectedSlots.value.length > 0) {
        const [y, m, d] = selectedSlots.value[0].date.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        return `${y}/${String(m).padStart(2, '0')}/${String(d).padStart(2, '0')} (${daysLookup[dateObj.getDay()]})`;
    }
    const d = baseDate.value;
    return `${d.getFullYear()}/${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getDate()).padStart(2, '0')} (${daysLookup[d.getDay()]})`;
});

const weekDates = computed<WeekDate[]>(() => {
    const dates: WeekDate[] = [];
    const startOfWeek = new Date(baseDate.value);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());

    for (let i = 0; i < 7; i++) {
        const d = new Date(startOfWeek);
        d.setDate(d.getDate() + i);
        dates.push({
            date: String(d.getDate()).padStart(2, '0'),
            dayName: daysLookup[i],
            fullDate: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`,
        });
    }
    return dates;
});

const isConsecutive = computed(() => {
    if (selectedSlots.value.length <= 1) return true;
    const indexes = selectedSlots.value
        .map((slot) => props.periods.findIndex((p) => p.code === slot.period))
        .sort((a, b) => a - b);

    for (let i = 0; i < indexes.length - 1; i++) {
        if (indexes[i + 1] !== indexes[i] + 1) return false;
    }
    return true;
});

// --- 監聽器 ---
watch(
    () => props.filters.date,
    (newDateStr) => (baseDate.value = new Date(newDateStr)),
);

// --- 操作邏輯：資料獲取與導航 ---
const fetchData = () => {
    // 當沒有選擇教室時,不發送請求
    if (!targetRoom.value) {
        return;
    }

    // 只有日期變更時才需要向後端請求
    const year = baseDate.value.getFullYear();
    const month = String(baseDate.value.getMonth() + 1).padStart(2, '0');
    const day = String(baseDate.value.getDate()).padStart(2, '0');
    const dateStr = `${year}-${month}-${day}`;

    router.get(
        '/Home',
        {
            room_code: targetRoom.value?.code, // 保持當前教室
            date: dateStr,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['allOccupiedData', 'filters'], // 只更新資料
            replace: true,
        },
    );
};

const selectRoom = (room: Room) => {
    if (targetRoom.value?.id !== room.id) {
        targetRoom.value = room;
        currentStep.value = 1;
        selectedSlots.value = [];

        const url = new URL(window.location.href);
        url.searchParams.set('room_code', room.code);
        window.history.replaceState({}, '', url.toString());
    }
};

const updateDate = (dateStr: string) => {
    if (!dateStr) return;
    baseDate.value = new Date(dateStr);
    selectedSlots.value = [];
    currentStep.value = 1;
    fetchData();
};

const changeWeek = (offset: number) => {
    const newDate = new Date(baseDate.value);
    newDate.setDate(newDate.getDate() + offset * 7);
    baseDate.value = newDate;
    fetchData();
};

const resetToToday = () => {
    baseDate.value = new Date();
    fetchData();
};

const resetSelection = () => {
    targetRoom.value = null;
    selectedSlots.value = [];
    currentStep.value = 1;

    Object.assign(applicantForm, {
        name: '',
        identity_code: '',
        email: '',
        phone: '',
        department: '資訊工程系',
        teacher: '',
        reason: '',
    });

    router.get('/Home', {}, { replace: true });
};
// --- 操作邏輯：流程控制 ---
const nextStep = () => {
    if (selectedSlots.value.length === 0) return;

    const uniqueDates = new Set(selectedSlots.value.map((s) => s.date));
    if (uniqueDates.size > 1) {
        alert('不能跨日借用，請重新選擇');
        return;
    }

    if (!isConsecutive.value) {
        alert('請選擇連續的時段，中間不能有空堂！');
        return;
    }

    currentStep.value = 2;
};

const submitForm = () => {
    if (
        !applicantForm.name ||
        !applicantForm.identity_code ||
        !applicantForm.reason
    ) {
        alert('請填寫完整資料 (姓名、學號、事由為必填)');
        return;
    }

    const payload = {
        room_code: targetRoom.value?.code,
        classroom_id: targetRoom.value?.id,
        date: selectedSlots.value[0]?.date,
        slots: selectedSlots.value.map((s) => s.period),
        applicant: { ...applicantForm },
    };

    console.group('送出的申請資料');
    console.log('完整 Payload:', payload);
    console.groupEnd();
};
</script>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
