<template>
    <Head title="長期借用管理 | Admin" />
    <AdminLayout title="長期借用管理">
        <div class="admin-page-container">
            <p
                v-if="$page.props.flash?.success"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-sm text-emerald-400"
            >
                {{ $page.props.flash.success }}
            </p>

            <div class="flex gap-1 border-b border-a-border-2">
                <button
                    type="button"
                    class="-mb-px border-b-2 px-5 py-2.5 text-sm font-medium transition-colors"
                    :class="activeMode === 'manual' ? 'border-primary text-primary' : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'manual'"
                >
                    手動新增
                </button>
                <button
                    type="button"
                    class="-mb-px border-b-2 px-5 py-2.5 text-sm font-medium transition-colors"
                    :class="activeMode === 'import' ? 'border-primary text-primary' : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'import'"
                >
                    教室課表匯入
                </button>
                <button
                    type="button"
                    class="-mb-px border-b-2 px-5 py-2.5 text-sm font-medium transition-colors"
                    :class="activeMode === 'records' ? 'border-primary text-primary' : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'records'"
                >
                    長期借用紀錄
                </button>
            </div>

            <section v-if="activeMode === 'manual'" class="flex flex-col gap-8">
                <div class="rounded-2xl border border-a-border-card bg-a-surface p-6 sm:p-8 shadow-sm">
                    <form class="space-y-8" @submit.prevent="handleManualSubmit">
                        <p v-if="manualConflictError" role="alert" class="text-red-500">{{ manualConflictError }}</p>
                        <div
                            v-if="manualForm.errors.semester || manualForm.errors.periods"
                            class="rounded-lg border border-red-500/20 bg-red-500/5 px-4 py-3 text-sm text-red-500"
                        >
                            {{ manualForm.errors.semester || manualForm.errors.periods }}
                        </div>

                        <fieldset class="space-y-5">
                            <legend class="w-full border-b border-a-divider pb-2 text-xs font-medium uppercase tracking-widest text-a-text-muted">
                                基本資訊
                            </legend>
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">指導老師</label>
                                    <input
                                        v-model.trim="manualForm.teacher_name"
                                        type="text"
                                        class="w-full rounded-xl border border-a-border-2 bg-transparent px-4 py-2.5 text-sm text-a-text-body transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    />
                                    <p v-if="manualForm.errors.teacher_name" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.teacher_name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">課程名稱</label>
                                    <input
                                        v-model.trim="manualForm.course_name"
                                        type="text"
                                        class="w-full rounded-xl border border-a-border-2 bg-transparent px-4 py-2.5 text-sm text-a-text-body transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    />
                                    <p v-if="manualForm.errors.course_name" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.course_name }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-5">
                            <legend class="w-full border-b border-a-divider pb-2 text-xs font-medium uppercase tracking-widest text-a-text-muted">
                                借用時段
                            </legend>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-[minmax(160px,220px)_1fr_1fr]">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-a-text-body">教室</label>
                                    <select
                                        v-model="manualForm.classroom_id"
                                        class="w-full rounded-xl border border-a-border-2 bg-a-surface px-4 py-2.5 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                                    >
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
                                    <input
                                        v-model="manualForm.start_date"
                                        type="date"
                                        class="w-full rounded-xl border border-a-border-2 bg-a-surface px-3 py-2.5 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                                    />
                                    <p v-if="manualForm.errors.start_date" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.start_date }}
                                    </p>
                                </div>
                                <div>
                                    <div class="mb-2 flex items-center justify-between">
                                        <label class="text-sm font-medium text-a-text-body">結束日期</label>
                                        <button
                                            type="button"
                                            :disabled="!props.semesterEndDate"
                                            class="text-xs font-medium text-primary hover:underline disabled:text-a-text-muted disabled:no-underline"
                                            @click="applyQuickDateRange"
                                        >
                                            帶入至學期末
                                        </button>
                                    </div>
                                    <input
                                        v-model="manualForm.end_date"
                                        type="date"
                                        class="w-full rounded-xl border border-a-border-2 bg-a-surface px-3 py-2.5 text-sm text-a-text-body outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                                    />
                                    <p v-if="manualForm.errors.end_date" class="mt-1 text-xs text-red-400">
                                        {{ manualForm.errors.end_date }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <h4 class="mb-3 font-semibold">每週固定星期與節次</h4>
                                <p class="mb-3 text-sm text-a-text-muted">套用於 {{ manualForm.start_date || '開始日期' }}～{{ manualForm.end_date || '結束日期' }} 期間內的每個指定星期。點選或拖曳選擇節次；切換星期會重新選取。</p>
                                <p v-if="!manualAvailabilityReady" class="mb-2 text-sm text-a-text-muted">請先選擇教室及有效日期範圍。</p>
                                <p v-if="manualAvailabilityLoading" role="status" class="mb-2 text-sm text-a-text-muted">正在彙整整段期間的佔用資訊…</p>
                                <p v-if="manualAvailabilityError" role="alert" class="mb-2 text-sm text-red-500">{{ manualAvailabilityError }} <button type="button" class="underline" @click="reloadManualAvailability">重試</button></p>
                                <div class="overflow-x-auto" :class="{ 'pointer-events-none opacity-50': !manualAvailabilityReady || manualAvailabilityLoading || manualAvailabilityError }" :aria-busy="manualAvailabilityLoading">
                                    <ScheduleGrid
                                        class="min-w-[660px]"
                                        :week-dates="manualWeekDates"
                                        :periods="manualGridPeriods"
                                        :occupied-data="manualAvailability"
                                        :model-value="manualSelectedSlots"
                                        :show-header-date="false"
                                        :show-occupied-labels="true"
                                        :non-selectable-dates="manualDisabledWeekdays"
                                        :theme="adminScheduleGridTheme"
                                        @update:model-value="handleManualScheduleChange"
                                        @occupied-click="handleManualOccupiedClick"
                                    />
                                </div>
                                <p class="mt-2 text-xs text-a-text-muted">色塊代表期間內至少一次佔用，不代表每週皆被佔用。點擊色塊僅可查看明細，已占用節次不可選取。若需修改長期借用，請至「長期借用紀錄」。</p>
                                <div v-if="manualOccupancySelection" class="mt-3 rounded border border-a-border-2 p-3 text-sm">
                                    <div class="flex justify-between"><h5 class="font-semibold">週{{ WEEKDAY_NAME_MAP[Number(manualOccupancySelection.date)] }} · {{ periodLabelText(Number(manualOccupancySelection.period)) }} 節 · 期間內佔用明細</h5><button type="button" @click="manualOccupancySelection = null">關閉</button></div>
                                    <ul class="max-h-48 space-y-3 overflow-y-auto py-2">
                                        <li v-for="(detail, index) in manualOccupancySelection.item.details" :key="index">
                                            <p>{{ STATUS_LABELS[detail.status] }} · {{ detail.title }}</p>
                                            <p v-if="detail.instructor || detail.applicant" class="text-a-text-muted">教師：{{ detail.instructor || '—' }}　借用人：{{ detail.applicant || '—' }}</p>
                                            <p class="text-xs break-words text-a-text-muted">{{ detail.dates.length }} 次：{{ detail.dates.join('、') }}</p>
                                        </li>
                                    </ul>
                                </div>
                                <button type="button" class="mt-2 text-sm text-primary" @click="manualSelectedSlots = []; resetManualConflictResult()">清除節次選取</button>
                                <p class="mt-3 text-sm">
                                    目前選擇：
                                    <span v-for="(periods, weekday) in buildManualPeriodsByDay()" :key="weekday">每週{{ WEEKDAY_NAME_MAP[Number(weekday)] }}，{{ periods.map(periodLabelText).join('、') }}</span>
                                    <span v-if="!manualSelectedSlots.length">尚未選擇節次</span>
                                </p>
                                <p v-if="manualForm.errors.periods" class="mt-2 text-xs text-red-400">
                                    {{ manualForm.errors.periods }}
                                </p>
                            </div>
                        </fieldset>

                        <div class="flex items-center justify-between border-t border-a-divider pt-6">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="h-2 w-2 rounded-full transition-colors" :class="manualConflictLoading ? 'animate-pulse bg-primary' : 'bg-a-text-muted/40'" />
                                <span class="text-a-text-muted">{{ manualConflictLoading ? '衝突檢查中…' : '尚未檢查' }}</span>
                            </div>
                            <button
                                type="submit"
                                :disabled="!canSubmitManual || manualForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-primary/90 hover:shadow-lg disabled:cursor-not-allowed disabled:bg-a-divider disabled:text-a-text-muted"
                            >
                                {{ manualForm.processing ? '處理中…' : '確認新增記錄' }}
                            </button>
                        </div>
                    </form>
                </div>

            </section>

            <LongTermRecordsSection v-else-if="activeMode === 'records'"
                :semesters="semesters" :default-semester-id="defaultSemesterId" :classrooms="recordClassrooms"
                :building-options="buildingOptions" :time-slots="timeSlots" @changed="refreshImportStatus" />

            <LongTermImportSection
                v-else-if="activeMode === 'import'"
                :classrooms="classrooms"
                :building-options="buildingOptions"
                :semesters="semesters" :default-semester-id="defaultSemesterId"
            />
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { AdminLayout } from '@/layouts';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ScheduleGrid } from '@/components';
import { useManualScheduleAvailability } from '@/composables/useManualScheduleAvailability';
import { useAdminTheme } from '@/composables';
import { STATUS_LABELS } from '@/constants';
import { LongTermImportSection, LongTermRecordsSection } from '@/components/admin';
import type {
    BuildingOption,
    ClassroomOption,
    ImportConfig,
    ManualConflictSummary,
    ManualFormData,
    SemesterOption,
    OccupiedItem,
    Period,
    SelectedSlot,
    TimeSlotOption,
    WeekDate,
} from '@/types';
import { formatDateToYYYYMMDD, withBase } from '@/utils';

