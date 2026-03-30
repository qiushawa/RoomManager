<template>
    <Head title="長期借用管理 | Admin" />
    <AdminLayout title="長期借用管理">
        <div class="admin-page-container">

            <!-- 成功訊息 -->
            <p v-if="$page.props.flash?.success"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-sm text-emerald-400">
                {{ $page.props.flash.success }}
            </p>

            <!-- 模式切換 Tab -->
            <div class="flex gap-1 border-b border-a-border-2">
                <button type="button" class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'manual'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'manual'">
                    手動新增
                </button>
                <button type="button" class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'import'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'import'">
                    教室課表匯入
                </button>
                <button type="button" class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'records'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'records'">
                    已儲存記錄
                </button>
            </div>

            <!-- ── 手動新增 ── -->
            <section v-if="activeMode === 'manual'" class="flex flex-col gap-8">
                <div class="rounded-2xl border border-a-border-card bg-a-surface p-6 sm:p-8 shadow-sm">

                    <div v-if="manualForm.errors.semester || manualForm.errors.periods"
                        class="mb-6 flex items-start gap-3 rounded-xl border border-red-500/20 bg-red-500/5 p-4 text-sm text-red-500">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p>{{ manualForm.errors.semester || manualForm.errors.periods }}</p>
                    </div>

                    <form class="space-y-8" @submit.prevent="handleManualSubmit">

                        <!-- ── 基本資訊 ── -->
                        <fieldset class="space-y-5">
                            <legend class="w-full border-b border-a-divider pb-2 text-xs font-medium uppercase tracking-widest text-a-text-muted">
                                基本資訊
                            </legend>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">指導老師</label>
                                    <input v-model.trim="manualForm.teacher_name" type="text" placeholder="如：王大明"
                                        class="w-full rounded-xl border border-a-border-2 bg-transparent px-4 py-2.5 text-sm text-a-text-body transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none" />
                                    <p v-if="manualForm.errors.teacher_name" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.teacher_name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">課程名稱</label>
                                    <input v-model.trim="manualForm.course_name" type="text" placeholder="如：資料結構"
                                        class="w-full rounded-xl border border-a-border-2 bg-transparent px-4 py-2.5 text-sm text-a-text-body transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none" />
                                    <p v-if="manualForm.errors.course_name" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.course_name }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <!-- ── 借用時段 ── -->
                        <fieldset class="space-y-5">
                            <legend class="w-full border-b border-a-divider pb-2 text-xs font-medium uppercase tracking-widest text-a-text-muted">
                                借用時段
                            </legend>

                            <!-- 教室 + 開始日期 + 結束日期（同一行） -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-[minmax(160px,220px)_1fr_1fr]">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">教室</label>
                                    <select v-model="manualForm.classroom_id"
                                        class="w-full rounded-xl border border-a-border-2 bg-a-surface px-4 py-2.5 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary">
                                        <option value="">請選擇教室</option>
                                        <option v-for="room in classrooms" :key="room.id" :value="room.id">
                                            {{ room.code }} - {{ room.name }}
                                        </option>
                                    </select>
                                    <p v-if="manualForm.errors.classroom_id" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.classroom_id }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">開始日期</label>
                                    <input v-model="manualForm.start_date" type="date"
                                        class="w-full rounded-xl border border-a-border-2 bg-a-surface px-3 py-2.5 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary" />
                                    <p v-if="manualForm.errors.start_date" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.start_date }}
                                    </p>
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between">
                                        <label class="text-sm font-medium text-a-text-body">結束日期</label>
                                        <button type="button" :disabled="!props.semesterEndDate"
                                            class="text-xs font-medium text-primary hover:underline disabled:text-a-text-muted disabled:no-underline"
                                            @click="applyQuickDateRange">
                                            帶入至學期末
                                        </button>
                                    </div>
                                    <input v-model="manualForm.end_date" type="date"
                                        class="w-full rounded-xl border border-a-border-2 bg-a-surface px-3 py-2.5 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary" />
                                    <p v-if="manualForm.errors.end_date" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.end_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- 節次選取（獨立一行） -->
                            <div>
                                <label class="mb-3 block text-sm font-medium text-a-text-body">節次選取</label>
                                <!-- overflow-visible 確保 tooltip 不被容器裁切 -->
                                <div class="overflow-visible rounded-xl border border-a-border-2 bg-a-surface p-3 shadow-sm">
                                    <ScheduleGrid
                                        :week-dates="manualWeekDates"
                                        :periods="manualGridPeriods"
                                        :occupied-data="manualConflictOccupiedData"
                                        :model-value="manualSelectedSlots"
                                        :show-header-date="false"
                                        :allow-cross-date-selection="true"
                                        :allow-occupied-selection="true"
                                        :show-period-time="false"
                                        period-column-width-class="w-16"
                                        :theme="adminScheduleGridTheme"
                                        @update:model-value="handleManualScheduleChange"
                                        @occupied-click="handleManualOccupiedClick"
                                    />
                                </div>
                                <p v-if="manualForm.errors.periods" class="mt-2 text-xs text-red-400">
                                    {{ manualForm.errors.periods }}
                                </p>
                            </div>
                        </fieldset>

                        <!-- ── 衝突狀態 + 送出 ── -->
                        <div class="flex items-center justify-between border-t border-a-divider pt-6">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="h-2 w-2 rounded-full transition-colors"
                                    :class="manualConflictLoading
                                        ? 'animate-pulse bg-primary'
                                        : manualConflictSummary
                                            ? 'bg-emerald-500'
                                            : 'bg-a-text-muted/40'">
                                </span>
                                <span v-if="manualConflictLoading" class="text-a-text-muted">衝突檢查中…</span>
                                <span v-else-if="manualConflictError" class="text-amber-400">{{ manualConflictError }}</span>
                                <span v-else-if="manualConflictSummary && remainingConflictCount > 0" class="text-amber-400">
                                    偵測到衝突，請依格內符號提示調整後再送出
                                </span>
                                <span v-else-if="manualConflictSummary" class="text-emerald-400">無衝突，可直接送出</span>
                                <span v-else class="text-a-text-muted">尚未檢查</span>
                            </div>

                            <button type="submit" :disabled="!canSubmitManual || manualForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-primary/90 hover:shadow-lg focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-a-divider disabled:text-a-text-muted disabled:shadow-none">
                                <svg v-if="manualForm.processing" class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ manualForm.processing ? '處理中…' : '確認新增記錄' }}
                            </button>
                        </div>

                    </form>
                </div>

                                <ConflictActionModal
                                        :show="conflictActionModalOpen"
                                        :loading="manualConflictLoading"
                                        :active-conflict-slot="activeConflictSlot"
                                        :weekday-name-map="WEEKDAY_NAME_MAP"
                                        :period-label-text="periodLabelText"
                                        @close="closeConflictActionModal"
                                        @action="applyConflictAction"
                                />
            </section>

            <!-- ── 已儲存記錄 ── -->
            <section v-else-if="activeMode === 'records'"
                class="rounded-2xl border border-a-border-card bg-a-surface p-6 sm:p-8 shadow-sm">
                <div class="mb-5 border-b border-a-divider pb-4">
                    <h3 class="text-base font-bold text-a-text">已儲存記錄</h3>
                    <p class="mt-1 text-sm text-a-text-muted">可在此檢視與撤回本學期手動新增的長期借用。</p>
                </div>
                <ManualRecordList
                    :manual-records="manualRecords"
                    @revoke="revokeManualRecord"
                />
            </section>

            <!-- ── 教室課表匯入 ── -->
            <section v-else-if="activeMode === 'import'" class="flex flex-col gap-5">

                <p v-if="importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError"
                    class="rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs text-red-400">
                    {{ importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError }}
                </p>

                <div class="space-y-4">
                    <ImportBuildingPanel v-for="buildingCode in buildingOrder" :key="buildingCode"
                        :building-code="buildingCode" :building-label="buildingLabels[buildingCode]"
                        :rooms="classroomsByBuilding[buildingCode]"
                        :selected-classroom-set="selectedClassroomSet"
                        @select-all="selectAllInBuilding"
                        @toggle-room="toggleClassroomSelection"
                        @revoke-room="revokeImport" />
                </div>

                <div v-if="isAwaitingImportConfirmation"
                    class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
                    已完成預覽，請確認內容後再次按下「確認匯入」。
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-a-border-2 pt-4">
                    <button type="button"
                        class="text-xs text-a-text-muted underline-offset-2 hover:text-a-text hover:underline"
                        @click="clearSelectedClassrooms">
                        清空選取
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button"
                            :disabled="previewLoading || importForm.processing || selectedClassroomIds.length === 0"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50"
                            @click="handleImportAction">
                            {{ importActionLabel }}
                        </button>
                    </div>
                </div>

                <ImportPreviewTable :classrooms="classrooms" :preview-schedules="previewSchedules" />
            </section>

        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { AdminLayout } from '@/layouts';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ScheduleGrid } from '@/components';
