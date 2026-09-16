import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { OccupiedData, TimeSlotOption } from '@/types';
import { withBase } from '@/utils';
import { requestError } from './useLongTermRecords';

export function useManualScheduleAvailability(getScope: () => { classroom_id: number | ''; start_date: string; end_date: string }, getSlots: () => TimeSlotOption[]) {
    const occupied = ref<OccupiedData>({});
    const loading = ref(false);
    const error = ref('');
    const ready = computed(() => {
        const scope = getScope();
        return !!scope.classroom_id && !!scope.start_date && !!scope.end_date && scope.start_date <= scope.end_date;
    });
    let version = 0;
    onBeforeUnmount(() => { version++; });
    async function reload() {
        const id = ++version;
        occupied.value = {}; error.value = ''; loading.value = false;
        if (!ready.value) return;
        loading.value = true;
        try {
            const response = await window.axios.get<{ occupied_data: OccupiedData }>(withBase('/admin/long-term-borrowing/manual/availability'), { params: getScope() });
            if (id !== version) return;
            const mapped: OccupiedData = {};
            // Manual conflict payloads use chronological slot positions, including lunch.
            Object.entries(response.data.occupied_data).forEach(([weekday, slots]) => {
                mapped[weekday] = {};
                getSlots().forEach((slot, index) => {
                    if (slots[slot.name]) mapped[weekday][String(index + 1)] = slots[slot.name];
                });
            });
            occupied.value = mapped;
        } catch (cause) { if (id === version) error.value = requestError(cause); }
        finally { if (id === version) loading.value = false; }
    }
    watch(getScope, () => reload(), { immediate: true });
    return { occupied, loading, error, ready, reload };
}
