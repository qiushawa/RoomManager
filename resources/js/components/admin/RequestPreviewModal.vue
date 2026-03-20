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
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click="handleClose"
            >
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="request"
                        class="flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-a-border-card bg-a-bg shadow-2xl"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-a-border px-5 py-3">
                            <div>
                                <h3 class="text-base font-semibold text-a-text">預約申請審核</h3>
                                <p class="mt-0.5 text-xs text-a-text-muted">
                                    申請編號 #{{ request.id }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-md border border-a-border-2 bg-a-badge px-2.5 py-1 text-xs text-a-text-2">
                                    {{ request.time_slots.length }} 節
                                </span>
                                <span class="rounded-md border border-a-border-2 bg-a-badge px-2.5 py-1 text-xs font-medium text-a-text-2">
                                    {{ request.classroom?.code }}
                                </span>
                                <button
                                    class="rounded p-1 text-a-text-muted hover:bg-a-surface-2 hover:text-a-text transition-colors"
                                    @click="handleClose"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex min-h-0 flex-1">
                            <!-- 左側：排程表格 -->
                            <div class="w-[35%] overflow-y-auto border-r border-a-border bg-a-header p-3">
                                <PreviewScheduleGrid
                                    v-if="props.periods"
                                    :weekDates="getWeekDatesForRequest(request)"
                                    :periods="props.periods"
                                    :occupiedData="getOccupiedDataForRequest(request)"
                                    :selectedSlots="getSelectedSlotsForRequest(request)"
                                    :highlightInfo="getHighlightInfoForRequest(request)"
                                />
                            </div>

                            <!-- 右側：詳細資訊 -->
                            <div class="flex w-[65%] flex-col overflow-y-auto">
                                <!-- 借用人資訊 -->
                                <div class="border-b border-a-divider bg-a-surface-hover px-5 py-4">
                                    <h4 class="mb-2 text-base font-semibold text-a-text">
                                        {{ request.borrower?.name }}
                                        <span class="ml-2 text-xs font-normal text-a-text-muted">
                                            {{ request.borrower?.identity_code }}
                                        </span>
                                    </h4>
                                    <div class="space-y-1 text-sm text-a-text-2">
                                        <div>電話：{{ request.borrower?.phone || '—' }}</div>
                                        <div>信箱：{{ request.borrower?.email || '—' }}</div>
                                    </div>
                                </div>

                                <!-- 詳細資訊 -->
                                <div class="space-y-4 p-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1 block text-xs text-a-text-dim">預約日期</label>
                                            <div class="text-sm font-medium text-a-text-body">
                                                {{ formatDate(request.date) }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs text-a-text-dim">指導老師</label>
                                            <div class="text-sm font-medium text-a-text-body">
                                                {{ request.teacher || '無' }}
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="border-a-divider" />

                                    <div>
                                        <label class="mb-1 block text-xs text-a-text-dim">借用時段</label>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span v-for="slot in request.time_slots" :key="slot"
                                                class="inline-block rounded-md bg-a-badge border border-a-divider px-2 py-1 text-xs text-a-text-2">
                                                {{ formatPeriodLabel(slot) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs text-a-text-dim">申請事由</label>
                                        <div
                                            class="min-h-[80px] whitespace-pre-wrap rounded-lg border border-a-divider bg-a-surface-hover p-3 text-sm text-a-text-2"
                                        >
                                            {{ request.reason || '未填寫具體事由' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer：操作按鈕 -->
                        <div v-if="request.status === 0" class="flex items-center justify-end gap-3 border-t border-a-border px-5 py-3">
                            <button
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-500/15 border border-red-500/25 px-4 py-2 text-xs font-semibold text-red-400 transition-colors hover:bg-red-500/25"
                                @click="handleReject"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                拒絕申請
                            </button>
                            <button
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-5 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
                                @click="handleApprove"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                批准申請
                            </button>
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

const { theme } = useAdminTheme();

const handleClose = () => emit('close');

const handleApprove = () => {
    if (!props.request) return;
    emit('approve', props.request.id);
};

const handleReject = () => {
    if (!props.request) return;
    emit('reject', props.request.id);
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
    if (!request?.date) return [];
    const date = new Date(request.date);
    const dayOfWeek = date.getDay();
    const dayNames = ['日', '一', '二', '三', '四', '五', '六'];
    const dateStr = date.toISOString().split('T')[0];
    return [{
        date: dateStr,
        dayName: dayNames[dayOfWeek],
        fullDate: dateStr,
    }];
};

const getOccupiedDataForRequest = (request: AdminBookingItem): OccupiedData => {
    if (!request?.date) return {};
    const dateStr = new Date(request.date).toISOString().split('T')[0];
    return { [dateStr]: {} };
};

const getSelectedSlotsForRequest = (request: AdminBookingItem): SelectedSlot[] => {
    if (!request || !props.periods) return [];

    const dateStr = new Date(request.date).toISOString().split('T')[0];
    return request.time_slots
        .map((slotName) => {
            const period = props.periods!.find(
                (p) => p.code === slotName || p.label === slotName,
            );
            if (!period) return null;
            return {
                date: dateStr,
                period: period.code ?? period.label ?? '',
                id: period.id,
                label: `${formatDate(request.date)} ${formatPeriodLabel(slotName)}`,
            };
        })
        .filter((s): s is SelectedSlot => s !== null);
};

const getHighlightInfoForRequest = (request: AdminBookingItem): HighlightInfo | null => {
    if (!request || !props.periods) return null;

    const dateStr = new Date(request.date).toISOString().split('T')[0];
    const slots = request.time_slots
        .map((slotName) => {
            const period = props.periods!.find(
                (p) => p.code === slotName || p.label === slotName,
            );
            return period ? (period.code ?? period.label ?? '') : null;
        })
        .filter((s): s is string => s !== null);

    return slots.length > 0 ? { date: dateStr, slots } : null;
};
</script>
