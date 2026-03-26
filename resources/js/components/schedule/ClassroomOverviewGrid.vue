<template>
    <div
        class="animate-fade-in flex w-full flex-col overflow-hidden rounded shadow-sm select-none"
        :class="[
            isDarkTheme
                ? 'border-slate-600 bg-slate-900'
                : 'border-gray-400 bg-white'
        ]"
    >
        <table class="flex h-full w-full flex-col">
            <thead
                class="shrink-0 border-b"
                :class="isDarkTheme ? 'border-slate-600 bg-slate-800' : 'border-gray-400 bg-gray-50'"
            >
                <tr class="flex w-full">
                    <th
                        class="flex w-24 shrink-0 items-center justify-center border-r py-2 text-xs"
                        :class="isDarkTheme ? 'border-slate-600 text-slate-300' : 'border-gray-300 text-gray-500'"
                    >
                        節次
                    </th>
                    <th
                        v-for="room in rooms"
                        :key="room.id"
                        class="flex flex-1 flex-col items-center justify-center border-r py-2 last:border-r-0"
                        :class="isDarkTheme ? 'border-slate-600' : 'border-gray-300'"
                    >
                        <div class="text-sm font-bold" :class="isDarkTheme ? 'text-slate-100' : 'text-gray-700'">
                            {{ room.code }}
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody class="no-scrollbar flex min-h-0 w-full flex-1 flex-col overflow-y-auto">
                <tr
                    v-for="(period, pIndex) in periods"
                    :key="period.code"
                    class="flex min-h-[40px] w-full flex-1 border-b transition-colors last:border-b-0"
                    :class="isDarkTheme ? 'border-slate-600 hover:bg-sky-900/20' : 'border-gray-300 hover:bg-blue-50/30'"
                >
                    <td
                        class="flex w-24 shrink-0 flex-col items-center justify-center border-r px-1"
                        :class="isDarkTheme ? 'border-slate-600 bg-slate-800' : 'border-gray-300 bg-gray-50'"
                    >
                        <span class="text-sm font-bold" :class="isDarkTheme ? 'text-slate-100' : 'text-gray-700'">{{ formatPeriodLabel(period.label) }}</span>
                        <span v-if="period.start_time && period.end_time" class="text-[10px]" :class="isDarkTheme ? 'text-slate-400' : 'text-gray-400'">
                            {{ formatTime(period.start_time) }}~{{ formatTime(period.end_time) }}
                        </span>
                    </td>

                    <td
                        v-for="room in rooms"
                        :key="`${room.id}-${period.code}`"
                        class="relative flex-1 border-r p-0 last:border-r-0"
                        :class="isDarkTheme ? 'border-slate-600' : 'border-gray-300'"
                    >
                        <div
                            v-if="getCellOccupancy(room.code, period.code).status"
                            class="group absolute inset-0 z-10 flex cursor-not-allowed items-center justify-center text-xs text-white hover:z-[60]"
                            :class="getStatusClass(getCellOccupancy(room.code, period.code).status)"
                        >
                            <OccupiedTooltip
                                :item="getCellOccupancy(room.code, period.code).item"
                                :show-below="pIndex < 3"
                            />
                        </div>

                        <label
                            v-else
                            :for="`overview-slot-${room.code}-${period.code}`"
                            class="group absolute inset-0 flex cursor-pointer items-center justify-center select-none"
                            @mousedown.prevent="handleMouseDown(room, period)"
                            @mouseenter="handleMouseEnter(room, period)"
                            @dragstart="preventDragDefault"
                        >
                            <input
                                type="checkbox"
                                :id="`overview-slot-${room.code}-${period.code}`"
                                :checked="isSelectedRoomPeriod(room.code, period.code)"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                                @change.prevent
                            />
                            <div
                                class="flex h-full w-full items-center justify-center transition-all duration-200"
                                :class="isSelectedRoomPeriod(room.code, period.code) ? 'bg-opacity-80 bg-success shadow-inner' : ''"
                            >
                                <span
                                    v-if="isSelectedRoomPeriod(room.code, period.code)"
                                    class="text-lg font-bold text-white"
                                >✓</span>
                            </div>
                            <span
                                class="pointer-events-none absolute inset-0 opacity-0 transition-opacity group-hover:opacity-30"
                                :class="isDarkTheme ? 'bg-sky-700/30' : 'bg-blue-100'"
                            ></span>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { useScheduleStatus } from '@/composables';
import type { OccupiedData, OccupiedStatus, Period, Room, SelectedSlot } from '@/types';
import { formatPeriodLabel, formatSlotLabel, formatTime } from '@/utils';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import OccupiedTooltip from './OccupiedTooltip.vue';