import { useAdminTheme } from '@/composables';
import {
    LONG_TERM_BUILDING_LABELS,
    LONG_TERM_BUILDING_ORDER,
} from '@/constants';
import { ConflictActionModal, ImportBuildingPanel, ImportPreviewTable, ManualRecordList } from '@/components/admin';
import type {
    BuildingCode,
    ClassroomOption,
    ImportConfig,
    ManualConflictItem,
    ManualConflictKind,
    ManualConflictSummary,
    ManualFormData,
    ManualRecord,
    OccupiedData,
    Period,
    PreviewSchedule,
    SelectedSlot,
    SlotResolutionAction,
    TimeSlotOption,
    WeekDate,
} from '@/types';
import { formatDateToYYYYMMDD, getRoomBuildingCode } from '@/utils';

const props = defineProps<{
    classrooms: ClassroomOption[];
    timeSlots: TimeSlotOption[];
    manualRecords: ManualRecord[];
    semesterEndDate: string | null;
    importConfig: ImportConfig;
}>();

const { isDark } = useAdminTheme();
const adminScheduleGridTheme = computed<'light' | 'dark'>(() => (isDark.value ? 'dark' : 'light'));

// ── 常數 ──────────────────────────────────────────────
const buildingOrder = LONG_TERM_BUILDING_ORDER;
const buildingLabels = LONG_TERM_BUILDING_LABELS;

const WEEKDAY_NAME_MAP: Record<number, string> = {
    1: '一',
    2: '二',
    3: '三',
    4: '四',
    5: '五',
    6: '六',
    7: '日',
};

const FULL_WEEK_DAYS = [1, 2, 3, 4, 5, 6, 7] as const;
const MANUAL_LONG_TERM_DRAFT_KEY = 'admin-long-term-manual-draft-v1';

interface OccupiedClickPayload {
    date: string;
    period: string;
    item?: unknown;
}

interface ActiveConflictSlot {
    slotKey: string;
    dayOfWeek: number;
    period: number;
    kind: ManualConflictKind;
    conflictKey?: string;
    bookingId?: number;
}

