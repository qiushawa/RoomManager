<template>
    <section class="space-y-3">
        <h4 class="font-semibold">每週固定星期與節次</h4>
        <p class="text-sm text-a-text-muted">
            套用於 {{ startDate }}～{{
                endDate
            }}
            期間內的每個指定星期。點選或拖曳選擇節次；切換星期會重新選取。
        </p>
        <p v-if="loading" role="status" class="text-sm">
            正在彙整整段期間的佔用資訊…
        </p>
        <p v-if="error" role="alert" class="text-sm text-red-500">
            {{ error }}
            <button type="button" class="underline" @click="load">重試</button>
        </p>
        <div
            class="overflow-x-auto"
            :class="{ 'pointer-events-none opacity-50': loading || error }"
            :aria-busy="loading"
        >
            <ScheduleGrid
                class="min-w-[660px]"
                :week-dates="weekDays"
                :periods="periods"
                :occupied-data="occupied"
                :model-value="selectedSlots"
                :theme="isDark ? 'dark' : 'light'"
                :show-header-date="false"
                :non-selectable-dates="disabledWeekdays"
                :show-occupied-labels="true"
                @update:model-value="selectSlots"
                @occupied-click="showDetails"
            />
        </div>
        <p class="text-xs text-a-text-muted">
            色塊代表期間內至少一次佔用，不代表每週都被佔用。點擊色塊查看各項資訊及實際日期，已排除本筆紀錄。
        </p>
        <div
            v-if="activeDetails"
            class="rounded border border-a-border-2 p-3 text-sm"
        >
            <div class="flex justify-between">
                <h5 class="font-semibold">
                    {{ activeLabel }} · 期間內佔用明細
                </h5>
                <button type="button" @click="activeDetails = null">
                    關閉
                </button>
            </div>
            <ul class="max-h-48 space-y-3 overflow-y-auto pt-2">
                <li v-for="(detail, index) in activeDetails" :key="index">
                    <p>
                        {{ STATUS_LABELS[detail.status] }} · {{ detail.title }}
                    </p>
                    <p
                        v-if="detail.instructor || detail.applicant"
                        class="text-a-text-muted"
                    >
                        教師：{{ detail.instructor || '—' }}　借用人：{{
                            detail.applicant || '—'
                        }}
                    </p>
                    <p class="text-xs break-words text-a-text-muted">
                        {{ detail.dates.length }} 次：{{
                            detail.dates.join('、')
                        }}
                    </p>
                </li>
            </ul>
        </div>
        <button
            type="button"
            class="text-sm text-primary"
            @click="
                emit('change', { day_of_week: dayOfWeek, time_slot_ids: [] })
            "
        >
            清除節次選取
        </button>
        <p class="text-sm">
            目前選擇：每{{ weekdayLabel(dayOfWeek) }}，{{
                selectedLabels || '尚未選擇節次'
            }}
        </p>
    </section>
</template>
<script setup lang="ts">
import { ScheduleGrid } from '@/components/schedule';
import { useAdminTheme } from '@/composables';
import { requestError } from '@/composables/useLongTermRecords';
import { STATUS_LABELS } from '@/constants';
import type {
    OccupiedData,
    OccupiedItem,
    SelectedSlot,
    TimeSlotOption,
    WeekDate,
} from '@/types';
import { weekdayLabel, withBase } from '@/utils';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
const props = defineProps<{
    recordId: number;
    classroomId: number;
    startDate: string;
    endDate: string;
    dayOfWeek: number;
    timeSlotIds: number[];
    timeSlots: TimeSlotOption[];
}>();
const emit = defineEmits<{
    change: [value: { day_of_week: number; time_slot_ids: number[] }];
    busy: [value: boolean];
}>();
const { isDark } = useAdminTheme();
// Grid keys represent weekdays, never a selected calendar date.
const weekDays: WeekDate[] = ['一', '二', '三', '四', '五', '六', '日'].map(
    (dayName, index) => ({ date: '', dayName, fullDate: String(index + 1) }),
);
const periods = computed(() =>
    props.timeSlots.map((slot) => ({
        id: slot.id,
        code: slot.name,
        label: slot.name,
        start_time: slot.start_time,
        end_time: slot.end_time,
    })),
);
const disabledWeekdays = computed(() => {
    const start = new Date(`${props.startDate}T00:00:00`);
    const end = new Date(`${props.endDate}T00:00:00`);
    return weekDays
        .filter((day) => {
            const first = new Date(start);
            first.setDate(
                first.getDate() +
                    ((Number(day.fullDate) - (start.getDay() || 7) + 7) % 7),
            );
            return first > end;
        })
        .map((day) => day.fullDate);
});
const selectedLabels = computed(() =>
    props.timeSlots
        .filter((slot) => props.timeSlotIds.includes(slot.id))
        .map((slot) => slot.name)
        .join('、'),
);
const selectedSlots = computed<SelectedSlot[]>(() =>
    periods.value
        .filter((slot) => props.timeSlotIds.includes(slot.id))
        .map((slot) => ({
            date: String(props.dayOfWeek),
            period: slot.code,
            id: slot.id,
            label: slot.label,
        })),
);
const occupied = ref<OccupiedData>({});
const loading = ref(false);
const error = ref('');
const activeDetails = ref<OccupiedItem['details'] | null>(null);
const activeLabel = ref('');
let version = 0;
onBeforeUnmount(() => {
    version++;
});
watch(
    () => [props.classroomId, props.startDate, props.endDate],
    () => load(),
    { immediate: true },
);
async function load() {
    const id = ++version;
    occupied.value = {};
    activeDetails.value = null;
    error.value = '';
    loading.value = true;
    emit('busy', true);
    try {
        const response = await window.axios.get<{
            occupied_data: OccupiedData;
        }>(
            withBase(
                `/admin/long-term-borrowing/records/${props.recordId}/availability`,
            ),
            {
                params: {
                    classroom_id: props.classroomId,
                    start_date: props.startDate,
                    end_date: props.endDate,
                },
            },
        );
        if (id === version) occupied.value = response.data.occupied_data;
    } catch (cause) {
        if (id === version) error.value = requestError(cause);
    } finally {
        if (id === version) {
            loading.value = false;
            emit('busy', !!error.value);
        }
    }
}
function selectSlots(slots: SelectedSlot[]) {
    if (loading.value || error.value) return;
    emit('change', {
        day_of_week: slots[0] ? Number(slots[0].date) : props.dayOfWeek,
        time_slot_ids: slots.map((slot) => slot.id),
    });
}
function showDetails(payload: { date: string; period: string; item: unknown }) {
    activeLabel.value = `${weekdayLabel(Number(payload.date))} · ${payload.period} 節`;
    activeDetails.value = (payload.item as OccupiedItem).details ?? null;
}
</script>
