<template>
    <div v-if="previewSchedules.length > 0" class="overflow-hidden rounded-xl border border-emerald-500/20 bg-emerald-500/5">
        <div class="border-b border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400">
            預覽：{{ previewSchedules.length }} 筆課表 — 確認後才會正式匯入
        </div>
        <div class="max-h-80 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-a-surface-2">
                    <tr class="text-left text-xs text-a-text-muted">
                        <th class="px-4 py-2 font-medium">教室</th>
                        <th class="px-4 py-2 font-medium">星期</th>
                        <th class="px-4 py-2 font-medium">節次</th>
                        <th class="px-4 py-2 font-medium">課程</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-a-divider">
                    <tr
                        v-for="(row, index) in previewSchedules"
                        :key="`${row.classroom_id}-${row.day_of_week}-${row.time_slot_ids.join('-')}-${index}`"
                        class="text-xs"
                    >
                        <td class="px-4 py-2.5 font-medium text-a-text">{{ getClassroomLabel(row.classroom_id) }}</td>
                        <td class="px-4 py-2.5 text-a-text-muted">{{ weekdayLabel(row.day_of_week) }}</td>
                        <td class="px-4 py-2.5 text-a-text-muted">{{ formatPeriodIds(row.time_slot_ids) }}</td>
                        <td class="px-4 py-2.5 text-a-text-muted">{{ row.course_name || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <p v-else class="text-center text-xs text-a-text-dim">
        請先選擇教室後點擊預覽。
    </p>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { ClassroomOption, PreviewSchedule } from '@/types';
import { weekdayLabel } from '@/utils';

const props = defineProps<{
    classrooms: ClassroomOption[];
    previewSchedules: PreviewSchedule[];
}>();

const classroomLabelMap = computed(() => {
    const map = new Map<number, string>();
    props.classrooms.forEach((room) => map.set(room.id, `${room.code} - ${room.name}`));
    return map;
});

const getClassroomLabel = (classroomId: number): string => {
    return classroomLabelMap.value.get(classroomId) ?? `教室 #${classroomId}`;
};

const formatPeriodIds = (ids: number[]): string => {
    if (!ids?.length) return '—';
    return ids.join('、') + ' 節';
};
</script>
