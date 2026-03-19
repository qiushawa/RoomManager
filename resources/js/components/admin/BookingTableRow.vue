<template>
    <tr
        class="cursor-pointer transition-colors hover:bg-a-surface-hover"
        :class="rowClass"
        @click="emit('open-preview', booking)"
    >
        <td class="px-4 py-3">
            <p class="font-medium text-a-text-body">{{ booking.borrower?.name ?? '-' }}</p>
            <p class="text-xs text-a-text-dim">{{ booking.borrower?.department ?? '' }}</p>
        </td>
        <td class="px-4 py-3">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-a-badge px-2 py-1 text-xs font-medium text-a-text-2">
                {{ booking.classroom?.code }}
            </span>
        </td>
        <td class="whitespace-nowrap px-4 py-3 text-a-text-2">{{ booking.date }}</td>
        <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="slot in booking.time_slots"
                    :key="slot"
                    class="inline-block rounded bg-a-badge px-1.5 py-0.5 text-[11px] text-a-text-muted"
                >
                    {{ formatPeriodLabel(slot) }}
                </span>
            </div>
        </td>
        <td class="whitespace-nowrap px-4 py-3 text-xs text-a-text-dim">{{ booking.created_at }}</td>
        <td class="px-4 py-3 text-center" @click.stop>
            <div class="flex items-center justify-center gap-1">
                <template v-if="mode === 'bookings'">
                    <template v-if="booking.status === 0">
                        <button
                            class="rounded-md border border-emerald-500/25 bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-400 transition-colors hover:bg-emerald-500/25"
                            @click="emit('approve', booking.id)"
                        >
                            核准
                        </button>
                        <button
                            class="rounded-md border border-red-500/25 bg-red-500/15 px-2.5 py-1 text-xs font-medium text-red-400 transition-colors hover:bg-red-500/25"
                            @click="emit('reject', booking.id)"
                        >
                            拒絕
                        </button>
                    </template>
                    <span v-else :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', statusStyle(booking.status)]">
                        {{ statusLabel(booking.status) }}
                    </span>
                </template>

                <template v-else-if="mode === 'reviews'">
                    <button
                        class="rounded-md border border-emerald-500/25 bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-400 transition-colors hover:bg-emerald-500/25"
                        @click="emit('approve', booking.id)"
                    >
                        核准
                    </button>
                    <button
                        class="rounded-md border border-red-500/25 bg-red-500/15 px-2.5 py-1 text-xs font-medium text-red-400 transition-colors hover:bg-red-500/25"
                        @click="emit('reject', booking.id)"
                    >
                        拒絕
                    </button>
                </template>

                <template v-else>
                    <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', statusStyle(booking.status)]">
                        {{ statusLabel(booking.status) }}
                    </span>
                </template>
            </div>
        </td>
    </tr>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useBookingStatus } from '@/composables';
import { formatPeriodLabel } from '@/utils';

interface BookingBorrower {
    name: string;
    department: string | null;
}

interface BookingClassroom {
    code: string;
}

interface BookingTableItem {
    id: number;
    date: string;
    status: number;
    reason: string | null;
    teacher: string | null;
    created_at: string;
    borrower: BookingBorrower | null;
    classroom: BookingClassroom | null;
    time_slots: string[];
}

const props = defineProps<{
    booking: BookingTableItem;
    mode: 'bookings' | 'reviews' | 'records';
}>();

const emit = defineEmits(['open-preview', 'approve', 'reject']);

const { statusLabel, statusStyle } = useBookingStatus();

const rowClass = computed(() => {
    if (props.mode === 'reviews') return 'bg-blue-500/5';
    if (props.mode === 'bookings' && props.booking.status === 0) return 'bg-blue-500/5';
    return '';
});
</script>