interface ShortTermConflictEntry {
    date: string;
    conflictDate: string;
    period: number;
    dayOfWeek: number;
    slotKey: string;
    conflictKey: string;
    bookingId: number;
    kind: 'short_term_pending' | 'short_term_approved';
    typeText: string;
    counterpart: string;
}

interface ManualDraftPayload {
    manualForm: {
        classroom_id: number | '';
        teacher_name: string;
        course_name: string;
        day_of_week: number[];
        start_date: string;
        end_date: string;
        periods: number[];
    };
    manualSelectedSlots: SelectedSlot[];
    slotResolutionMap: Record<string, SlotResolutionAction>;
    manualConflicts: ManualConflictItem[];
    manualConflictSummary: ManualConflictSummary | null;
}

// ── 狀態 ──────────────────────────────────────────────
const activeMode = ref<'manual' | 'import' | 'records'>('manual');
const selectedClassroomIds = ref<number[]>([]);
const importErrorMessage = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const previewSchedules = ref<PreviewSchedule[]>([]);
const isAwaitingImportConfirmation = ref(false);

// ── 手動表單 ──────────────────────────────────────────
const manualForm = useForm<ManualFormData>({
    classroom_id: '',
    teacher_name: '',
    course_name: '',
    day_of_week: [...FULL_WEEK_DAYS],
    start_date: '',
    end_date: '',
    periods: [],
    slot_resolutions: {},
});

const manualSelectedSlots = ref<SelectedSlot[]>([]);
const manualConflictLoading = ref(false);
const manualConflictError = ref('');
const manualConflicts = ref<ManualConflictItem[]>([]);
const manualConflictSummary = ref<ManualConflictSummary | null>(null);
const slotResolutionMap = ref<Record<string, SlotResolutionAction>>({});
const conflictActionModalOpen = ref(false);
const activeConflictSlot = ref<ActiveConflictSlot | null>(null);
const isRestoringDraft = ref(false);

const manualGridPeriods = computed<Period[]>(() => {
    let nonLunchOrder = 0;

    return props.timeSlots.map((slot, index) => {
        const isLunch = slot.name === '午休';
        if (!isLunch) {
            nonLunchOrder += 1;
        }

        return {
            id: slot.id,
            code: String(index + 1),
            label: isLunch ? '午休' : String(nonLunchOrder),
        };
    });
});

const manualPeriodDisplayLabelByCode = computed<Record<number, string>>(() => {
    const map: Record<number, string> = {};

    manualGridPeriods.value.forEach((period) => {
        const periodCode = Number(period.code);
        if (Number.isFinite(periodCode)) {
            map[periodCode] = period.label;
        }
    });

    return map;
});

// 固定產生週一到週日，不再依賴 day_of_week
const manualWeekDates = computed<WeekDate[]>(() => {
    const today = new Date();
    const day = today.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    const monday = new Date(today);
    monday.setDate(today.getDate() + mondayOffset);

    return [1, 2, 3, 4, 5, 6, 7].map((weekday) => {
        const target = new Date(monday);
        target.setDate(monday.getDate() + (weekday - 1));
        const fullDate = formatDateToYYYYMMDD(target);
        return {
            date: String(target.getDate()).padStart(2, '0'),
            dayName: WEEKDAY_NAME_MAP[weekday] ?? String(weekday),
            fullDate,
        };
    });
});

function applyQuickDateRange() {
    if (!props.semesterEndDate) return;
    const today = formatDateToYYYYMMDD(new Date());
    const end = props.semesterEndDate >= today ? props.semesterEndDate : today;
    manualForm.start_date = today;
    manualForm.end_date = end;
}

function isoWeekdayFromDateString(dateString: string): number | null {
    const [yearText, monthText, dayText] = dateString.split('-');
    const year = Number(yearText);
    const month = Number(monthText);
    const day = Number(dayText);

    if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
        return null;
    }

    const date = new Date(year, month - 1, day);
    if (Number.isNaN(date.getTime())) return null;

    const jsWeekday = date.getDay();
    return jsWeekday === 0 ? 7 : jsWeekday;
}

// manualDateToWeekdayMap 從 manualWeekDates 直接建立，不再依賴 day_of_week
const manualDateToWeekdayMap = computed<Record<string, number>>(() => {
    const mapping: Record<string, number> = {};

    manualWeekDates.value.forEach((day) => {
        const weekday = isoWeekdayFromDateString(day.fullDate);
        if (weekday) {
            mapping[day.fullDate] = weekday;
        }
    });

    return mapping;
});

const slotKindPriority: Record<ManualConflictKind, number> = {
    short_term_pending: 1,
    short_term_approved: 2,
    schedule: 3,
};

const validActionsByKind: Record<ManualConflictKind, SlotResolutionAction[]> = {
    schedule: ['cancel_slot'],
    short_term_pending: ['review_pending', 'reject_and_override'],
    short_term_approved: ['defer_to_short_term', 'override_with_long_term'],
};

function toSlotKey(dayOfWeek: number, period: number): string {
    return `${dayOfWeek}:${period}`;
}

function toCellKey(date: string, period: number): string {
    return `${date}|${period}`;
}

function buildShortTermConflictResolutionKey(slotKey: string, bookingDateId: number, timeSlotId: number): string {
    return `${slotKey}|bd:${bookingDateId}|ts:${timeSlotId}`;
}

function periodLabelText(period: number): string {
    return manualPeriodDisplayLabelByCode.value[period] ?? String(period);
}

function removeSelectedSlotByWeekdayPeriod(dayOfWeek: number, period: number) {
    manualSelectedSlots.value = manualSelectedSlots.value.filter((slot) => {
        const weekday = isoWeekdayFromDateString(slot.date);
        return !(weekday === dayOfWeek && Number(slot.period) === period);
    });
}

