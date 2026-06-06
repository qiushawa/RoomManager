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
                        <RequestPreviewHeader :request="request" @close="handleClose" />

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

                                <RequestPreviewBorrowerSection :request="request" />

                                <RequestPreviewBasicInfoGrid :request="request" />

                                <RequestPreviewTimeSlotsSection
                                    :request="request"
                                    :resolved-preview-date="resolvedPreviewDate"
                                    :is-dark="isDark"
                                    @select-preview-date="selectPreviewDate"
                                />

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
import {
    RequestPreviewBasicInfoGrid,
    RequestPreviewBorrowerSection,
    RequestPreviewHeader,
    RequestPreviewTimeSlotsSection,
} from '@/components/admin/preview/request-preview';
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