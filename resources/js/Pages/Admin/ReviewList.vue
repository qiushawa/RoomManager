<template>

    <Head title="審核列表 | Admin" />
    <AdminLayout title="審核列表">
        <div class="flex flex-col gap-6 max-w-[1600px] mx-auto">

            <!-- 篩選列 -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-a-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="searchInput"
                        @keydown.enter="applyFilters"
                        type="text"
                        placeholder="搜尋教室、借用人、事由..."
                        class="w-full rounded-lg border border-a-border-2 bg-a-input pl-9 pr-4 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition"
                    />
                </div>
                <span class="text-xs text-a-text-muted">共 {{ bookings.total }} 筆待審核</span>
            </div>

            <!-- 表格 -->
            <div class="bg-a-surface rounded-xl border border-a-border-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-a-border">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">申請人</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">教室</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">日期</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">時段</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">申請時間</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-a-text-muted uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-a-divider">
                            <tr v-if="bookings.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-a-text-dim">目前沒有待審核的申請</td>
                            </tr>
                            <tr v-for="booking in bookings.data" :key="booking.id"
                                class="hover:bg-a-surface-hover transition-colors cursor-pointer bg-blue-500/5"
                                @click="openPreview(booking)"
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
                                <td class="px-4 py-3 text-a-text-2 whitespace-nowrap">{{ booking.date }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="slot in booking.time_slots" :key="slot"
                                            class="inline-block rounded bg-a-badge px-1.5 py-0.5 text-[11px] text-a-text-muted">
                                            {{ formatPeriodLabel(slot) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-a-text-dim text-xs whitespace-nowrap">{{ booking.created_at }}</td>
                                <td class="px-4 py-3 text-center" @click.stop>
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="updateStatus(booking.id, 1)"
                                            class="rounded-md bg-emerald-500/15 border border-emerald-500/25 px-2.5 py-1 text-xs font-medium text-emerald-400 hover:bg-emerald-500/25 transition-colors">
                                            核准
                                        </button>
                                        <button @click="updateStatus(booking.id, 2)"
                                            class="rounded-md bg-red-500/15 border border-red-500/25 px-2.5 py-1 text-xs font-medium text-red-400 hover:bg-red-500/25 transition-colors">
                                            拒絕
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 分頁 -->
                <div v-if="bookings.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-a-border">
                    <p class="text-xs text-a-text-dim">共 {{ bookings.total }} 筆，第 {{ bookings.current_page }} / {{ bookings.last_page }} 頁</p>
                    <div class="flex gap-1">
                        <Link v-for="link in bookings.links" :key="link.label"
                            :href="link.url ?? ''"
                            :class="[
                                'px-3 py-1 text-xs rounded-md transition-colors',
                                link.active
                                    ? 'bg-primary/40 text-a-text font-medium'
                                    : link.url
                                        ? 'text-a-text-muted hover:bg-a-surface-hover hover:text-a-text-body'
                                        : 'text-a-text-dim cursor-not-allowed'
                            ]"
                            v-html="link.label"
                            preserve-state
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- 預覽 Modal -->
        <RequestPreviewModal
            :open="previewOpen"
            :request="previewBooking"
            :periods="periods"
            @close="previewOpen = false"
            @approve="(id) => { updateStatus(id, 1); previewOpen = false; }"
            @reject="(id) => { updateStatus(id, 2); previewOpen = false; }"
        />
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import RequestPreviewModal from '@/components/admin/RequestPreviewModal.vue';
import { formatPeriodLabel } from '@/utils';
import type { Period } from '@/types';

interface BookingBorrower {
    name: string;
    identity_code: string;
    department: string | null;
    email: string | null;
    phone: string | null;
}

interface BookingClassroom {
    code: string;
    name: string;
}

interface BookingItem {
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

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedBookings {
    data: BookingItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    bookings: PaginatedBookings;
    filters: { search?: string };
    periods: Period[];
}>();

const searchInput = ref(props.filters.search ?? '');
const previewOpen = ref(false);
const previewBooking = ref<BookingItem | null>(null);

function applyFilters() {
    router.get('/admin/reviews', {
        search: searchInput.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function updateStatus(bookingId: number, status: number) {
    router.patch(`/admin/bookings/${bookingId}/status`, { status }, {
        preserveScroll: true,
    });
}

function openPreview(booking: BookingItem) {
    previewBooking.value = booking;
    previewOpen.value = true;
}
</script>
