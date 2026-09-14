<template>
    <BaseModal :show="true" @close="!saving && emit('close')">
        <form
            class="max-h-[85vh] space-y-4 overflow-y-auto p-6"
            @submit.prevent="save"
        >
            <h3 class="text-lg font-bold">編輯長期借用紀錄</h3>
            <p class="text-sm text-a-text-muted">
                {{ semester?.label }} ·
                {{
                    record.type === 'course'
                        ? '課表匯入（重新匯入將覆寫本次修改）'
                        : '長期借用'
                }}
            </p>
            <p v-if="error" role="alert" class="text-red-500">{{ error }}</p>
            <label class="block"
                >名稱<input
                    v-model.trim="form.course_name"
                    required
                    maxlength="100"
                    class="field"
            /></label>
            <label class="block"
                >教師<input
                    v-model.trim="form.teacher_name"
                    maxlength="50"
                    class="field"
            /></label>
            <label class="block"
                >教室<select v-model="form.classroom_id" class="field">
                    <option
                        v-for="room in classrooms"
                        :key="room.id"
                        :value="room.id"
                    >
                        {{ room.code }} {{ room.name }}
                    </option>
                </select></label
            >
            <label class="block"
                >星期<select v-model="form.day_of_week" class="field">
                    <option v-for="day in 7" :key="day" :value="day">
                        {{ weekdayLabel(day) }}
                    </option>
                </select></label
            >
            <div class="grid grid-cols-2 gap-3">
                <label
                    >開始日期<input
                        v-model="form.start_date"
                        type="date"
                        required
                        :min="semester?.start_date"
                        :max="semester?.end_date"
                        class="field"
                /></label>
                <label
                    >結束日期<input
                        v-model="form.end_date"
                        type="date"
                        required
                        :min="form.start_date"
                        :max="semester?.end_date"
                        class="field"
                /></label>
            </div>
            <fieldset>
                <legend class="mb-2">節次（可不連續）</legend>
                <div class="flex flex-wrap gap-3">
                    <label
                        v-for="slot in timeSlots"
                        :key="slot.id"
                        class="flex items-center gap-1"
                        ><input
                            v-model="form.time_slot_ids"
                            type="checkbox"
                            :value="slot.id"
                        />{{ slot.name }}</label
                    >
                </div>
            </fieldset>
            <div class="flex justify-end gap-3">
                <button type="button" :disabled="saving" @click="emit('close')">
                    取消</button
                ><button
                    class="rounded bg-primary px-4 py-2 text-white disabled:opacity-50"
                    :disabled="saving || !form.time_slot_ids.length"
                >
                    {{ saving ? '儲存中…' : '儲存修改' }}
                </button>
            </div>
        </form>
    </BaseModal>
</template>
<script setup lang="ts">
import { BaseModal } from '@/components';
import { requestError } from '@/composables/useLongTermRecords';
import type {
    ClassroomOption,
    LongTermRecord,
    SemesterOption,
    TimeSlotOption,
} from '@/types';
import { weekdayLabel, withBase } from '@/utils';
import { reactive, ref } from 'vue';
const props = defineProps<{
    record: LongTermRecord;
    semester?: SemesterOption;
    classrooms: ClassroomOption[];
    timeSlots: TimeSlotOption[];
}>();
const emit = defineEmits<{ close: []; saved: [] }>();
const form = reactive({
    classroom_id: props.record.classroom_id,
    course_name: props.record.course_name,
    teacher_name: props.record.teacher_name ?? '',
    day_of_week: props.record.day_of_week,
    start_date: props.record.start_date ?? props.semester?.start_date ?? '',
    end_date: props.record.end_date ?? props.semester?.end_date ?? '',
    time_slot_ids: props.record.time_slots.map((s) => s.id),
});
const saving = ref(false);
const error = ref('');
async function save() {
    if (saving.value) return;
    saving.value = true;
    error.value = '';
    try {
        await window.axios.patch(
            withBase(`/admin/long-term-borrowing/records/${props.record.id}`),
            form,
        );
        emit('saved');
    } catch (cause) {
        error.value = requestError(cause);
    } finally {
        saving.value = false;
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
</style>
