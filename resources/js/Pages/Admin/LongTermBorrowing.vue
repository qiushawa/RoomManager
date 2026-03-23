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
                        : 'border-transparent text-a-text-muted hover:text-a-text'" @click="activeMode = 'manual'">
                    手動新增
                </button>
                <button type="button" class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'import'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'" @click="activeMode = 'import'">
                    教室課表匯入
                </button>
                <button type="button" class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'records'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'" @click="activeMode = 'records'">
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

                    <form class="space-y-10" @submit.prevent="submitManual">

                        <section>
                            <h4 class="mb-5 flex items-center gap-2 text-sm font-bold text-a-text">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs text-primary">1</span>
                                基本資訊
                            </h4>

                            <div class="space-y-6 pl-8">
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-a-text-body">指導老師</label>
                                        <input v-model.trim="manualForm.teacher_name" type="text" placeholder="如：王大明"
                                            class="w-full rounded-xl border border-a-border-2 bg-transparent px-4 py-2.5 text-sm text-a-text-body transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none" />
                                        <p v-if="manualForm.errors.teacher_name" class="mt-1 text-xs text-red-400">{{
                                            manualForm.errors.teacher_name }}</p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-a-text-body">課程名稱</label>
                                        <input v-model.trim="manualForm.course_name" type="text" placeholder="如：資料結構"
                                            class="w-full rounded-xl border border-a-border-2 bg-transparent px-4 py-2.5 text-sm text-a-text-body transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none" />
                                        <p v-if="manualForm.errors.course_name" class="mt-1 text-xs text-red-400">{{
                                            manualForm.errors.course_name }}</p>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <label class="mb-3 block text-sm font-medium text-a-text-body">選擇教室</label>
                                    <div
                                        class="grid max-h-[220px] grid-cols-2 gap-3 overflow-y-auto pr-2 sm:grid-cols-3 xl:grid-cols-4">
                                        <button v-for="room in classrooms" :key="room.id" type="button"
                                            class="group flex flex-col items-start rounded-xl border p-3 text-left transition-all duration-200"
                                            :class="manualForm.classroom_id === room.id
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                                                : 'border-a-border-2 hover:border-a-text-muted/30 hover:bg-a-surface-hover'"
                                            @click="manualForm.classroom_id = room.id">
                                            <span class="text-sm font-bold transition-colors"
                                                :class="manualForm.classroom_id === room.id ? 'text-primary' : 'text-a-text'">
                                                {{ room.code }}
                                            </span>
                                            <span class="mt-0.5 text-xs text-a-text-muted">{{ room.name }}</span>
                                        </button>
                                    </div>
                                    <p v-if="manualForm.errors.classroom_id" class="mt-2 text-xs text-red-400">{{
                                        manualForm.errors.classroom_id }}</p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h4 class="mb-5 flex items-center gap-2 text-sm font-bold text-a-text">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs text-primary">2</span>
                                時段與排程
                            </h4>

                            <div class="grid grid-cols-1 gap-8 pl-8 xl:grid-cols-2">
                                <div class="space-y-6">
                                    <div>
                                        <label class="mb-3 block text-sm font-medium text-a-text-body">借用星期 <span
                                                class="text-xs text-a-text-muted font-normal">(可多選)</span></label>
                                        <div class="flex flex-wrap gap-2">
                                            <label v-for="day in weekdayOptions" :key="day.value"
                                                class="cursor-pointer rounded-lg border px-3.5 py-1.5 text-sm font-medium transition-all"
                                                :class="manualForm.day_of_week.includes(day.value)
                                                    ? 'border-primary bg-primary text-white shadow-sm'
                                                    : 'border-a-border-2 text-a-text-muted hover:border-a-text-muted/40'">
                                                <input type="checkbox" :value="day.value"
                                                    v-model="manualForm.day_of_week" class="sr-only" />
                                                {{ day.label }}
                                            </label>
                                        </div>
                                        <p v-if="manualForm.errors.day_of_week" class="mt-2 text-xs text-red-400">{{
                                            manualForm.errors.day_of_week }}</p>
                                    </div>

                                    <div class="space-y-3 rounded-xl border border-a-divider bg-a-surface-hover/30 p-4">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-medium text-a-text-body">生效日期區間</label>
                                            <button type="button" :disabled="!props.semesterEndDate"
                                                class="text-xs font-medium text-primary hover:underline disabled:text-a-text-muted disabled:no-underline"
                                                @click="applyQuickDateRange">
                                                自動帶入至學期末
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <input v-model="manualForm.start_date" type="date"
                                                    class="w-full rounded-lg border border-a-border-2 bg-a-surface px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary" />
                                            </div>
                                            <div>
                                                <input v-model="manualForm.end_date" type="date"
                                                    class="w-full rounded-lg border border-a-border-2 bg-a-surface px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary" />
                                            </div>
                                        </div>
                                        <p v-if="manualForm.errors.start_date || manualForm.errors.end_date"
                                            class="mt-1 text-xs text-red-400">日期設定有誤，請檢查起訖時間。</p>
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-3 block text-sm font-medium text-a-text-body">節數選取</label>
                                    <div class="flex-1 rounded-xl border border-a-border-2 bg-a-surface p-3 shadow-sm">
                                        <div v-if="manualWeekDates.length === 0"
                                            class="flex h-full items-center justify-center rounded-lg border border-dashed border-a-divider bg-a-surface-hover/50 p-6 text-center text-sm text-a-text-muted">
                                            請先於左側選擇「借用星期」，<br>課表將會自動顯示供您勾選。
                                        </div>
                                        <div v-else class="max-h-[320px] overflow-auto">
                                            <ScheduleGrid :week-dates="manualWeekDates" :periods="manualGridPeriods"
                                                :model-value="manualSelectedSlots" :show-header-date="false"
                                                :allow-cross-date-selection="true" :theme="adminScheduleGridTheme"
                                                @update:model-value="handleManualScheduleChange" />
                                        </div>
                                    </div>
                                    <p v-if="manualForm.errors.periods" class="mt-2 text-xs text-red-400">{{
                                        manualForm.errors.periods }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-a-divider pt-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-a-text">衝突檢查狀態</h4>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-a-surface-hover px-3 py-1 text-xs font-medium text-a-text-muted">
                                    <span v-if="manualConflictLoading"
                                        class="h-2 w-2 animate-pulse rounded-full bg-primary"></span>
                                    <span v-else class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    {{ manualConflictLoading ? '自動檢查中...' : '系統監測中' }}
                                </span>
                            </div>

                            <div v-if="manualConflictError"
                                class="rounded-xl border border-red-500/20 bg-red-500/5 p-4 text-sm text-red-500">
                                {{ manualConflictError }}
                            </div>

                            <div v-else-if="manualConflictSummary && manualConflictSummary.total === 0"
                                class="flex items-center gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                時段暢通無阻，可以放心送出申請。
                            </div>

                            <div v-else-if="manualConflictSummary && manualConflictSummary.total > 0"
                                class="rounded-xl border border-red-500/20 bg-red-500/10 p-5">
                                <div class="mb-4 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-red-400">偵測到 {{
                                            manualConflictSummary.total }} 筆排程衝突</p>
                                        <p class="mt-1 text-xs text-red-300/80">不可覆蓋 {{
                                            manualConflictSummary.protected }} 筆 / 可處理 {{
                                            manualConflictSummary.overridable }} 筆</p>
                                    </div>

                                </div>

                                <div
                                    class="overflow-x-auto rounded-lg border border-a-border-2 bg-a-surface">
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead
                                            class="border-b border-a-border-2 bg-a-surface-hover text-a-text-muted">
                                            <tr>
                                                <th class="px-4 py-2.5 font-medium">來源 / 課程</th>
                                                <th class="px-4 py-2.5 font-medium">星期</th>
                                                <th class="px-4 py-2.5 font-medium">衝突節次</th>
                                                <th class="px-4 py-2.5 font-medium">有效日期</th>
                                                <th class="px-4 py-2.5 font-medium">處理狀態</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-a-divider text-a-text-body">
                                            <tr v-for="item in manualConflicts" :key="item.id">
                                                <td class="px-4 py-3">
                                                    <p class="font-medium text-a-text">{{ item.source_label }}</p>
                                                    <p v-if="item.course_name" class="text-xs text-a-text-muted mt-0.5">
                                                        {{ item.course_name }}</p>
                                                </td>
                                                <td class="px-4 py-3">{{ weekdayText(item.day_of_week) }}</td>
                                                <td class="px-4 py-3">{{ formatPeriodList(item.overlap_periods) }}</td>
                                                <td class="px-4 py-3 text-xs text-a-text-muted">
                                                    {{ item.start_date || '—' }} ~ {{ item.end_date || '—' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span
                                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                                        :class="item.is_protected ? 'bg-red-500/10 text-red-400' : 'bg-primary/10 text-primary'">
                                                        {{ item.is_protected ? '不可覆蓋' : '可處理' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        <div class="mt-8 flex justify-end border-t border-a-divider pt-6">
                            <button type="submit" :disabled="!canSubmitManual || manualForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-primary/90 hover:shadow-lg focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-a-divider disabled:text-a-text-muted disabled:shadow-none">
                                <svg v-if="manualForm.processing" class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                {{ manualForm.processing ? '處理中...' : '確認新增記錄' }}
                            </button>
                        </div>
                    </form>
                </div>

            </section>

            <section v-else-if="activeMode === 'records'" class="rounded-2xl border border-a-border-card bg-a-surface p-6 sm:p-8 shadow-sm">
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

                <!-- 錯誤訊息 -->
                <p v-if="importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError"
                    class="rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs text-red-400">
                    {{ importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError }}
                </p>

                <!-- 大樓教室選擇 -->
                <div class="space-y-4">
                    <ImportBuildingPanel v-for="buildingCode in buildingOrder" :key="buildingCode"
                        :building-code="buildingCode" :building-label="buildingLabels[buildingCode]"
                        :rooms="classroomsByBuilding[buildingCode]" :selected-building-code="selectedBuildingCode"
                        :selected-classroom-set="selectedClassroomSet" @select-all="selectAllInBuilding"
                        @toggle-room="toggleClassroomSelection" @revoke-room="revokeImport" />
                </div>

                <!-- 工具列 + 動作按鈕 -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-a-border-2 pt-4">
                    <button type="button"
                        class="text-xs text-a-text-muted underline-offset-2 hover:text-a-text hover:underline"
                        @click="clearSelectedClassrooms">
                        清空選取
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" :disabled="previewLoading || selectedClassroomIds.length === 0"
                            class="rounded-lg border border-primary/40 bg-primary/10 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary/20 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="previewImport">
                            {{ previewLoading ? '預覽中...' : `預覽 ${selectedClassroomIds.length} 間課表` }}
                        </button>
                        <button type="button" :disabled="importForm.processing || previewSchedules.length === 0"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitImport">
                            {{ importForm.processing ? '匯入中...' : `確認匯入` }}
                        </button>
                    </div>
                </div>

                <ImportPreviewTable :classrooms="classrooms" :preview-schedules="previewSchedules" />
            </section>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ScheduleGrid } from '@/components';
import { useAdminTheme } from '@/composables';
import {
    LONG_TERM_BUILDING_LABELS,
    LONG_TERM_BUILDING_ORDER,
    LONG_TERM_WEEKDAY_OPTIONS,
} from '@/constants';
import { ImportBuildingPanel, ImportPreviewTable, ManualRecordList } from '@/components/admin';
import type {
    BuildingCode,
    ClassroomOption,
    ImportConfig,
    ManualConflictItem,
    ManualConflictSummary,
    ManualFormData,
    ManualRecord,
    Period,
    PreviewSchedule,
    SelectedSlot,
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
const weekdayOptions = LONG_TERM_WEEKDAY_OPTIONS;

// ── 狀態 ──────────────────────────────────────────────
const activeMode = ref<'manual' | 'import' | 'records'>('manual');
const selectedClassroomIds = ref<number[]>([]);
const importErrorMessage = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const previewSchedules = ref<PreviewSchedule[]>([]);

// ── 手動表單 ──────────────────────────────────────────
const manualForm = useForm<ManualFormData>({
    classroom_id: '',
    teacher_name: '',
    course_name: '',
    day_of_week: [],
    start_date: '',
    end_date: '',
    periods: [],
});

const WEEKDAY_NAME_MAP: Record<number, string> = {
    1: '一',
    2: '二',
    3: '三',
    4: '四',
    5: '五',
    6: '六',
    7: '日',
};

const manualSelectedSlots = ref<SelectedSlot[]>([]);
const manualConflictLoading = ref(false);
const manualConflictError = ref('');
const manualConflicts = ref<ManualConflictItem[]>([]);
const manualConflictSummary = ref<ManualConflictSummary | null>(null);
let manualConflictTimer: ReturnType<typeof setTimeout> | null = null;

const manualGridPeriods = computed<Period[]>(() =>
    props.timeSlots.map((slot, index) => ({
        id: slot.id,
        code: String(index + 1),
        label: String(index + 1),
    })),
);

const manualWeekDates = computed<WeekDate[]>(() => {
    const selectedDays = [...manualForm.day_of_week].sort((a, b) => a - b);
    if (selectedDays.length === 0) return [];

    const today = new Date();
    const day = today.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    const monday = new Date(today);
    monday.setDate(today.getDate() + mondayOffset);

    return selectedDays.map((weekday) => {
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
    if (!props.semesterEndDate) {
        return;
    }

    const today = formatDateToYYYYMMDD(new Date());
    const end = props.semesterEndDate >= today ? props.semesterEndDate : today;
    manualForm.start_date = today;
    manualForm.end_date = end;
}

const selectedManualWeekDateSet = computed(() => new Set(manualWeekDates.value.map((day) => day.fullDate)));

const manualDateToWeekdayMap = computed<Record<string, number>>(() => {
    const selectedDays = [...manualForm.day_of_week].sort((a, b) => a - b);
    const mapping: Record<string, number> = {};

    manualWeekDates.value.forEach((day, index) => {
        const weekday = selectedDays[index];
        if (weekday) {
            mapping[day.fullDate] = weekday;
        }
    });

    return mapping;
});

const canPreviewManualConflicts = computed(() => (
    !!manualForm.classroom_id
    && manualForm.day_of_week.length > 0
    && manualForm.periods.length > 0
    && !!manualForm.start_date
    && !!manualForm.end_date
));

const canSubmitManual = computed(() => (
    canPreviewManualConflicts.value
    && !manualConflictLoading.value
    && !!manualConflictSummary.value
    && manualConflictSummary.value.total === 0
    && !manualConflictError.value
    && !manualForm.processing
));

function weekdayText(day: number): string {
    return `週${WEEKDAY_NAME_MAP[day] ?? day}`;
}

function formatPeriodList(periods: number[]): string {
    if (!periods.length) return '—';
    return periods.map((p) => `第${p}節`).join('、');
}

function resetManualConflictResult() {
    manualConflictError.value = '';
    manualConflicts.value = [];
    manualConflictSummary.value = null;
}

function buildManualPeriodsByDay(): Record<string, number[]> {
    const grouped: Record<number, Set<number>> = {};
    const dateToWeekday = manualDateToWeekdayMap.value;

    manualSelectedSlots.value.forEach((slot) => {
        const weekday = dateToWeekday[slot.date];
        const period = Number(slot.period);
        if (!weekday || !Number.isFinite(period) || period <= 0) {
            return;
        }

        if (!grouped[weekday]) {
            grouped[weekday] = new Set<number>();
        }

        grouped[weekday].add(period);
    });

    const result: Record<string, number[]> = {};
    Object.entries(grouped).forEach(([weekday, periodSet]) => {
        result[weekday] = Array.from(periodSet).sort((a, b) => a - b);
    });

    return result;
}

async function previewManualConflicts() {
    if (!canPreviewManualConflicts.value) {
        manualConflictError.value = '請先完成教室、星期、節次與日期範圍設定。';
        return;
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
    } catch (error: any) {
        const backendMessage =
            error?.response?.data?.message
            || error?.response?.data?.errors?.semester?.[0]
            || error?.response?.data?.errors?.periods?.[0]
            || error?.response?.data?.errors?.classroom_id?.[0];
        manualConflictError.value = backendMessage || '衝突檢查失敗，請稍後再試。';
        manualConflicts.value = [];
        manualConflictSummary.value = null;
    } finally {
        manualConflictLoading.value = false;
    }
}

function scheduleAutoManualConflictPreview() {
    if (manualConflictTimer) {
        clearTimeout(manualConflictTimer);
        manualConflictTimer = null;
    }

    if (!canPreviewManualConflicts.value) {
        return;
    }

    manualConflictTimer = setTimeout(() => {
        previewManualConflicts();
    }, 400);
}

function handleManualScheduleChange(slots: SelectedSlot[]) {
    manualSelectedSlots.value = slots;
}

watch(manualSelectedSlots, (slots) => {
    const periods = Array.from(new Set(slots.map((slot) => Number(slot.period)).filter((value) => Number.isFinite(value))))
        .sort((a, b) => a - b);
    manualForm.periods = periods;
});

watch(
    () => manualForm.day_of_week,
    () => {
        const allowedDates = selectedManualWeekDateSet.value;
        manualSelectedSlots.value = manualSelectedSlots.value.filter((slot) => allowedDates.has(slot.date));
    },
    { deep: true },
);

watch(
    () => [
        manualForm.classroom_id,
        manualForm.start_date,
        manualForm.end_date,
        manualForm.teacher_name,
        manualForm.course_name,
        manualForm.day_of_week.join(','),
        manualForm.periods.join(','),
    ],
    () => {
        resetManualConflictResult();
        scheduleAutoManualConflictPreview();
    },
);

onBeforeUnmount(() => {
    if (manualConflictTimer) {
        clearTimeout(manualConflictTimer);
        manualConflictTimer = null;
    }
});

function submitManual() {
    if (!canSubmitManual.value) {
        manualConflictError.value = '僅可在衝突檢查完成且無衝突時送出新增。';
        if (canPreviewManualConflicts.value) {
            scheduleAutoManualConflictPreview();
        }
        return;
    }

    if (manualConflictLoading.value) {
        manualConflictError.value = '衝突自動檢查進行中，請稍候再送出。';
        return;
    }

    if (canPreviewManualConflicts.value && !manualConflictSummary.value) {
        manualConflictError.value = '尚未完成衝突檢查，系統將自動重試後再送出。';
        scheduleAutoManualConflictPreview();
        return;
    }

    manualForm.transform((data) => ({
        ...data,
        periods_by_day: buildManualPeriodsByDay(),
    })).post('/admin/long-term-borrowing/manual', {
        preserveScroll: true,
        onSuccess: () => {
            manualForm.reset();
            manualSelectedSlots.value = [];
            resetManualConflictResult();
        },
    });
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

const selectedClassrooms = computed(() => {
    const selected = selectedClassroomSet.value;
    return props.classrooms.filter((room) => selected.has(room.id));
});

const selectedBuildingCode = computed<BuildingCode | null>(() => {
    const selected = selectedClassrooms.value;
    if (selected.length === 0) return null;
    const codes = new Set<BuildingCode>();
    for (const room of selected) {
        const code = getRoomBuildingCode(room);
        if (!code) return null;
        codes.add(code);
    }
    if (codes.size !== 1) return null;
    return Array.from(codes)[0];
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
});

function toggleClassroomSelection(room: ClassroomOption) {
    importErrorMessage.value = '';
    const targetBuilding = getRoomBuildingCode(room);
    if (!targetBuilding) {
        importErrorMessage.value = '教室代碼無法判斷大樓，僅支援 CB、GC、RA。';
        return;
    }
    const selected = new Set(selectedClassroomIds.value);
    if (selected.has(room.id)) {
        selected.delete(room.id);
        selectedClassroomIds.value = Array.from(selected);
        return;
    }
    const currentBuilding = selectedBuildingCode.value;
    if (currentBuilding && currentBuilding !== targetBuilding) {
        importErrorMessage.value = '只能批量匯入同一大樓教室，請先清空目前選取。';
        return;
    }
    selected.add(room.id);
    selectedClassroomIds.value = Array.from(selected);
}

function selectAllInBuilding(buildingCode: BuildingCode) {
    importErrorMessage.value = '';
    const currentBuilding = selectedBuildingCode.value;
    if (currentBuilding && currentBuilding !== buildingCode) {
        importErrorMessage.value = '只能批量匯入同一大樓教室，請先清空目前選取。';
        return;
    }
    const ids = classroomsByBuilding.value[buildingCode].map((room) => room.id);
    selectedClassroomIds.value = Array.from(new Set([...selectedClassroomIds.value, ...ids]));
}

function clearSelectedClassrooms() {
    selectedClassroomIds.value = [];
    importErrorMessage.value = '';
}

async function previewImport() {
    importErrorMessage.value = '';
    previewError.value = '';
    if (selectedClassroomIds.value.length === 0) {
        previewError.value = '請至少選擇一間教室。';
        return;
    }
    if (!selectedBuildingCode.value) {
        previewError.value = '請選擇同一大樓教室（CB、GC、RA）後再預覽。';
        return;
    }
    previewLoading.value = true;
    previewSchedules.value = [];
    try {
        const payloadIds = selectedClassroomIds.value.map((id) => Number(id));
        const response = await window.axios.post('/admin/long-term-borrowing/preview', {
            classroom_ids: payloadIds,
        });
        const schedules = (response?.data?.schedules ?? []) as PreviewSchedule[];
        previewSchedules.value = schedules;
        if (schedules.length === 0) {
            previewError.value = '預覽成功，但未取得可匯入課表。';
        }
    } catch (error: any) {
        const backendMessage =
            error?.response?.data?.errors?.import?.[0] ||
            error?.response?.data?.errors?.classroom_ids?.[0] ||
            error?.response?.data?.message;
        previewError.value = backendMessage || '預覽失敗，請確認匯入服務與參數設定。';
    } finally {
        previewLoading.value = false;
    }
}

function submitImport() {
    importErrorMessage.value = '';
    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }
    if (!selectedBuildingCode.value) {
        importErrorMessage.value = '請僅保留同一大樓教室再匯入。';
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
            previewSchedules.value = [];
        },
        onError: () => {
            importErrorMessage.value = '匯入失敗，請確認匯入服務與參數設定。';
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
