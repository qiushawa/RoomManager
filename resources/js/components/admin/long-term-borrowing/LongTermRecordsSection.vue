<template>
    <section class="space-y-4 rounded-xl bg-a-surface p-5 text-a-text">
        <h3 class="text-lg font-bold">長期借用紀錄</h3>
        <form class="grid gap-3 sm:grid-cols-3" @submit.prevent="load(1)">
            <label
                >學期<select v-model="filters.semester_id" class="field">
                    <option value="">全部學期</option>
                    <option
                        v-for="s in semesters"
                        :key="s.id"
                        :value="String(s.id)"
                    >
                        {{ s.label }}
                    </option>
                </select></label
            >
            <label
                >類型<select v-model="filters.type" class="field">
                    <option value="">全部類型</option>
                    <option
                        v-for="(label, key) in typeLabels"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </select></label
            >
            <label
                >大樓<select v-model="filters.building" class="field">
                    <option value="">全部大樓</option>
                    <option
                        v-for="b in buildingOptions"
                        :key="b.code"
                        :value="b.code"
                    >
                        {{ b.label }}
                    </option>
                </select></label
            >
            <label
                >教室<select v-model="filters.classroom_id" class="field">
                    <option value="">全部教室</option>
                    <option
                        v-for="r in classrooms"
                        :key="r.id"
                        :value="String(r.id)"
                    >
                        {{ r.code }} {{ r.name }}
                    </option>
                </select></label
            >
            <label
                >星期<select v-model="filters.day_of_week" class="field">
                    <option value="">全部星期</option>
                    <option v-for="d in 7" :key="d" :value="String(d)">
                        {{ weekdayLabel(d) }}
                    </option>
                </select></label
            >
            <label
                >搜尋<input
                    v-model="filters.search"
                    class="field"
                    maxlength="100"
                    placeholder="教室、名稱、班級、教師"
            /></label>
            <button
                class="rounded bg-primary px-4 py-2 text-white disabled:opacity-50"
                :disabled="loading"
            >
                搜尋／套用篩選
            </button>
        </form>
        <p v-if="error" role="alert" class="text-red-500">{{ error }}</p>
        <p v-if="message" role="status" class="text-emerald-500">
            {{ message }}
        </p>
        <p v-if="loading" role="status">載入中…</p>
        <div v-else-if="records" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr>
                        <th
                            v-for="h in [
                                '學期／類型',
                                '教室',
                                '名稱／教師',
                                '開課班級',
                                '星期／節次',
                                '有效日期',
                                '操作',
                            ]"
                            :key="h"
                            class="p-3"
                        >
                            {{ h }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="record in records.data"
                        :key="record.id"
                        class="border-t border-a-divider"
                    >
                        <td class="p-3">
                            {{
                                semesters.find(
                                    (s) => s.id === record.semester_id,
                                )?.label
                            }}
                            <div class="text-a-text-muted">
                                {{ typeLabels[record.type] }}
                            </div>
                        </td>
                        <td class="p-3">
                            {{ record.classroom?.code }}
                            <div>{{ record.classroom?.name }}</div>
                        </td>
                        <td class="p-3">
                            {{ record.course_name }}
                            <div class="text-a-text-muted">
                                {{ record.teacher_name }}
                            </div>
                        </td>
                        <td class="p-3">
                            {{ record.class_name || '—' }}
                        </td>
                        <td class="p-3">
                            {{ weekdayLabel(record.day_of_week) }}
                            <div>
                                {{
                                    record.time_slots
                                        .map((s) => s.name)
                                        .join('、')
                                }}
                            </div>
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            {{
                                record.start_date ??
                                semesterFor(record)?.start_date
                            }}
                            <div>
                                ～
                                {{
                                    record.end_date ??
                                    semesterFor(record)?.end_date
                                }}
                            </div>
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            <button
                                class="mr-3 text-primary"
                                :disabled="deleting"
                                @click="editing = record"
                            >
                                編輯</button
                            ><button
                                class="text-red-500"
                                :disabled="deleting"
                                @click="remove(record)"
                            >
                                刪除
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!records.data.length" class="p-8 text-center">
                沒有符合條件的紀錄。
            </p>
            <div class="flex items-center justify-between pt-4">
                <span
                    >共 {{ records.total }} 筆，第
                    {{ records.current_page }}／{{ records.last_page }} 頁</span
                >
                <div class="flex gap-3">
                    <button
                        :disabled="records.current_page <= 1"
                        @click="load(records.current_page - 1)"
                    >
                        上一頁</button
                    ><button
                        :disabled="records.current_page >= records.last_page"
                        @click="load(records.current_page + 1)"
                    >
                        下一頁
                    </button>
                </div>
            </div>
        </div>
        <LongTermRecordEditor
            v-if="editing"
            :record="editing"
            :semester="semesterFor(editing)"
            :classrooms="classrooms"
            :time-slots="timeSlots"
            @close="editing = null"
            @saved="saved"
        />
    </section>
</template>
<script setup lang="ts">
import {
    requestError,
    useLongTermRecords,
} from '@/composables/useLongTermRecords';
import type {
    BuildingOption,
    ClassroomOption,
    LongTermRecord,
    SemesterOption,
    TimeSlotOption,
} from '@/types';
import { weekdayLabel, withBase } from '@/utils';
import { ref } from 'vue';
import LongTermRecordEditor from './LongTermRecordEditor.vue';
const props = defineProps<{
    semesters: SemesterOption[];
    defaultSemesterId: number | null;
    classrooms: ClassroomOption[];
    buildingOptions: BuildingOption[];
    timeSlots: TimeSlotOption[];
}>();
const emit = defineEmits<{ changed: [] }>();
const { filters, records, loading, error, load } = useLongTermRecords(
    props.defaultSemesterId,
);
const typeLabels = {
    course: '課表匯入',
    manual: '手動課程',
    borrowed: '一般長期借用',
};
const editing = ref<LongTermRecord | null>(null);
const deleting = ref(false);
const message = ref('');
const semesterFor = (record: LongTermRecord) =>
    props.semesters.find((s) => s.id === record.semester_id);
function saved() {
    editing.value = null;
    message.value = '紀錄已更新。';
    emit('changed');
    load(records.value?.current_page ?? 1);
}
async function remove(record: LongTermRecord) {
    if (
        !confirm(
            `刪除 ${semesterFor(record)?.label} ${record.classroom?.code}「${record.course_name}」\n${weekdayLabel(record.day_of_week)} ${record.time_slots.map((s) => s.name).join('、')}？`,
        )
    )
        return;
    deleting.value = true;
    error.value = '';
    message.value = '';
    try {
        await window.axios.delete(
            withBase(`/admin/long-term-borrowing/records/${record.id}`),
        );
        message.value = '紀錄已刪除。';
        emit('changed');
        await load(
            records.value?.data.length === 1
                ? Math.max(1, records.value.current_page - 1)
                : (records.value?.current_page ?? 1),
        );
    } catch (cause) {
        error.value = requestError(cause);
    } finally {
        deleting.value = false;
    }
}
</script>
<style scoped>
.field {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    border: 1px solid var(--color-a-border-2);
    border-radius: 0.5rem;
    padding: 0.5rem;
    background: var(--color-a-surface);
}
button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