const props = defineProps<{
    classrooms: ClassroomOption[];
    buildingOptions: BuildingOption[];
    timeSlots: TimeSlotOption[];
    semesters: SemesterOption[];
    defaultSemesterId: number | null;
    recordClassrooms: ClassroomOption[];
    semesterEndDate: string | null;
    importConfig: ImportConfig;
}>();

function refreshImportStatus() {
    router.reload({ only: ['classrooms'], data: Object.fromEntries(new URL(window.location.href).searchParams) });
}

const { isDark } = useAdminTheme();
const adminScheduleGridTheme = computed<'light' | 'dark'>(() => (isDark.value ? 'dark' : 'light'));

// ── 常數 ──────────────────────────────────────────────
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

const initialMode = new URL(window.location.href).searchParams.get('mode');
const activeMode = ref<'manual' | 'import' | 'records'>(initialMode === 'records' || initialMode === 'import' ? initialMode : 'manual');
watch(activeMode, (mode) => {
    const url = new URL(window.location.href); url.searchParams.set('mode', mode);
    window.history.replaceState(window.history.state, '', url);
});

// ── 手動表單 ──────────────────────────────────────────
const manualForm = useForm<ManualFormData>({
    classroom_id: '',
    teacher_name: '',
    course_name: '',
    day_of_week: [...FULL_WEEK_DAYS],
    start_date: '',
    end_date: '',
    periods: [],
});

