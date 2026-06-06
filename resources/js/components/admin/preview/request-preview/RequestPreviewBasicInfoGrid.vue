<template>
    <div class="grid grid-cols-3 gap-px border-b border-a-divider bg-a-divider">
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
</template>

<script setup lang="ts">
import type { AdminBookingItem } from '@/types';

defineProps<{
    request: AdminBookingItem;
}>();

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
    return summary.replace(/\d{4}-\d{2}-\d{2}/g, (matched) => formatMonthDay(matched));
};
</script>
