<template>
    <tr
        class="group cursor-pointer transition-all duration-150 hover:bg-a-surface-hover"
        :class="rowClass"
        style="max-height: 72px;"
        @click="emit('open-preview', booking)"
    >
        <!-- 借用人 -->
        <td class="px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                    {{ booking.borrower?.name?.charAt(0) ?? '?' }}
                </div>
                <div class="min-w-0">
                    <p class="truncate font-medium text-a-text-body">{{ booking.borrower?.name ?? '-' }}</p>
                    <p class="truncate text-xs text-a-text-dim">{{ booking.borrower?.department ?? '—' }}</p>
                </div>
            </div>
        </td>

        <!-- 教室 -->
        <td class="px-5 py-3">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-a-badge px-2.5 py-1.5 text-xs font-semibold tracking-wide text-a-text-2">
                {{ booking.classroom?.code }}
            </span>
        </td>

        <!-- 日期 -->
        <td class="whitespace-nowrap px-5 py-3">
            <p class="font-medium text-a-text-2">{{ booking.date_summary || booking.date }}</p>
            <p v-if="booking.is_multi_day" class="mt-1 flex items-center gap-1 text-[11px] text-primary">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-primary"></span>
                跨日申請
            </p>
        </td>

        <!-- 時段 -->
        <td class="px-5 py-3">
            <template v-if="booking.is_multi_day && booking.booking_dates?.length">
                <!-- 跨日明細：限制高度 + 捲動 -->
                <div class="max-h-[56px] space-y-1 overflow-y-auto pr-1 scrollbar-thin">
                    <div
                        v-for="item in booking.booking_dates"
                        :key="item.date"
                        class="flex items-baseline gap-2"
                    >
                        <span class="shrink-0 text-[11px] font-semibold text-a-text-muted">{{ item.date }}</span>
                        <span class="text-[11px] text-a-text-dim">{{ formatSlotsSummary(item.time_slots) }}</span>
                    </div>
                </div>
            </template>
            <div v-else class="flex flex-wrap gap-1.5">
                <span
                    v-for="slot in booking.time_slots"
                    :key="slot"
                    class="inline-block rounded-md bg-a-badge px-2 py-0.5 text-[11px] font-medium text-a-text-muted"
                >
                    {{ formatPeriodLabel(slot) }}
                </span>
            </div>
        </td>

        <!-- 申請時間 -->
        <td class="whitespace-nowrap px-5 py-3 text-xs text-a-text-dim">
            {{ booking.created_at }}
        </td>

        <!-- 操作 / 狀態 -->
        <td v-if="mode !== 'reviews'" class="px-5 py-3 text-center" @click.stop>
            <div class="flex items-center justify-center gap-2">
                <template v-if="mode === 'bookings'">
                    <template v-if="booking.status === 0">
                        <button
                            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-400 transition-colors hover:bg-emerald-500/20"
                            @click="emit('approve', booking.id)"
                        >
                            核准
                        </button>
                        <button
                            class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-400 transition-colors hover:bg-red-500/20"
                            @click="emit('reject', booking.id)"
                        >
                            拒絕
                        </button>
                    </template>
                    <StatusBadge v-else :status="booking.status" />
                </template>
                <StatusBadge v-else :status="booking.status" />
            </div>
        </td>
    </tr>
</template>

<script setup lang="ts">
import { computed, defineComponent, h } from 'vue';
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
    date_summary?: string;
    is_multi_day?: boolean;
    status: number;
    reason: string | null;
    teacher: string | null;
    created_at: string;
    borrower: BookingBorrower | null;
    classroom: BookingClassroom | null;
    time_slots: string[];
    booking_dates?: Array<{
        date: string;
        time_slots: string[];
    }>;
}

const props = defineProps<{
    booking: BookingTableItem;
    mode: 'bookings' | 'reviews' | 'records';
}>();

const emit = defineEmits(['open-preview', 'approve', 'reject']);

const { statusLabel, statusStyle } = useBookingStatus();

// 內聯狀態徽章元件，加上圓點指示器
const StatusBadge = defineComponent({
    props: { status: Number },
    setup(p) {
        return () =>
            h(
                'span',
                { class: ['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', statusStyle(p.status!)] },
                [
                    h('span', { class: 'h-1.5 w-1.5 rounded-full bg-current opacity-70' }),
                    statusLabel(p.status!),
                ],
            );
    },
});

const formatSlotsSummary = (slots: string[]) => {
    const labels = slots.map((slot) => formatPeriodLabel(slot));
    if (labels.length <= 3) return labels.join('、');
    return `${labels.slice(0, 3).join('、')} +${labels.length - 3}`;
};

const rowClass = computed(() => {
    if (props.mode === 'reviews') return 'bg-blue-500/5';
    if (props.mode === 'bookings' && props.booking.status === 0) return 'bg-blue-500/5';
    return '';
});
</script>