const props = withDefaults(
    defineProps<{
        date: string;
        dayName: string;
        rooms: Room[];
        periods: Period[];
        occupiedData?: OccupiedData;
        selectedRoomCode?: string | null;
        modelValue?: SelectedSlot[];
        theme?: 'auto' | 'light' | 'dark';
    }>(),
    {
        occupiedData: () => ({}),
        selectedRoomCode: null,
        modelValue: () => [],
        theme: 'auto',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: SelectedSlot[]): void;
    (e: 'update:selectedRoomCode', value: string | null): void;
}>();

const {
    getOccupiedItem,
    getStatusClassByStatus,
} = useScheduleStatus({
    occupiedData: () => props.occupiedData,
    defaultClass: 'bg-gray-400/90',
});

const prefersDark = ref(false);
let mediaQuery: MediaQueryList | null = null;
const handleSchemeChange = (e: MediaQueryListEvent) => {
    prefersDark.value = e.matches;
};

const isDarkTheme = computed(() => {
    if (props.theme === 'dark') return true;
    if (props.theme === 'light') return false;
    return prefersDark.value;
});

const getStatusClass = (status: OccupiedStatus | null): string => getStatusClassByStatus(status);

type CellOccupancy = {
    item: ReturnType<typeof getOccupiedItem>;
    status: OccupiedStatus | null;
};

const EMPTY_CELL_OCCUPANCY: CellOccupancy = {
    item: null,
    status: null,
};

const cellOccupancyMap = computed<Record<string, CellOccupancy>>(() => {
    const map: Record<string, CellOccupancy> = {};

    for (const room of props.rooms) {
        for (const period of props.periods) {
            const key = `${room.code}-${period.code}`;
            const item = getOccupiedItem(room.code, period.code);
            const status = !item
                ? null
                : typeof item === 'string'
                    ? (item as OccupiedStatus)
                    : (item.status as OccupiedStatus);

            map[key] = { item, status };
        }
    }

    return map;
});

const getCellOccupancy = (roomCode: string, periodCode: string) => {
    const key = `${roomCode}-${periodCode}`;
    return cellOccupancyMap.value[key] ?? EMPTY_CELL_OCCUPANCY;
};

const isSelectedRoomPeriod = (roomCode: string, periodCode: string): boolean => {
    if (props.selectedRoomCode !== roomCode) return false;
    return props.modelValue.some((slot) => slot.date === props.date && slot.period === periodCode);
};

const isDragging = ref(false);
const dragMode = ref<'select' | 'deselect'>('select');
const currentDragRoomCode = ref('');

const preventDragDefault = (e: DragEvent) => e.preventDefault();

const handleMouseDown = (room: Room, period: Period) => {
    isDragging.value = true;
    currentDragRoomCode.value = room.code;

    const currentSlots = props.selectedRoomCode === room.code ? [...props.modelValue] : [];
    const alreadySelected = isSelectedRoomPeriod(room.code, period.code);
    dragMode.value = alreadySelected ? 'deselect' : 'select';

    emit('update:selectedRoomCode', room.code);
    applySelection(period, currentSlots, dragMode.value);
};

const handleMouseEnter = (room: Room, period: Period) => {
    if (!isDragging.value) return;
    if (currentDragRoomCode.value !== room.code) return;

    const currentSlots = props.selectedRoomCode === room.code ? [...props.modelValue] : [];
    applySelection(period, currentSlots, dragMode.value);
};

const handleMouseUp = () => {
    isDragging.value = false;
    currentDragRoomCode.value = '';
};

const applySelection = (
    period: Period,
    currentSlots: SelectedSlot[],
    mode: 'select' | 'deselect',
) => {
    const targetCode = period.code;

    if (mode === 'select') {
        if (!currentSlots.some((slot) => slot.date === props.date && slot.period === targetCode)) {
            currentSlots.push({
                date: props.date,
                period: targetCode,
                id: period.id,
                label: formatSlotLabel(props.date, props.dayName, period.label),
            });
            currentSlots.sort((a, b) => {
                const idxA = props.periods.findIndex((p) => p.code === a.period);
                const idxB = props.periods.findIndex((p) => p.code === b.period);
                return idxA - idxB;
            });
        }
    } else {
        const nextSlots = currentSlots.filter((slot) => !(slot.date === props.date && slot.period === targetCode));
        emit('update:modelValue', nextSlots);
        if (nextSlots.length === 0) {
            emit('update:selectedRoomCode', null);
        }
        return;
    }

    emit('update:modelValue', currentSlots);
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('mouseup', handleMouseUp);
        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        prefersDark.value = mediaQuery.matches;
        mediaQuery.addEventListener('change', handleSchemeChange);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('mouseup', handleMouseUp);
    }
    if (mediaQuery) {
        mediaQuery.removeEventListener('change', handleSchemeChange);
    }
});
</script>