const conflictKindBySlot = computed<Record<string, ManualConflictKind>>(() => {
    const mapped: Record<string, ManualConflictKind> = {};

    manualConflicts.value.forEach((item) => {
        const weekdays = item.conflict_kind === 'schedule'
            ? [item.day_of_week]
            : Array.from(new Set(
                item.conflict_dates
                    .map((date) => isoWeekdayFromDateString(date))
                    .filter((weekday): weekday is number => !!weekday),
            ));

        weekdays.forEach((weekday) => {
            item.overlap_periods.forEach((period) => {
                const key = toSlotKey(weekday, period);
                const currentKind = mapped[key];
                if (!currentKind || slotKindPriority[item.conflict_kind] > slotKindPriority[currentKind]) {
                    mapped[key] = item.conflict_kind;
                }
            });
        });
    });

    return mapped;
});

const selectedSlotKeySet = computed<Set<string>>(() => new Set(
    manualSelectedSlots.value
        .map((slot) => {
            const weekday = isoWeekdayFromDateString(slot.date);
            const period = Number(slot.period);
            if (!weekday || !Number.isFinite(period) || period <= 0) return null;
            return toSlotKey(weekday, period);
        })
        .filter((slotKey): slotKey is string => !!slotKey),
));

const shortTermConflictEntries = computed<ShortTermConflictEntry[]>(() => {
    const entries: ShortTermConflictEntry[] = [];
    const dateToWeekday = manualDateToWeekdayMap.value;

    const datesByWeekday = new Map<number, string[]>();
    manualWeekDates.value.forEach((day) => {
        const weekday = dateToWeekday[day.fullDate];
        if (!weekday) return;
        if (!datesByWeekday.has(weekday)) datesByWeekday.set(weekday, []);
        datesByWeekday.get(weekday)?.push(day.fullDate);
    });

    manualConflicts.value.forEach((item) => {
        if (item.conflict_kind !== 'short_term_pending' && item.conflict_kind !== 'short_term_approved') {
            return;
        }

        const typeText = conflictTypeText(item.conflict_kind);
        const counterpart = item.applicant_name || item.teacher_name || '—';

        const conflictSlots = Array.isArray(item.conflict_slots) ? item.conflict_slots : [];
        conflictSlots.forEach((slot) => {
            const period = Number(slot.period);
            const dayOfWeek = Number(slot.day_of_week);
            const conflictDate = typeof slot.date === 'string' ? slot.date : '';
            const bookingDateId = Number(slot.booking_date_id);
            const timeSlotId = Number(slot.time_slot_id);
            if (!Number.isFinite(period) || period <= 0) return;
            if (!Number.isFinite(dayOfWeek) || dayOfWeek < 1 || dayOfWeek > 7) return;
            if (!conflictDate) return;
            if (!Number.isFinite(bookingDateId) || bookingDateId <= 0) return;
            if (!Number.isFinite(timeSlotId) || timeSlotId <= 0) return;

            const displayDate = (datesByWeekday.get(dayOfWeek) ?? [])[0];
            if (!displayDate) return;

            const slotKey = toSlotKey(dayOfWeek, period);
            if (!selectedSlotKeySet.value.has(slotKey)) {
                return;
            }

            entries.push({
                date: displayDate,
                conflictDate,
                period,
                dayOfWeek,
                slotKey,
                conflictKey: buildShortTermConflictResolutionKey(slotKey, bookingDateId, timeSlotId),
                bookingId: Number(item.booking_id ?? 0),
                kind: item.conflict_kind as 'short_term_pending' | 'short_term_approved',
                typeText,
                counterpart,
            });
        });
    });

    return entries;
});

const shortTermQueueByCell = computed<Record<string, ShortTermConflictEntry[]>>(() => {
    const grouped: Record<string, ShortTermConflictEntry[]> = {};

    shortTermConflictEntries.value.forEach((entry) => {
        const action = slotResolutionMap.value[entry.conflictKey] ?? slotResolutionMap.value[entry.slotKey];
        if (isResolvedActionForKind(entry.kind, action)) {
            return;
        }

        const cellKey = toCellKey(entry.date, entry.period);
        if (!grouped[cellKey]) grouped[cellKey] = [];
        grouped[cellKey].push(entry);
    });

    Object.values(grouped).forEach((queue) => {
        queue.sort((a, b) => {
            if (a.conflictDate === b.conflictDate) return 0;
            return a.conflictDate < b.conflictDate ? -1 : 1;
        });
    });

    return grouped;
});

function isResolvedActionForKind(kind: ManualConflictKind, action: SlotResolutionAction | undefined): boolean {
    if (!action) return false;
    if (kind === 'schedule') return action === 'cancel_slot';
    if (kind === 'short_term_pending') return action === 'reject_and_override' || action === 'review_pending';
    return action === 'defer_to_short_term' || action === 'override_with_long_term';
}

const unresolvedConflictSlots = computed<string[]>(() => {
    const unresolvedSlotKeys = new Set<string>();

    Object.entries(conflictKindBySlot.value).forEach(([slotKey, kind]) => {
        if (!selectedSlotKeySet.value.has(slotKey)) return;

        if (kind === 'schedule') {
            const action = slotResolutionMap.value[slotKey];
            if (action !== 'cancel_slot') {
                unresolvedSlotKeys.add(slotKey);
            }
        }
    });

    shortTermConflictEntries.value.forEach((entry) => {
        const action = slotResolutionMap.value[entry.conflictKey] ?? slotResolutionMap.value[entry.slotKey];
        if (!action || !isResolvedActionForKind(entry.kind, action)) {
            unresolvedSlotKeys.add(`${entry.slotKey}|${entry.conflictKey}`);
        }
    });

    return Array.from(unresolvedSlotKeys);
});