const manualSelectedSlots = ref<SelectedSlot[]>([]);
const { occupied: manualAvailability, loading: manualAvailabilityLoading, error: manualAvailabilityError, ready: manualAvailabilityReady, reload: reloadManualAvailability } = useManualScheduleAvailability(
    () => ({ classroom_id: manualForm.classroom_id, start_date: manualForm.start_date, end_date: manualForm.end_date }),
    () => props.timeSlots,
);
const manualOccupancySelection = ref<{ date: string; period: string; item: OccupiedItem } | null>(null);
const manualDisabledWeekdays = computed(() => {
    const start = new Date(`${manualForm.start_date}T00:00:00`);
    const end = new Date(`${manualForm.end_date}T00:00:00`);
    return FULL_WEEK_DAYS.filter(weekday => {
        const first = new Date(start); first.setDate(first.getDate() + (weekday - (start.getDay() || 7) + 7) % 7);
        return first > end;
    }).map(String);
});

const manualGridPeriods = computed<Period[]>(() => props.timeSlots.map((slot, index) => ({
    id: slot.id,
    // Keep the chronological codes required by the manual borrowing API.
    code: String(index + 1),
    label: slot.name,
    start_time: slot.start_time,
    end_time: slot.end_time,
})));

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
    return FULL_WEEK_DAYS.map(weekday => ({ date: '', dayName: WEEKDAY_NAME_MAP[weekday], fullDate: String(weekday) }));
});

function applyQuickDateRange() {
    if (!props.semesterEndDate) return;
    const today = formatDateToYYYYMMDD(new Date());
    const end = props.semesterEndDate >= today ? props.semesterEndDate : today;
    manualForm.start_date = today;
    manualForm.end_date = end;
}

