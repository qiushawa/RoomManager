<template>

    <Head title="教室管理 | Admin" />
    <AdminLayout title="教室管理">
        <div class="admin-page-container">

            <!-- 新增教室表單 -->
            <div class="admin-panel p-5">
                <h3 class="text-sm font-semibold text-a-text mb-4">新增教室</h3>
                <form @submit.prevent="addRoom" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[120px] max-w-[180px]">
                        <label class="block text-xs text-a-text-muted mb-1">教室代號</label>
                        <input
                            v-model="createForm.code"
                            type="text"
                            maxlength="7"
                            class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition"
                        />
                        <p v-if="createForm.errors.code" class="mt-1 text-xs text-red-400">{{ createForm.errors.code }}</p>
                    </div>
                    <div class="flex-1 min-w-[150px] max-w-[250px]">
                        <label class="block text-xs text-a-text-muted mb-1">教室名稱</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            maxlength="25"
                            class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition"
                        />
                        <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-400">{{ createForm.errors.name }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition-colors disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        新增
                    </button>
                </form>
            </div>

            <div class="admin-filter-bar">
                <AdminSearchBar v-model="searchInput" placeholder="搜尋教室代號或名稱" @enter="applyFilters" />
                <AdminStatusTabs :tabs="statusTabs" :model-value="filterStatus" @select="setStatusAndApply" />

                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <span class="text-xs text-a-text-muted">已選 {{ selectedRoomIds.length }} 間</span>
                    <input
                        v-model="renameName"
                        type="text"
                        maxlength="25"
                        placeholder="輸入新教室名稱（單選）"
                        class="w-52 rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                    />
                    <button
                        type="button"
                        :disabled="!hasSelection || batchForm.processing"
                        @click="applyBatchAction('enable')"
                        class="rounded-md border border-emerald-500/25 bg-emerald-500/15 px-3 py-2 text-xs font-medium text-emerald-400 transition-colors hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        啟用所選
                    </button>
                    <button
                        type="button"
                        :disabled="!hasSelection || batchForm.processing"
                        @click="applyBatchAction('disable')"
                        class="rounded-md border border-warning/25 bg-warning/15 px-3 py-2 text-xs font-medium text-warning transition-colors hover:bg-warning/25 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        停用所選
                    </button>
                    <button
                        type="button"
                        :disabled="!canRename"
                        @click="applyBatchAction('rename')"
                        class="rounded-md border border-primary/30 bg-primary/15 px-3 py-2 text-xs font-medium text-primary transition-colors hover:bg-primary/25 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        套用名稱
                    </button>
                </div>
            </div>

            <p v-if="selectedRoomIds.length > 1" class="text-xs text-warning">教室名稱更改僅支援單選教室。</p>
            <p v-if="operationError" class="text-xs text-red-400">{{ operationError }}</p>
            <p v-if="batchForm.errors.name" class="text-xs text-red-400">{{ batchForm.errors.name }}</p>

            <!-- 教室列表 -->
            <div class="admin-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-a-border">
                                <th class="w-12 px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        class="h-4 w-4 rounded border-a-border-2 bg-a-input text-primary focus:ring-primary/40"
                                        @change="toggleSelectAll(($event.target as HTMLInputElement).checked)"
                                    />
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">教室代號</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">教室名稱</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-a-text-muted uppercase tracking-wider">借用次數</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-a-text-muted uppercase tracking-wider">狀態</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-a-divider">
                            <tr v-if="classrooms.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-a-text-dim">尚無教室資料</td>
                            </tr>
                            <tr v-for="room in classrooms" :key="room.id"
                                class="transition-colors"
                                :class="[
                                    !room.is_active ? 'opacity-50' : '',
                                    isSelected(room.id) ? 'bg-a-surface-active/60' : 'hover:bg-a-surface-hover'
                                ]"
                            >
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(room.id)"
                                        class="h-4 w-4 rounded border-a-border-2 bg-a-input text-primary focus:ring-primary/40"
                                        @change="toggleSelectRoom(room.id, ($event.target as HTMLInputElement).checked)"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md bg-a-badge px-2 py-1 text-xs font-medium text-a-text-2">
                                        {{ room.code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-a-text-body font-medium">{{ room.name }}</td>
                                <td class="px-4 py-3 text-center text-a-text-muted text-xs">{{ room.bookings_count }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                        room.is_active
                                            ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25'
                                            : 'bg-a-badge text-a-text-muted border border-a-border-2'
                                    ]">
                                        {{ room.is_active ? '啟用中' : '已停用' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AdminSearchBar, AdminStatusTabs } from '@/components/admin';
import { CLASSROOM_STATUS_TABS } from '@/constants';
import { useTableFilters } from '@/composables';
import { AdminLayout } from '@/layouts';
import type { AdminClassroomItem } from '@/types';

const props = defineProps<{
    classrooms: AdminClassroomItem[];
    filters: { status?: string; search?: string };
}>();

const statusTabs = CLASSROOM_STATUS_TABS;
const createForm = useForm({ code: '', name: '' });
const batchForm = useForm({
    action: 'enable',
    selected_ids: [] as number[],
    name: '',
});

const selectedRoomIds = ref<number[]>([]);
const renameName = ref('');

const { searchInput, filterStatus, applyFilters, setStatusAndApply } = useTableFilters({
    route: '/admin/rooms',
    initialSearch: props.filters.search,
    initialStatus: props.filters.status,
});

const selectedSet = computed(() => new Set(selectedRoomIds.value));
const hasSelection = computed(() => selectedRoomIds.value.length > 0);
const canRename = computed(() =>
    selectedRoomIds.value.length === 1 && renameName.value.trim().length > 0 && !batchForm.processing,
);
const operationError = computed(() => (batchForm.errors as Record<string, string | undefined>).operation);
const allSelected = computed(() =>
    props.classrooms.length > 0 && props.classrooms.every((room) => selectedSet.value.has(room.id)),
);

function addRoom() {
    createForm.post('/admin/rooms', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function isSelected(roomId: number) {
    return selectedSet.value.has(roomId);
}

function toggleSelectAll(checked: boolean) {
    selectedRoomIds.value = checked ? props.classrooms.map((room) => room.id) : [];
}

function toggleSelectRoom(roomId: number, checked: boolean) {
    if (checked) {
        if (!selectedSet.value.has(roomId)) {
            selectedRoomIds.value = [...selectedRoomIds.value, roomId];
        }

        return;
    }

    selectedRoomIds.value = selectedRoomIds.value.filter((id) => id !== roomId);
}

function applyBatchAction(action: 'enable' | 'disable' | 'rename') {
    if (!hasSelection.value) {
        return;
    }

    if (action === 'rename' && selectedRoomIds.value.length !== 1) {
        return;
    }

    batchForm.transform(() => ({
        action,
        selected_ids: selectedRoomIds.value,
        name: action === 'rename' ? renameName.value.trim() : undefined,
    })).patch('/admin/rooms/batch', {
        preserveScroll: true,
        onSuccess: () => {
            selectedRoomIds.value = [];
            if (action === 'rename') {
                renameName.value = '';
            }
        },
    });
}
</script>
