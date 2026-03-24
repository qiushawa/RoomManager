<template>
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                :data-admin-theme="theme"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                :class="isDark ? 'bg-black/60 backdrop-blur-sm' : 'bg-slate-900/35 backdrop-blur-[1px]'"
                @click="handleClose"
            >
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-2 scale-[0.98]"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-1 scale-[0.98]"
                >
                    <div
                        v-if="request"
                        class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border bg-a-bg"
                        :class="isDark ? 'border-a-border-card shadow-2xl' : 'border-slate-200 shadow-xl shadow-slate-900/10'"
                        @click.stop
                    >
                        <!-- ── Header ── -->
                        <div class="flex items-start justify-between px-6 py-4 border-b border-a-border">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                                    <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-a-text leading-snug">預約申請審核</h3>
                                    <p class="mt-0.5 text-[13px] text-a-text-muted">申請編號 #{{ request.id }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-0.5">
                                
                                <button
                                    class="ml-1 rounded-lg p-1.5 text-a-text-muted hover:bg-a-surface-2 hover:text-a-text transition-colors"
                                    @click="handleClose"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- ── Body ── -->
                        <div class="flex min-h-0 flex-1">

                            <!-- 左側：排程預覽 -->
                            <div class="w-[36%] shrink-0 overflow-y-auto border-r border-a-border p-3"
                                :class="isDark ? 'bg-a-header' : 'bg-slate-50'">
                                <!-- 跨日提示 -->
                                <div
                                    v-if="request.is_multi_day"
                                    class="mb-2.5 flex items-center gap-1.5 rounded-lg bg-primary/8 px-3 py-2"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-[11px] font-medium text-primary leading-snug">點選右側日期可切換預覽</p>
                                </div>

                                <PreviewScheduleGrid
                                    v-if="props.periods"
                                    :weekDates="getWeekDatesForRequest(request)"
                                    :periods="props.periods"
                                    :occupiedData="getOccupiedDataForRequest(request)"
                                    :selectedSlots="getSelectedSlotsForRequest(request)"
                                    :highlightInfo="getHighlightInfoForRequest(request)"
                                />
                            </div>

                            <!-- 右側：資訊 -->
                            <div class="flex w-[64%] flex-col overflow-y-auto">

                                <!-- 借用人 -->
                                <div class="px-5 py-4 border-b border-a-divider">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-a-surface-2 text-sm font-semibold text-a-text-2">
                                            {{ request.borrower?.name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-base font-semibold text-a-text">{{ request.borrower?.name }}</span>
                                                <span class="text-[13px] text-a-text-muted">{{ request.borrower?.identity_code }}</span>
                                            </div>
                                            <div class="mt-0.5 flex items-center gap-3 text-[13px] text-a-text-muted">
                                                <span v-if="request.borrower?.phone">{{ request.borrower.phone }}</span>
                                                <span v-if="request.borrower?.email" class="truncate max-w-[180px]">{{ request.borrower.email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 基本資訊格 -->
                                <div class="grid grid-cols-3 gap-px bg-a-divider border-b border-a-divider">
                                    <div class="bg-a-bg px-4 py-3">
                                        <p class="mb-1 text-[11px] uppercase tracking-wide text-a-text-dim">預約日期</p>
                                        <p class="text-sm font-medium leading-snug text-a-text-body">
                                            {{ formatDateSummaryMonthDay(request.date_summary || request.date) }}
                                        </p>
                                    </div>
                                    <div class="bg-a-bg px-4 py-3">
                                        <p class="mb-1 text-[11px] uppercase tracking-wide text-a-text-dim">指導老師</p>
                                        <p class="text-sm font-medium text-a-text-body">{{ request.teacher || '—' }}</p>
                                    </div>
                                    <div class="bg-a-bg px-4 py-3">
                                        <p class="mb-1 text-[11px] uppercase tracking-wide text-a-text-dim">教室</p>
                                        <p class="text-sm font-medium text-a-text-body">{{ request.classroom?.code || '—' }}</p>
                                    </div>
                                </div>

                                <!-- 借用時段 -->
                                <div class="px-5 py-4 border-b border-a-divider">
                                    <p class="mb-3 text-[11px] uppercase tracking-wide text-a-text-dim">借用時段</p>

                                    <!-- 跨日明細 -->
                                    <template v-if="request.is_multi_day && request.booking_dates?.length">
                                        <!-- 天數摘要 -->
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="text-xs text-a-text-muted">
                                                共 {{ request.booking_dates.length }} 天，點選切換左側預覽
                                            </span>
                                            <span class="text-xs text-a-text-dim">
                                                {{ request.booking_dates.reduce((acc, d) => acc + d.time_slots.length, 0) }} 節總計
                                            </span>
                                        </div>

                                        <!-- 固定高度可捲動列表；3 筆以下自然撐開，超過則捲動 -->
                                        <div
                                            class="max-h-[168px] overflow-y-auto overscroll-contain rounded-xl border divide-y"
                                            :class="isDark
                                                ? 'border-a-divider divide-a-divider bg-a-bg'
                                                : 'border-slate-200 divide-slate-200 bg-slate-50/70'"
                                        >
                                            <button
                                                v-for="item in request.booking_dates"
                                                :key="item.date"
                                                class="group w-full flex items-center gap-3 px-3 py-2.5 text-left transition-colors"
                                                :class="resolvedPreviewDate === item.date
                                                    ? (isDark ? 'bg-primary/8' : 'bg-blue-50')
                                                    : (isDark ? 'bg-a-badge hover:bg-a-surface' : 'bg-white hover:bg-slate-50')"
                                                @click="selectPreviewDate(item.date)"
                                            >
                                                <!-- 選中指示條 -->
                                                <div class="shrink-0 h-7 w-0.5 rounded-full transition-colors"
                                                    :class="resolvedPreviewDate === item.date ? 'bg-primary' : 'bg-transparent'" />

                                                <!-- 日期 -->
                                                <div class="shrink-0 w-[100px]">
                                                    <p class="text-xs font-semibold leading-snug"
                                                        :class="resolvedPreviewDate === item.date ? 'text-primary' : 'text-a-text-body'">
                                                        {{ formatDateShort(item.date) }}
                                                    </p>
                                                    <p class="text-[11px] text-a-text-dim">{{ item.time_slots.length }} 節</p>
                                                </div>

                                                <!-- 時段 pills（最多顯示 4 個，超出省略） -->
                                                <div class="flex flex-1 flex-wrap gap-1 overflow-hidden" style="max-height: 2.5rem;">
                                                    <span
                                                        v-for="slot in item.time_slots.slice(0, 4)"
                                                        :key="`${item.date}-${slot}`"
                                                        class="rounded px-1.5 py-0.5 text-[11px] font-medium"
                                                        :class="resolvedPreviewDate === item.date
                                                            ? 'bg-primary/12 text-primary'
                                                            : (isDark ? 'bg-a-surface border border-a-divider text-a-text-2' : 'bg-slate-100 text-slate-700')"
                                                    >
                                                        {{ formatPeriodLabel(slot) }}
                                                    </span>
                                                    <span
                                                        v-if="item.time_slots.length > 4"
                                                        class="rounded px-1.5 py-0.5 text-[11px] text-a-text-muted border border-a-divider"
                                                        :class="isDark ? 'bg-a-surface' : 'bg-slate-100 border-slate-200 text-slate-600'"
                                                    >
                                                        +{{ item.time_slots.length - 4 }}
                                                    </span>
                                                </div>

                                                <!-- 箭頭 -->
                                                <svg class="h-3 w-3 shrink-0 transition-opacity"
                                                    :class="resolvedPreviewDate === item.date ? 'text-primary opacity-100' : 'text-a-text-muted opacity-0 group-hover:opacity-60'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- 單日時段 pills -->
                                    <div v-else class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="slot in request.time_slots"
                                            :key="slot"
                                            class="rounded-lg border px-2.5 py-1 text-[13px] font-medium"
                                            :class="isDark
                                                ? 'border-a-divider bg-a-badge text-a-text-2'
                                                : 'border-slate-200 bg-white text-slate-700'"
                                        >
                                            {{ formatPeriodLabel(slot) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- 申請事由 -->
                                <div class="flex-1 px-5 py-4">
                                    <p class="mb-2 text-[11px] uppercase tracking-wide text-a-text-dim">申請事由</p>
                                    <div class="min-h-[72px] whitespace-pre-wrap rounded-xl border border-a-divider bg-a-surface-hover px-4 py-3 text-sm leading-relaxed text-a-text-2">
                                        {{ request.reason || '未填寫具體事由' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Footer ── -->
                        <div
                            v-if="request.status === 0"
                            class="flex items-center justify-between border-t border-a-border bg-a-header px-5 py-3"
                        >
                            <p class="text-sm text-a-text-muted">
                                審核後將自動通知申請人
                            </p>
                            <div class="flex items-center gap-2">
                                <button
                                    class="rounded-md border border-a-divider bg-a-surface px-4 py-2 text-sm font-medium text-a-text-2 transition-colors hover:bg-a-surface-hover"
                                    @click="handleReject"
                                >
                                    ✗ 不通過
                                </button>
                                <button
                                    class="rounded-md border border-a-divider bg-a-surface px-4 py-2 text-sm font-medium text-a-text-2 transition-colors hover:bg-a-surface-hover"
                                    @click="handleApprove"
                                >
                                    ✓ 通過
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

<script setup lang="ts">
import { PreviewScheduleGrid } from '@/components/admin';
import { useAdminTheme } from '@/composables';
import { formatPeriodLabel } from '@/utils';
import { computed, ref, watch } from 'vue';
import type {
    AdminBookingItem,
    HighlightInfo,
    OccupiedData,
    Period,
    SelectedSlot,
    WeekDate,
} from '@/types';

const props = withDefaults(
    defineProps<{
        open: boolean;
        request: AdminBookingItem | null;
        periods?: Period[];
    }>(),
    {
        open: false,
        request: null,
        periods: undefined,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'approve', id: number): void;
    (e: 'reject', id: number): void;
}>();

const { theme, isDark } = useAdminTheme();

const handleClose = () => emit('close');
const handleApprove = () => { if (props.request) emit('approve', props.request.id); };
const handleReject = () => { if (props.request) emit('reject', props.request.id); };

const selectedPreviewDate = ref<string>('');

const resolvedPreviewDate = computed(() =>
    selectedPreviewDate.value ||
    props.request?.booking_dates?.[0]?.date ||
    props.request?.date ||
    ''
);

watch(
    () => [props.open, props.request?.id],
    () => {
        selectedPreviewDate.value =
            props.request?.booking_dates?.[0]?.date ||
            props.request?.date ||
            '';
    },
    { immediate: true },
);

const selectPreviewDate = (date: string) => {
    if (date) selectedPreviewDate.value = date;
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        weekday: 'short',
    });
};

const formatMonthDay = (dateString: string): string => {
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('zh-TW', {
        month: 'numeric',
        day: 'numeric',
    });
};

const formatDateSummaryMonthDay = (summary: string): string => {
    if (!summary) return '';

    // Handles values like "2026-03-15" and "2026-03-15 ~ 2026-03-29".
    return summary.replace(/\d{4}-\d{2}-\d{2}/g, (matched) => formatMonthDay(matched));
};

/** 跨日列表用：顯示「M/D（週X）」 */
const formatDateShort = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-TW', {
        month: 'numeric',
        day: 'numeric',
        weekday: 'short',
    });
};

const getWeekDatesForRequest = (request: AdminBookingItem): WeekDate[] => {
    const previewDate = resolvedPreviewDate.value || request?.booking_dates?.[0]?.date || request?.date;
    if (!previewDate) return [];
    const date = new Date(previewDate);
    const dayNames = ['日', '一', '二', '三', '四', '五', '六'];
    const dateStr = date.toISOString().split('T')[0];
    return [{ date: dateStr, dayName: dayNames[date.getDay()], fullDate: dateStr }];
};

const getOccupiedDataForRequest = (request: AdminBookingItem): OccupiedData => {
    const previewDate = resolvedPreviewDate.value || request?.booking_dates?.[0]?.date || request?.date;
    if (!previewDate) return {};
    return { [new Date(previewDate).toISOString().split('T')[0]]: {} };
};

const getSelectedSlotsForRequest = (request: AdminBookingItem): SelectedSlot[] => {
    if (!request || !props.periods) return [];
    const previewDate = resolvedPreviewDate.value || request?.booking_dates?.[0]?.date || request.date;
    const matched = request?.booking_dates?.find((item) => item.date === previewDate);
    const slots = matched?.time_slots || request?.booking_dates?.[0]?.time_slots || request.time_slots;
    const dateStr = new Date(previewDate).toISOString().split('T')[0];
    return slots
        .map((slotName) => {
            const period = props.periods!.find((p) => p.code === slotName || p.label === slotName);
            if (!period) return null;
            return {
                date: dateStr,
                period: period.code ?? period.label ?? '',
                id: period.id,
                label: `${formatDate(previewDate)} ${formatPeriodLabel(slotName)}`,
            };
        })
        .filter((s): s is SelectedSlot => s !== null);
};

const getHighlightInfoForRequest = (request: AdminBookingItem): HighlightInfo | null => {
    if (!request || !props.periods) return null;
    const previewDate = resolvedPreviewDate.value || request?.booking_dates?.[0]?.date || request.date;
    const matched = request?.booking_dates?.find((item) => item.date === previewDate);
    const slots = (matched?.time_slots || request?.booking_dates?.[0]?.time_slots || request.time_slots)
        .map((slotName) => {
            const period = props.periods!.find((p) => p.code === slotName || p.label === slotName);
            return period ? (period.code ?? period.label ?? '') : null;
        })
        .filter((s): s is string => s !== null);
    const dateStr = new Date(previewDate).toISOString().split('T')[0];
    return slots.length > 0 ? { date: dateStr, slots } : null;
};
</script>