function isoWeekdayFromDateString(dateString: string): number | null {
    if (/^[1-7]$/.test(dateString)) return Number(dateString);
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

const manualConflictLoading = ref(false);
const manualConflictError = ref('');
const canSubmitManual = computed(() => manualAvailabilityReady.value
    && !manualAvailabilityLoading.value && !manualAvailabilityError.value
    && manualSelectedSlots.value.length > 0 && !manualConflictLoading.value);

function periodLabelText(period: number): string {
    return manualPeriodDisplayLabelByCode.value[period] ?? String(period);
}

function handleManualOccupiedClick(payload: OccupiedClickPayload) {
    const item = payload.item as OccupiedItem | undefined;
    manualOccupancySelection.value = item?.details
        ? { date: payload.date, period: payload.period, item }
        : null;
}

function resetManualConflictResult() {
    manualConflictError.value = '';
}

function handleManualScheduleChange(slots: SelectedSlot[]) {
    if (!manualAvailabilityReady.value || manualAvailabilityLoading.value || manualAvailabilityError.value) return;
    manualSelectedSlots.value = slots;
    resetManualConflictResult();
}

watch(manualSelectedSlots, (slots) => {
    manualForm.periods = [...new Set(slots.map(slot => Number(slot.period)))].sort((a, b) => a - b);
});

watch(() => [manualForm.classroom_id, manualForm.start_date, manualForm.end_date], () => {
    manualOccupancySelection.value = null;
    resetManualConflictResult();
});

// Drop selections that became unavailable after a date range or classroom change.
watch([manualAvailability, manualDisabledWeekdays], () => {
    manualSelectedSlots.value = manualSelectedSlots.value.filter(slot =>
        !manualDisabledWeekdays.value.includes(slot.date)
        && !manualAvailability.value[slot.date]?.[slot.period]);
});

onMounted(() => {
    const raw = window.localStorage.getItem(MANUAL_LONG_TERM_DRAFT_KEY);
    if (!raw) return;
    try {
        const draft = JSON.parse(raw);
        if (!draft?.manualForm) return;
        // Restore only new-booking fields; old conflict actions are never restored.
        for (const key of ['classroom_id', 'teacher_name', 'course_name', 'start_date', 'end_date'] as const) {
            if (draft.manualForm[key] !== undefined) Object.assign(manualForm, { [key]: draft.manualForm[key] });
        }
        const slots: SelectedSlot[] = Array.isArray(draft.manualSelectedSlots) ? draft.manualSelectedSlots : [];
        const normalized = slots.map(slot => ({ ...slot, date: String(isoWeekdayFromDateString(slot.date)) }));
        manualSelectedSlots.value = normalized.filter(slot => slot.date === normalized[0]?.date);
    } catch {
        window.localStorage.removeItem(MANUAL_LONG_TERM_DRAFT_KEY);
    }
});

async function handleManualSubmit() {
    if (!canSubmitManual.value || manualForm.processing) return;
    manualConflictLoading.value = true;
    manualConflictError.value = '';
    const payload = {
        classroom_id: Number(manualForm.classroom_id),
        teacher_name: manualForm.teacher_name,
        course_name: manualForm.course_name,
        start_date: manualForm.start_date,
        end_date: manualForm.end_date,
        day_of_week: [...manualForm.day_of_week],
        periods: [...manualForm.periods],
        periods_by_day: buildManualPeriodsByDay(),
    };
    try {
        const response = await window.axios.post<{ summary: ManualConflictSummary }>(withBase('/admin/long-term-borrowing/manual/conflicts'), payload);
        if (response.data.summary.total > 0) {
            manualConflictError.value = '所選節次已有占用，請調整日期區間或節次。若需修改長期借用，請至「長期借用紀錄」。';
            await reloadManualAvailability();
            return;
        }
        manualForm.transform(() => payload).post(withBase('/admin/long-term-borrowing/manual'), {
            preserveScroll: true,
            onSuccess: () => {
                manualForm.reset();
                manualSelectedSlots.value = [];
                resetManualConflictResult();
                window.localStorage.removeItem(MANUAL_LONG_TERM_DRAFT_KEY);
                void reloadManualAvailability();
            },
        });
    } catch (error: any) {
        manualConflictError.value = error?.response?.data?.message || '衝突檢查失敗，請稍後再試。';
    } finally {
        manualConflictLoading.value = false;
    }
}
</script>
