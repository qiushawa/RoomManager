<template>

    <Head title="教室管理 | Admin" />
    <AdminLayout title="教室管理">
        <div class="flex flex-col gap-6 max-w-[1200px] mx-auto">

            <!-- 新增教室表單 -->
            <div class="bg-a-surface rounded-xl border border-a-border-card p-5">
                <h3 class="text-sm font-semibold text-a-text mb-4">新增教室</h3>
                <form @submit.prevent="addRoom" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[120px] max-w-[180px]">
                        <label class="block text-xs text-a-text-muted mb-1">教室代號</label>
                        <input
                            v-model="form.code"
                            type="text"
                            maxlength="7"
                            placeholder="如 IB-501"
                            class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition"
                        />
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-400">{{ form.errors.code }}</p>
                    </div>
                    <div class="flex-1 min-w-[150px] max-w-[250px]">
                        <label class="block text-xs text-a-text-muted mb-1">教室名稱</label>
                        <input
                            v-model="form.name"
                            type="text"
                            maxlength="25"
                            placeholder="如 資訊大樓501教室"
                            class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition-colors disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        新增
                    </button>
                </form>
            </div>

            <!-- 教室列表 -->
            <div class="bg-a-surface rounded-xl border border-a-border-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-a-border">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">教室代號</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-a-text-muted uppercase tracking-wider">教室名稱</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-a-text-muted uppercase tracking-wider">借用次數</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-a-text-muted uppercase tracking-wider">狀態</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-a-text-muted uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-a-divider">
                            <tr v-if="classrooms.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-a-text-dim">尚無教室資料</td>
                            </tr>
                            <tr v-for="room in classrooms" :key="room.id"
                                class="hover:bg-a-surface-hover transition-colors"
                                :class="{ 'opacity-50': !room.is_active }"
                            >
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
                                            : 'bg-slate-500/15 text-slate-400 border border-slate-500/25'
                                    ]">
                                        {{ room.is_active ? '啟用中' : '已停用' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="toggleRoom(room)"
                                            :class="[
                                                'rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                                                room.is_active
                                                    ? 'bg-warning/15 border border-warning/25 text-warning hover:bg-warning/25'
                                                    : 'bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 hover:bg-emerald-500/25'
                                            ]"
                                        >
                                            {{ room.is_active ? '停用' : '啟用' }}
                                        </button>
                                        <button @click="confirmDelete(room)"
                                            :disabled="room.bookings_count > 0"
                                            :class="[
                                                'rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                                                room.bookings_count > 0
                                                    ? 'bg-slate-500/10 text-slate-500 border border-slate-500/20 cursor-not-allowed'
                                                    : 'bg-red-500/15 border border-red-500/25 text-red-400 hover:bg-red-500/25'
                                            ]"
                                            :title="room.bookings_count > 0 ? '有借用紀錄，無法刪除' : '刪除教室'"
                                        >
                                            刪除
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 刪除確認 Modal -->
        <teleport to="body">
            <transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150"
                leave-to-class="opacity-0"
            >
                <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click="deleteTarget = null">
                    <div class="w-full max-w-sm rounded-xl border border-a-border-card bg-a-bg p-6 shadow-2xl" @click.stop>
                        <h3 class="text-base font-semibold text-a-text">確認刪除</h3>
                        <p class="mt-2 text-sm text-a-text-2">
                            確定要刪除教室 <span class="font-medium text-a-text">{{ deleteTarget.code }}</span>（{{ deleteTarget.name }}）嗎？此操作無法復原。
                        </p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button @click="deleteTarget = null"
                                class="rounded-lg border border-a-border-2 bg-a-surface-2 px-4 py-2 text-xs font-medium text-a-text-2 hover:bg-a-surface-hover transition-colors">
                                取消
                            </button>
                            <button @click="doDelete"
                                class="rounded-lg bg-red-500 px-4 py-2 text-xs font-semibold text-white hover:bg-red-600 transition-colors">
                                確認刪除
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ClassroomItem {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    bookings_count: number;
}

defineProps<{
    classrooms: ClassroomItem[];
}>();

const form = useForm({ code: '', name: '' });
const deleteTarget = ref<ClassroomItem | null>(null);

function addRoom() {
    form.post('/admin/rooms', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function toggleRoom(room: ClassroomItem) {
    router.patch(`/admin/rooms/${room.id}/toggle`, {}, { preserveScroll: true });
}

function confirmDelete(room: ClassroomItem) {
    deleteTarget.value = room;
}

function doDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/admin/rooms/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
}
</script>
