<template>
    <BaseModal :show="show" size="sm" @close="$emit('close')">
        <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
            <div class="flex items-center gap-2">
                <h4 class="text-base font-bold text-slate-800">處理時段衝突</h4>
            </div>
            <p v-if="activeConflictSlot" class="mt-2 flex items-center gap-1.5 text-sm font-medium text-slate-600">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ `週${weekdayNameMap[activeConflictSlot.dayOfWeek]} 第${periodLabelText(activeConflictSlot.period)}節` }}
            </p>
        </div>

        <div class="space-y-3 px-6 py-5 text-sm">
            <template v-if="activeConflictSlot?.kind === 'schedule'">
                <button
                    type="button"
                    :disabled="loading"
                    class="group flex w-full items-center justify-between rounded-xl border border-rose-200 bg-white p-3.5 text-left shadow-sm transition-all hover:border-rose-300 hover:bg-rose-50 hover:shadow"
                    @click="$emit('action', 'cancel_slot')"
                >
                    <div>
                        <div class="font-semibold text-rose-700">取消該節</div>
                        <div class="mt-0.5 text-xs text-rose-500/80">移除此時段的長期借用安排</div>
                    </div>
                </button>
            </template>

            <template v-else-if="activeConflictSlot?.kind === 'short_term_pending'">
                <button
                    type="button"
                    :disabled="loading"
                    class="group flex w-full items-center justify-between rounded-xl border border-amber-200 bg-white p-3.5 text-left shadow-sm transition-all hover:border-amber-300 hover:bg-amber-50 hover:shadow"
                    @click="$emit('action', 'review_pending')"
                >
                    <div>
                        <div class="font-semibold text-amber-700">跳轉審核</div>
                        <div class="mt-0.5 text-xs text-amber-600/80">保留目前進度為草稿並前往查看</div>
                    </div>
                </button>

                <button
                    type="button"
                    :disabled="loading"
                    class="group flex w-full items-center justify-between rounded-xl border border-rose-200 bg-white p-3.5 text-left shadow-sm transition-all hover:border-rose-300 hover:bg-rose-50 hover:shadow"
                    @click="$emit('action', 'reject_and_override')"
                >
                    <div>
                        <div class="font-semibold text-rose-700">直接覆蓋並拒絕</div>
                        <div class="mt-0.5 text-xs text-rose-500/90">將強制佔用，且整筆短期申請（含其他節次）會被駁回</div>
                    </div>
                </button>
            </template>

            <template v-else-if="activeConflictSlot?.kind === 'short_term_approved'">
                <button
                    type="button"
                    :disabled="loading"
                    class="group flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white p-3.5 text-left shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:shadow"
                    @click="$emit('action', 'defer_to_short_term')"
                >
                    <div>
                        <div class="font-semibold text-slate-700">該節讓給短期借用</div>
                        <div class="mt-0.5 text-xs text-slate-500/80">僅限當日當節，其他週同節仍由長期借用使用</div>
                    </div>
                </button>

                <button
                    type="button"
                    :disabled="loading"
                    class="group flex w-full items-center justify-between rounded-xl border border-rose-200 bg-white p-3.5 text-left shadow-sm transition-all hover:border-rose-300 hover:bg-rose-50 hover:shadow"
                    @click="$emit('action', 'override_with_long_term')"
                >
                    <div>
                        <div class="font-semibold text-rose-700">讓給長期借用 (覆蓋)</div>
                        <div class="mt-0.5 text-xs text-rose-600/90">會駁回整筆短期借用申請（含其他節次），請謹慎操作</div>
                    </div>
                </button>
            </template>
        </div>

        <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-right">
            <button
                type="button"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1"
                @click="$emit('close')"
            >
                關閉
            </button>
        </div>
    </BaseModal>
</template>

<script setup lang="ts">
import { BaseModal } from '@/components';
import type { ManualConflictKind, SlotResolutionAction } from '@/types';

interface ActiveConflictSlot {
    slotKey: string;
    dayOfWeek: number;
    period: number;
    kind: ManualConflictKind;
    conflictKey?: string;
    bookingId?: number;
}

defineProps<{
    show: boolean;
    loading: boolean;
    activeConflictSlot: ActiveConflictSlot | null;
    weekdayNameMap: Record<number, string>;
    periodLabelText: (period: number) => string;
}>();

defineEmits<{
    close: [];
    action: [action: SlotResolutionAction];
}>();
</script>