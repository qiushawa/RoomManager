<template>
    <div v-if="summary" class="mt-6 divide-y divide-stone-200 border-y border-stone-200">
        <div
            v-for="item in summaryItems"
            :key="item.label"
            class="grid gap-2 py-4 sm:grid-cols-[140px_minmax(0,1fr)] sm:gap-6"
        >
            <div class="text-sm font-medium text-stone-400">
                {{ item.label }}
            </div>
            <div class="text-sm leading-7 text-slate-800">
                <template v-if="item.list">
                    <ul class="list-disc pl-5">
                        <li v-for="entry in item.list" :key="entry">{{ entry }}</li>
                    </ul>
                </template>
                <template v-else>
                    {{ item.value }}
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { BookingCancellationSummary, BookingCancellationSummaryItem } from '@/types';

const props = defineProps<{
    summary: BookingCancellationSummary | null;
}>();

const summaryItems = computed<BookingCancellationSummaryItem[]>(() => {
    if (!props.summary) return [];

    return [
        { label: '申請人', value: props.summary.borrower_name },
        { label: '教室', value: props.summary.classroom_name },
        { label: '借用日期', value: props.summary.date },
        { label: '指導老師', value: props.summary.teacher },
        { label: '借用事由', value: props.summary.reason },
        { label: '申請時段', list: props.summary.time_slots },
    ];
});
</script>
