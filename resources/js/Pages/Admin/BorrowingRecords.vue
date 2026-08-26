<template>

    <Head title="短期借用紀錄 | Admin" />
    <AdminLayout title="短期借用紀錄">
        <div class="admin-page-container">

            <!-- 篩選列 -->
            <div class="admin-filter-bar">
                <AdminSearchBar v-model="searchInput" @enter="applyFilters" />
                <AdminStatusTabs :tabs="statusTabs" :model-value="filterStatus" @select="setStatusAndApply" />
            </div>

            <AdminDataTable
                :headers="tableHeaders"
                :is-empty="localBookings.data.length === 0"
                :col-span="6"
                empty-text="沒有符合條件的借用紀錄"
            >
                <template #rows>
                    <BookingTableRow
                        v-for="booking in localBookings.data"
                        :key="booking.id"
                        :booking="booking"
                        mode="records"
                        @open-preview="openPreview"
                    />
                </template>

                <template #footer>
                    <AdminPagination :pagination="localBookings" />
                </template>
            </AdminDataTable>
        </div>

        <!-- 預覽 Modal (唯讀) -->
        <RequestPreviewModal
            :open="previewOpen"
            :request="previewBooking"
            :periods="periods"
            @close="closePreview"
            @approve="(id) => { updateStatus(id, 1); closePreview(); }"
            @reject="(id) => { updateStatus(id, 2); closePreview(); }"
        />
    </AdminLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { AdminLayout } from '@/layouts';
import { BORROWING_RECORD_STATUS_TABS, RECORD_TABLE_HEADERS } from '@/constants';
import { usePreviewModal, useTableFilters } from '@/composables';
import { withBase } from '@/utils';
import {
    AdminDataTable,
    AdminPagination,
    AdminSearchBar,
    AdminStatusTabs,
    BookingTableRow,
    RequestPreviewModal,
} from '@/components/admin';
import type { AdminBookingItem, PaginatedData, Period } from '@/types';

const props = defineProps<{
    bookings: PaginatedData<AdminBookingItem>;
    filters: { status?: string; search?: string };
    periods: Period[];
}>();

const statusTabs = BORROWING_RECORD_STATUS_TABS;
const tableHeaders = RECORD_TABLE_HEADERS;

const { searchInput, filterStatus, applyFilters, setStatusAndApply } = useTableFilters({
    route: withBase('/admin/borrowing-records'),
    initialSearch: props.filters.search,
    initialStatus: props.filters.status,
});

const { previewOpen, previewItem: previewBooking, openPreview, closePreview } = usePreviewModal<AdminBookingItem>();

const localBookings = ref(props.bookings);

watch(
    () => props.bookings,
    (v) => {
        localBookings.value = v;
    },
    { deep: true },
);

const STATUS_INT_TO_ENUM: Record<number, string> = {
    0: 'pending',
    1: 'approved',
    2: 'rejected',
    3: 'cancelled',
};

function updateStatus(bookingId: number, status: number) {
    router.patch(withBase(`/admin/bookings/${bookingId}/status`), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            const items = (localBookings.value.data || []).map((b: AdminBookingItem) => {
                if (b.id === bookingId) {
                    return {
                        ...b,
                        status: status,
                        status_enum: STATUS_INT_TO_ENUM[status] ?? b.status_enum,
                    };
                }
                return b;
            });
            localBookings.value = { ...localBookings.value, data: items } as typeof localBookings.value;
        },
    });
}
</script>
