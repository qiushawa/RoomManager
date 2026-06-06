<template>
    <div class="border-b border-a-divider px-5 py-4">
        <p class="mb-3 text-[11px] uppercase tracking-wide text-a-text-dim">借用時段</p>

        <template v-if="request.is_multi_day && request.booking_dates?.length">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs text-a-text-muted">
                    共 {{ request.booking_dates.length }} 天，點選切換左側預覽
                </span>
                <span class="text-xs text-a-text-dim">
                    {{ request.booking_dates.reduce((acc, d) => acc + d.time_slots.length, 0) }} 節總計
                </span>
            </div>

            <div
                class="max-h-[168px] overflow-y-auto overscroll-contain rounded-xl border divide-y"
                :class="isDark
                    ? 'divide-a-divider border-a-divider bg-a-bg'
                    : 'divide-slate-200 border-slate-200 bg-slate-50/70'"
            >
                <button
                    v-for="item in request.booking_dates"
                    :key="item.date"
                    class="group flex w-full items-center gap-3 px-3 py-2.5 text-left transition-colors"
                    :class="resolvedPreviewDate === item.date
                        ? (isDark ? 'bg-primary/8' : 'bg-blue-50')
                        : (isDark ? 'bg-a-badge hover:bg-a-surface' : 'bg-white hover:bg-slate-50')"
                    @click="$emit('select-preview-date', item.date)"
                >
                    <div
                        class="h-7 w-0.5 shrink-0 rounded-full transition-colors"
                        :class="resolvedPreviewDate === item.date ? 'bg-primary' : 'bg-transparent'"
                    />

                    <div class="w-[100px] shrink-0">
                        <p
                            class="text-xs font-semibold leading-snug"
                            :class="resolvedPreviewDate === item.date ? 'text-primary' : 'text-a-text-body'"
                        >
                            {{ formatDateShort(item.date) }}
                        </p>
                        <p class="text-[11px] text-a-text-dim">{{ item.time_slots.length }} 節</p>
                    </div>

                    <div class="flex flex-1 flex-wrap gap-1 overflow-hidden" style="max-height: 2.5rem;">
                        <span
                            v-for="slot in item.time_slots.slice(0, 4)"
                            :key="`${item.date}-${slot}`"
                            class="rounded px-1.5 py-0.5 text-[11px] font-medium"
                            :class="resolvedPreviewDate === item.date
                                ? 'bg-primary/12 text-primary'
                                : (isDark ? 'border border-a-divider bg-a-surface text-a-text-2' : 'bg-slate-100 text-slate-700')"
                        >
                            {{ formatPeriodLabel(slot) }}
                        </span>
                        <span
                            v-if="item.time_slots.length > 4"
                            class="rounded border border-a-divider px-1.5 py-0.5 text-[11px] text-a-text-muted"
                            :class="isDark ? 'bg-a-surface' : 'border-slate-200 bg-slate-100 text-slate-600'"
                        >
                            +{{ item.time_slots.length - 4 }}
                        </span>
                    </div>

                    <svg
                        class="h-3 w-3 shrink-0 transition-opacity"
                        :class="resolvedPreviewDate === item.date ? 'text-primary opacity-100' : 'text-a-text-muted opacity-0 group-hover:opacity-60'"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </template>

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
</template>

<script setup lang="ts">
import type { AdminBookingItem } from '@/types';
import { formatPeriodLabel } from '@/utils';

defineProps<{
    request: AdminBookingItem;
    resolvedPreviewDate: string;
    isDark: boolean;
}>();

defineEmits<{
    'select-preview-date': [date: string];
}>();

const formatDateShort = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-TW', {
        month: 'numeric',
        day: 'numeric',
        weekday: 'short',
    });
};
</script>