const remainingConflictCount = computed(() => unresolvedConflictSlots.value.length);

function restoreManualDraft() {
    if (typeof window === 'undefined') return;

    try {
        isRestoringDraft.value = true;
        const raw = window.localStorage.getItem(MANUAL_LONG_TERM_DRAFT_KEY);
        if (!raw) return;

        const draft = JSON.parse(raw) as ManualDraftPayload;
        manualForm.classroom_id = draft.manualForm.classroom_id;
        manualForm.teacher_name = draft.manualForm.teacher_name;
        manualForm.course_name = draft.manualForm.course_name;
        manualForm.day_of_week = Array.isArray(draft.manualForm.day_of_week)
            ? draft.manualForm.day_of_week
            : [...FULL_WEEK_DAYS];
        manualForm.start_date = draft.manualForm.start_date;
        manualForm.end_date = draft.manualForm.end_date;
        manualForm.periods = Array.isArray(draft.manualForm.periods) ? draft.manualForm.periods : [];
        manualSelectedSlots.value = Array.isArray(draft.manualSelectedSlots) ? draft.manualSelectedSlots : [];
        slotResolutionMap.value = draft.slotResolutionMap ?? {};
        manualConflicts.value = Array.isArray(draft.manualConflicts) ? draft.manualConflicts : [];
        manualConflictSummary.value = draft.manualConflictSummary ?? null;
        manualConflictError.value = '已還原未完成的申請草稿。';
    } catch {
        window.localStorage.removeItem(MANUAL_LONG_TERM_DRAFT_KEY);
    } finally {
        isRestoringDraft.value = false;
    }
}

function saveManualDraft() {
    if (typeof window === 'undefined') return;

    const payload: ManualDraftPayload = {
        manualForm: {
            classroom_id: manualForm.classroom_id,
            teacher_name: manualForm.teacher_name,
            course_name: manualForm.course_name,
            day_of_week: [...manualForm.day_of_week],
            start_date: manualForm.start_date,
            end_date: manualForm.end_date,
            periods: [...manualForm.periods],
        },
        manualSelectedSlots: [...manualSelectedSlots.value],
        slotResolutionMap: { ...slotResolutionMap.value },
        manualConflicts: [...manualConflicts.value],
        manualConflictSummary: manualConflictSummary.value,
    };

    window.localStorage.setItem(MANUAL_LONG_TERM_DRAFT_KEY, JSON.stringify(payload));
}

function clearManualDraft() {
    if (typeof window === 'undefined') return;
    window.localStorage.removeItem(MANUAL_LONG_TERM_DRAFT_KEY);
}

function closeConflictActionModal() {
    conflictActionModalOpen.value = false;
    activeConflictSlot.value = null;
}

function handleManualOccupiedClick(payload: OccupiedClickPayload) {
    const dayOfWeek = manualDateToWeekdayMap.value[payload.date];
    const period = Number(payload.period);
    if (!dayOfWeek || !Number.isFinite(period) || period <= 0) return;

    const slotKey = toSlotKey(dayOfWeek, period);
    const item = payload.item as { status?: string; conflict_key?: string } | string | null | undefined;
    const clickedStatus = typeof item === 'string'
        ? item
        : (item && typeof item === 'object' ? item.status ?? null : null);
    const kind = conflictKindFromOccupiedStatus(clickedStatus) ?? conflictKindBySlot.value[slotKey];
    if (!kind) return;

    const conflictKey = item && typeof item === 'object' ? (item.conflict_key ?? undefined) : undefined;
    const bookingId = item && typeof item === 'object' ? Number((item as { booking_id?: number }).booking_id ?? 0) : 0;

    activeConflictSlot.value = {
        slotKey,
        dayOfWeek,
        period,
        kind,
        conflictKey,
        bookingId: Number.isFinite(bookingId) ? bookingId : 0,
    };
    conflictActionModalOpen.value = true;
}

