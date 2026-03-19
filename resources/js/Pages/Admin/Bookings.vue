<template>

    <Head title="短期借用管理 | Admin" />
    <AdminLayout title="短期借用管理">
        <div class="flex flex-col gap-6 max-w-[1600px] mx-auto">

            <!-- 篩選列 -->
            <div class="flex flex-wrap items-center gap-3">
                <AdminSearchBar v-model="searchInput" @enter="applyFilters" />
                <AdminStatusTabs :tabs="statusTabs" :model-value="filterStatus" @select="setStatusAndApply" />
            </div>

            <AdminDataTable
                :headers="tableHeaders"
                :is-empty="bookings.data.length === 0"
                :col-span="6"
                empty-text="沒有符合條件的預約紀錄"
            >
                <template #rows>
                    <BookingTableRow
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        :booking="booking"
                        mode="bookings"
                        @open-preview="openPreview"
                        @approve="(id) => updateStatus(id, 1)"
                        @reject="(id) => updateStatus(id, 2)"
                    />
                </template>

                <template #footer>
                    <AdminPagination :pagination="bookings" />
                </template>
            </AdminDataTable>
        </div>

        <!-- 預覽 Modal -->
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
import AdminLayout from '@/layouts/AdminLayout.vue';
import { BOOKING_STATUS_TABS, BOOKING_TABLE_HEADERS } from '@/constants';
import { usePreviewModal, useTableFilters } from '@/composables';
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

const statusTabs = BOOKING_STATUS_TABS;
const tableHeaders = BOOKING_TABLE_HEADERS;

const { searchInput, filterStatus, applyFilters, setStatusAndApply } = useTableFilters({
    route: '/admin/bookings',
    initialSearch: props.filters.search,
    initialStatus: props.filters.status,
});

const { previewOpen, previewItem: previewBooking, openPreview, closePreview } = usePreviewModal<AdminBookingItem>();

function updateStatus(bookingId: number, status: number) {
    router.patch(`/admin/bookings/${bookingId}/status`, { status }, {
        preserveScroll: true,
    });
}
</script>
