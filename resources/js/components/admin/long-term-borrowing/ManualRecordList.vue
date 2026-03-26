<template>
    <div class="rounded-2xl border border-a-border-card bg-a-surface">
        <div class="flex items-center justify-between border-b border-a-divider px-4 py-3">
            <span class="text-sm font-medium text-a-text">已儲存記錄</span>
            <span class="rounded-full bg-a-badge px-2 py-0.5 text-xs text-a-text-2">{{ manualRecords.length }}</span>
        </div>

        <div v-if="manualRecords.length === 0" class="px-4 py-12 text-center text-xs text-a-text-dim">
            尚無資料
        </div>

        <ul v-else class="max-h-[620px] divide-y divide-a-divider overflow-auto">
            <li
                v-for="record in manualRecords"
                :key="record.id"
                class="px-4 py-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-medium text-a-text">{{ record.classroom_code }}</p>
                            <span
                                class="shrink-0 rounded border px-1.5 py-0.5 text-[10px]"
                                :class="record.type === 'borrowed'
                                    ? 'border-primary/30 bg-primary/10 text-primary'
                                    : record.type === 'manual'
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                        : 'border-a-border-2 bg-a-surface-hover text-a-text-muted'"
                            >
                                {{ record.type === 'borrowed' ? '一般借用' : record.type === 'manual' ? '手動課程' : '課表匯入' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-a-text-muted">{{ weekdayLabel(record.day_of_week) }}</p>
                        <p class="text-xs text-a-text-dim">{{ record.start_date }} ~ {{ record.end_date }}</p>
                        <p class="mt-0.5 truncate text-xs text-a-text-muted">
                            {{ record.teacher_name }}
                            <span v-if="record.course_name">｜{{ record.course_name }}</span>
                        </p>
                        <p class="text-xs text-a-text-dim">{{ record.start_slot }} — {{ record.end_slot }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg border border-red-500/30 px-2.5 py-1 text-[11px] font-medium text-red-400 transition-colors hover:border-red-500/50 hover:bg-red-500/10"
                        @click="emit('revoke', record)"
                    >
                        撤回
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import type { ManualRecord } from '@/types';
import { weekdayLabel } from '@/utils';

defineProps<{
    manualRecords: ManualRecord[];
}>();

const emit = defineEmits<{
    (e: 'revoke', record: ManualRecord): void;
}>();
</script>