async function applyConflictAction(action: SlotResolutionAction) {
    const slot = activeConflictSlot.value;
    if (!slot) return;

    if (!validActionsByKind[slot.kind].includes(action)) {
        manualConflictError.value = '此衝突類型不支援該操作。';
        return;
    }

    if (action === 'review_pending') {
        if ((slot.bookingId ?? 0) > 0) {
            shortTermConflictEntries.value
                .filter((entry) => entry.kind === 'short_term_pending' && entry.bookingId === slot.bookingId)
                .forEach((entry) => {
                    slotResolutionMap.value[entry.conflictKey] = action;
                });
        } else {
            const targetKey = slot.conflictKey ?? slot.slotKey;
            slotResolutionMap.value[targetKey] = action;
        }
        saveManualDraft();
        closeConflictActionModal();
        window.location.href = '/admin/reviews?from=long-term-borrowing';
        return;
    }

    if (action === 'cancel_slot') {
        if (slot.conflictKey) {
            slotResolutionMap.value[slot.conflictKey] = action;
        } else {
            slotResolutionMap.value[slot.slotKey] = action;
        }
        removeSelectedSlotByWeekdayPeriod(slot.dayOfWeek, slot.period);
        closeConflictActionModal();
        return;
    }

    if (action === 'defer_to_short_term') {
        if (slot.conflictKey) {
            // 僅標記當前衝突為讓給短借，不移除整個節次選取。
            slotResolutionMap.value[slot.conflictKey] = action;
        } else {
            slotResolutionMap.value[slot.slotKey] = action;
        }
        closeConflictActionModal();
        return;
    }

    if (action === 'reject_and_override' || action === 'override_with_long_term') {
        const bookingId = Number(slot.bookingId ?? 0);
        if (!Number.isFinite(bookingId) || bookingId <= 0) {
            manualConflictError.value = '缺少短期借用識別資訊，請重新整理後再試。';
            return;
        }

        const actionLabel = action === 'reject_and_override'
            ? '直接覆蓋並拒絕'
            : '該節讓給長期借用';
        const firstConfirm = window.confirm(
            `【高風險操作】你選擇「${actionLabel}」。此動作會駁回整筆短期借用申請（同申請內其他日期/節次也會失效），是否繼續？`,
        );
        if (!firstConfirm) {
            return;
        }

        const secondConfirm = window.confirm(
            '請再次確認：本次駁回無法自動復原，必須由借用人重新提出申請。確定要執行嗎？',
        );
        if (!secondConfirm) {
            return;
        }

        shortTermConflictEntries.value
            .filter((entry) => entry.bookingId === bookingId)
            .forEach((entry) => {
                slotResolutionMap.value[entry.conflictKey] = action;
            });

        manualConflictLoading.value = true;
        try {
            await window.axios.post('/admin/long-term-borrowing/manual/resolve-conflict', {
                action,
                booking_id: bookingId,
            });

            await previewManualConflicts();
            manualConflictError.value = '';
        } catch (error: any) {
            const backendMessage =
                error?.response?.data?.message
                || error?.response?.data?.errors?.action?.[0]
                || error?.response?.data?.errors?.booking_id?.[0];
            manualConflictError.value = backendMessage || '衝突處理執行失敗，請稍後再試。';
        } finally {
            manualConflictLoading.value = false;
        }

        closeConflictActionModal();
        return;
    }

    const targetKey = slot.conflictKey ?? slot.slotKey;
    slotResolutionMap.value[targetKey] = action;

    closeConflictActionModal();
}

const canPreviewManualConflicts = computed(() => (
    !!manualForm.classroom_id
    && manualForm.periods.length > 0
    && !!manualForm.start_date
    && !!manualForm.end_date
));

const canSubmitManual = computed(() => (
    canPreviewManualConflicts.value
    && !manualConflictLoading.value
    && !manualForm.processing
));

const conflictStatusPriority: Record<string, number> = {
    conflict_short_term_pending: 1,
    conflict_short_term_approved: 2,
    conflict_schedule: 3,
};

function conflictStatusForKind(item: ManualConflictItem): 'conflict_short_term_pending' | 'conflict_short_term_approved' | 'conflict_schedule' {
    if (item.conflict_kind === 'short_term_pending') return 'conflict_short_term_pending';
    if (item.conflict_kind === 'short_term_approved') return 'conflict_short_term_approved';
    return 'conflict_schedule';
}

function conflictTypeText(kind: ManualConflictKind): string {
    if (kind === 'schedule') return '課表衝突';
    if (kind === 'short_term_pending') return '未審核短期借用衝突';
    return '已審核短期借用衝突';
}

function conflictKindFromOccupiedStatus(status: string | null): ManualConflictKind | null {
    if (status === 'conflict_schedule') return 'schedule';
    if (status === 'conflict_short_term_pending') return 'short_term_pending';
    if (status === 'conflict_short_term_approved') return 'short_term_approved';
    return null;
}

const manualConflictOccupiedData = computed<OccupiedData>(() => {
    const occupied: OccupiedData = {};
    const dateToWeekday = manualDateToWeekdayMap.value;

    const datesByWeekday = new Map<number, string[]>();
    manualWeekDates.value.forEach((day) => {
        const weekday = dateToWeekday[day.fullDate];
        if (!weekday) return;
        if (!datesByWeekday.has(weekday)) datesByWeekday.set(weekday, []);
        datesByWeekday.get(weekday)?.push(day.fullDate);
    });

    manualConflicts.value.forEach((item) => {
        if (!item.overlap_periods.length || item.conflict_kind !== 'schedule') return;

        const status = conflictStatusForKind(item);
        const typeText = conflictTypeText(item.conflict_kind);
        const detailText = item.conflict_kind === 'schedule'
            ? (item.course_name || '—')
            : null;
        const counterpartText = item.applicant_name || item.teacher_name || '—';

        let targetDates: string[] = [];
        if (item.conflict_kind === 'schedule') {
            targetDates = datesByWeekday.get(item.day_of_week) ?? [];
        } else {
            const weekdays = Array.from(new Set(
                item.conflict_dates
                    .map((date) => isoWeekdayFromDateString(date))
                    .filter((weekday): weekday is number => !!weekday),
            ));
            targetDates = weekdays.flatMap((weekday) => datesByWeekday.get(weekday) ?? []);
        }

        const uniqueTargetDates = Array.from(new Set(targetDates));

        uniqueTargetDates.forEach((date) => {
            if (!occupied[date]) occupied[date] = {};

            const weekday = manualDateToWeekdayMap.value[date];
            if (!weekday) return;

            item.overlap_periods.forEach((period) => {
                const code = String(period);
                const slotKey = toSlotKey(weekday, period);

                if (!selectedSlotKeySet.value.has(slotKey)) {
                    return;
                }

                if (isResolvedActionForKind(item.conflict_kind, slotResolutionMap.value[slotKey])) {
                    return;
                }

                const existing = occupied[date][code];
                const existingStatus = typeof existing === 'string' ? existing : existing?.status;
                if (
                    existingStatus
                    && (conflictStatusPriority[existingStatus] ?? 0) >= conflictStatusPriority[status]
                ) {
                    return;
                }

                occupied[date][code] = {
                    status,
                    title: typeText,
                    instructor: detailText ?? date,
                    applicant: counterpartText,
                    marker: '✗',
                };
            });
        });
    });

    Object.entries(shortTermQueueByCell.value).forEach(([cellKey, queue]) => {
        if (!queue.length) return;

        const [date, periodText] = cellKey.split('|');
        const period = Number(periodText);
        if (!date || !Number.isFinite(period)) return;

        const active = queue[0];
        if (!occupied[date]) occupied[date] = {};

        const code = String(period);
        const current = occupied[date][code];
        const currentStatus = typeof current === 'string' ? current : current?.status;
        if (currentStatus === 'conflict_schedule') {
            return;
        }

        const status = active.kind === 'short_term_pending'
            ? 'conflict_short_term_pending'
            : 'conflict_short_term_approved';

        occupied[date][code] = {
            status,
            title: active.typeText,
            instructor: active.conflictDate,
            applicant: active.counterpart,
            marker: active.kind === 'short_term_pending' ? '!' : '◆',
            remaining_count: queue.length,
            conflict_key: active.conflictKey,
            booking_id: active.bookingId > 0 ? active.bookingId : undefined,
        };
    });

    return occupied;
});

