<template>

    <Head title="短期借用紀錄 | Admin" />
    <AdminLayout title="短期借用紀錄">
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
                empty-text="沒有符合條件的借用紀錄"
            >
                <template #rows>
                    <BookingTableRow
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        :booking="booking"
                        mode="records"
                        @open-preview="openPreview"
                    />
                </template>

                <template #footer>
                    <AdminPagination :pagination="bookings" />
                </template>
            </AdminDataTable>
        </div>

        <!-- 預覽 Modal (唯讀) -->
        <RequestPreviewModal
            :open="previewOpen"
            :request="previewBooking"
            :periods="periods"
            @close="closePreview"
        />
    </AdminLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { BORROWING_RECORD_STATUS_TABS, RECORD_TABLE_HEADERS } from '@/constants';
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

const statusTabs = BORROWING_RECORD_STATUS_TABS;
const tableHeaders = RECORD_TABLE_HEADERS;

const { searchInput, filterStatus, applyFilters, setStatusAndApply } = useTableFilters({
    route: '/admin/borrowing-records',
    initialSearch: props.filters.search,
    initialStatus: props.filters.status,
});

const { previewOpen, previewItem: previewBooking, openPreview, closePreview } = usePreviewModal<AdminBookingItem>();
</script>
