<template>

    <Head title="審核列表 | Admin" />
    <AdminLayout title="審核列表">
        <div class="admin-page-container">

            <!-- 篩選列 -->
            <div class="admin-filter-bar">
                <AdminSearchBar v-model="searchInput" @enter="applyFilters" />
                <span class="text-xs text-a-text-muted">共 {{ bookings.total }} 筆待審核</span>
            </div>

            <AdminDataTable
                :headers="tableHeaders"
                :is-empty="bookings.data.length === 0"
                :col-span="5"
                empty-text="目前沒有待審核的申請"
            >
                <template #rows>
                    <BookingTableRow
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        :booking="booking"
                        mode="reviews"
                        @open-preview="openPreview"
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
import { REVIEW_TABLE_HEADERS } from '@/constants';
import { usePreviewModal, useTableFilters } from '@/composables';
import {
    AdminDataTable,
    AdminPagination,
    AdminSearchBar,
    BookingTableRow,
    RequestPreviewModal,
} from '@/components/admin';
import type { AdminBookingItem, PaginatedData, Period } from '@/types';

const props = defineProps<{
    bookings: PaginatedData<AdminBookingItem>;
    filters: { search?: string };
    periods: Period[];
}>();

const tableHeaders = REVIEW_TABLE_HEADERS.filter((header) => header.label !== '操作');

const { searchInput, applyFilters } = useTableFilters({
    route: '/admin/reviews',
    initialSearch: props.filters.search,
    includeStatus: false,
});

const { previewOpen, previewItem: previewBooking, openPreview, closePreview } = usePreviewModal<AdminBookingItem>();

function updateStatus(bookingId: number, status: number) {
    router.patch(`/admin/bookings/${bookingId}/status`, { status }, {
        preserveScroll: true,
    });
}
</script>