function weekdayText(day: number): string {
    return `週${WEEKDAY_NAME_MAP[day] ?? day}`;
}

function resetManualConflictResult() {
    manualConflictError.value = '';
    manualConflicts.value = [];
    manualConflictSummary.value = null;
    slotResolutionMap.value = {};
    closeConflictActionModal();
}

// periods_by_day 直接從 slot.date 反推 weekday，不再依賴 day_of_week
function buildManualPeriodsByDay(): Record<string, number[]> {
    const grouped: Record<number, Set<number>> = {};

    manualSelectedSlots.value.forEach((slot) => {
        const weekday = isoWeekdayFromDateString(slot.date);
        const period = Number(slot.period);
        if (!weekday || !Number.isFinite(period) || period <= 0) return;
        if (!grouped[weekday]) grouped[weekday] = new Set<number>();
        grouped[weekday].add(period);
    });

    const result: Record<string, number[]> = {};
    Object.entries(grouped).forEach(([weekday, periodSet]) => {
        result[weekday] = Array.from(periodSet).sort((a, b) => a - b);
    });

    return result;
}

async function previewManualConflicts(): Promise<boolean> {
    if (!canPreviewManualConflicts.value) {
        manualConflictError.value = '請先完成教室、節次與日期範圍設定。';
        return false;
    }

    manualConflictLoading.value = true;
    manualConflictError.value = '';

    try {
        const payload = {
            classroom_id: Number(manualForm.classroom_id),
            teacher_name: manualForm.teacher_name,
            course_name: manualForm.course_name,
            day_of_week: [...manualForm.day_of_week],
            start_date: manualForm.start_date,
            end_date: manualForm.end_date,
            periods: [...manualForm.periods],
            periods_by_day: buildManualPeriodsByDay(),
        };

        const response = await window.axios.post('/admin/long-term-borrowing/manual/conflicts', payload);
        manualConflicts.value = (response?.data?.conflicts ?? []) as ManualConflictItem[];
        manualConflictSummary.value = (response?.data?.summary ?? null) as ManualConflictSummary | null;
        return true;
    } catch (error: any) {
        const backendMessage =
            error?.response?.data?.message
            || error?.response?.data?.errors?.semester?.[0]
            || error?.response?.data?.errors?.periods?.[0]
            || error?.response?.data?.errors?.classroom_id?.[0];
        manualConflictError.value = backendMessage || '衝突檢查失敗，請稍後再試。';
        manualConflicts.value = [];
        manualConflictSummary.value = null;
        return false;
    } finally {
        manualConflictLoading.value = false;
    }
}

function handleManualScheduleChange(slots: SelectedSlot[]) {
    manualSelectedSlots.value = slots;
}

onMounted(() => {
    restoreManualDraft();
});

watch(manualSelectedSlots, (slots) => {
    const periods = Array.from(
        new Set(slots.map((slot) => Number(slot.period)).filter((value) => Number.isFinite(value))),
    ).sort((a, b) => a - b);
    manualForm.periods = periods;
});

watch(
    () => [
        manualForm.classroom_id,
        manualForm.start_date,
        manualForm.end_date,
    ],
    () => {
        if (isRestoringDraft.value) return;
        resetManualConflictResult();
    },
);

watch(conflictKindBySlot, (kindMap) => {
    const nextMap: Record<string, SlotResolutionAction> = {};
    Object.entries(slotResolutionMap.value).forEach(([resolutionKey, action]) => {
        const baseSlotKey = resolutionKey.split('|')[0] || resolutionKey;
        const kind = kindMap[baseSlotKey];
        if (kind && validActionsByKind[kind].includes(action)) {
            nextMap[resolutionKey] = action;
        }
    });
    slotResolutionMap.value = nextMap;
});

function submitManual() {
    if (manualConflictLoading.value) {
        manualConflictError.value = '衝突自動檢查進行中，請稍候再送出。';
        return;
    }

    manualForm.transform((data) => ({
        ...data,
        periods_by_day: buildManualPeriodsByDay(),
        slot_resolutions: { ...slotResolutionMap.value },
    })).post('/admin/long-term-borrowing/manual', {
        preserveScroll: true,
        onSuccess: () => {
            manualForm.reset();
            manualForm.day_of_week = [...FULL_WEEK_DAYS];
            manualSelectedSlots.value = [];
            resetManualConflictResult();
            clearManualDraft();
        },
    });
}

async function handleManualSubmit() {
    if (!canSubmitManual.value) {
        manualConflictError.value = '請先完成教室、節次與日期設定。';
        return;
    }

    const checked = await previewManualConflicts();
    if (!checked || !manualConflictSummary.value) return;

    if (manualConflictSummary.value.total === 0) {
        submitManual();
        return;
    }

    if (unresolvedConflictSlots.value.length > 0) {
        manualConflictError.value = `仍有 ${unresolvedConflictSlots.value.length} 個衝突格尚未處理，請點擊 ! 選擇操作。`;
        return;
    }

    submitManual();
}

function revokeManualRecord(record: ManualRecord) {
    if (!confirm(`確定要撤回「${record.classroom_code} ${weekdayText(record.day_of_week)} ${record.start_slot}-${record.end_slot}」嗎？`)) {
        return;
    }

    router.delete(`/admin/long-term-borrowing/manual/${record.id}`, {
        preserveScroll: true,
    });
}

// ── 匯入功能 ──────────────────────────────────────────
const importForm = useForm<{ classroom_ids: number[] }>({
    classroom_ids: [],
});

const selectedClassroomSet = computed(() => new Set(selectedClassroomIds.value.map((id) => Number(id))));

const importActionLabel = computed(() => {
    if (previewLoading.value) return '預覽中...';
    if (importForm.processing) return '匯入中...';
    if (isAwaitingImportConfirmation.value) return '確認匯入';
    return `匯入 ${selectedClassroomIds.value.length} 間教室`;
});

const classroomsByBuilding = computed<Record<BuildingCode, ClassroomOption[]>>(() => ({
    CB: props.classrooms.filter((room) => getRoomBuildingCode(room) === 'CB'),
    GC: props.classrooms.filter((room) => getRoomBuildingCode(room) === 'GC'),
    RA: props.classrooms.filter((room) => getRoomBuildingCode(room) === 'RA'),
}));

const importServerError = computed(() => {
    const errors = importForm.errors as Record<string, string | undefined>;
    return errors.import ?? '';
});

watch(selectedClassroomIds, () => {
    previewSchedules.value = [];
    previewError.value = '';
    isAwaitingImportConfirmation.value = false;
});

function toggleClassroomSelection(room: ClassroomOption) {
    importErrorMessage.value = '';
    const selected = new Set(selectedClassroomIds.value);
    if (selected.has(room.id)) {
        selected.delete(room.id);
        selectedClassroomIds.value = Array.from(selected);
        return;
    }
    selected.add(room.id);
    selectedClassroomIds.value = Array.from(selected);
}

function selectAllInBuilding(buildingCode: BuildingCode) {
    importErrorMessage.value = '';
    const ids = classroomsByBuilding.value[buildingCode].map((room) => room.id);
    selectedClassroomIds.value = Array.from(new Set([...selectedClassroomIds.value, ...ids]));
}

function clearSelectedClassrooms() {
    selectedClassroomIds.value = [];
    importErrorMessage.value = '';
    previewSchedules.value = [];
    previewError.value = '';
    isAwaitingImportConfirmation.value = false;
}

async function previewImport() {
    importErrorMessage.value = '';
    previewError.value = '';
    if (selectedClassroomIds.value.length === 0) {
        previewError.value = '請至少選擇一間教室。';
        return;
    }
    previewLoading.value = true;
    previewSchedules.value = [];
    isAwaitingImportConfirmation.value = false;
    try {
        const payloadIds = selectedClassroomIds.value.map((id) => Number(id));
        const response = await window.axios.post('/admin/long-term-borrowing/preview', {
            classroom_ids: payloadIds,
        });
        const schedules = (response?.data?.schedules ?? []) as PreviewSchedule[];
        previewSchedules.value = schedules;
        if (schedules.length === 0) {
            previewError.value = '預覽成功，但未取得可匯入課表。';
            return;
        }
        isAwaitingImportConfirmation.value = true;
    } catch (error: any) {
        const backendMessage =
            error?.response?.data?.errors?.import?.[0]
            || error?.response?.data?.errors?.classroom_ids?.[0]
            || error?.response?.data?.message;
        previewError.value = backendMessage || '預覽失敗，請確認匯入服務與參數設定。';
    } finally {
        previewLoading.value = false;
    }
}

async function handleImportAction() {
    importErrorMessage.value = '';

    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }

    if (isAwaitingImportConfirmation.value && previewSchedules.value.length > 0) {
        submitImport();
        return;
    }

    await previewImport();
}

function submitImport() {
    importErrorMessage.value = '';
    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }
    if (previewSchedules.value.length === 0) {
        importErrorMessage.value = '請先完成課表預覽，再進行匯入。';
        return;
    }
    importForm.classroom_ids = selectedClassroomIds.value.map((id) => Number(id));
    importForm.post('/admin/long-term-borrowing/import', {
        preserveScroll: true,
        onSuccess: () => {
            selectedClassroomIds.value = [];
            previewSchedules.value = [];
            previewError.value = '';
            isAwaitingImportConfirmation.value = false;
        },
        onError: () => {
            importErrorMessage.value = '匯入失敗，請確認匯入服務與參數設定。';
            isAwaitingImportConfirmation.value = false;
        },
    });
}

function revokeImport(room: ClassroomOption) {
    if (!confirm(`確定要撤回「${room.code}」的課表匯入嗎？此操作將刪除該教室本學期所有匯入的課表記錄。`)) {
        return;
    }
    router.delete(`/admin/long-term-borrowing/import/${room.id}`, {
        preserveScroll: true,
    });
}
</script>