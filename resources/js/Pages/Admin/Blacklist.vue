<template>
    <Head title="黑名單管理 | Admin" />
    <AdminLayout title="黑名單管理">
        <div class="admin-page-container">
            <p
                v-if="$page.props.flash?.success"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-sm text-emerald-400"
            >
                {{ $page.props.flash.success }}
            </p>

            <div class="flex gap-1 border-b border-a-border-2">
                <button
                    type="button"
                    class="-mb-px border-b-2 px-5 py-2.5 text-sm font-medium transition-colors"
                    :class="activeView === 'manage'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeView = 'manage'"
                >
                    黑名單管理
                </button>
                <button
                    type="button"
                    class="-mb-px border-b-2 px-5 py-2.5 text-sm font-medium transition-colors"
                    :class="activeView === 'records'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeView = 'records'"
                >
                    黑名單紀錄
                </button>
            </div>

            <section v-if="activeView === 'manage'" class="admin-panel p-5">
                <h3 class="mb-4 text-sm font-semibold text-a-text">新增黑名單</h3>

                <form class="space-y-4" @submit.prevent="submitBlacklist">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">學號</label>
                            <input
                                v-model.trim="form.identity_code"
                                type="text"
                                placeholder="請輸入借用人學號"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                            <p v-if="form.errors.identity_code" class="mt-1 text-xs text-red-400">{{ form.errors.identity_code }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">停權結束時間</label>
                            <input
                                v-model="form.banned_until"
                                type="date"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                            <p class="mt-1 text-xs text-a-text-dim">預設為目前學期結束日</p>
                            <p v-if="form.errors.banned_until" class="mt-1 text-xs text-red-400">{{ form.errors.banned_until }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs text-a-text-muted">黑名單原因</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="reason in blacklistReasons"
                                :key="reason.id"
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition-colors"
                                :class="form.reason_ids.includes(reason.id)
                                    ? 'border-primary/40 bg-primary/10 text-primary'
                                    : 'border-a-border-2 bg-a-input text-a-text-body hover:border-a-border-card'"
                                @click="toggleReason(reason.id)"
                            >
                                {{ reason.reason }}
                            </button>
                        </div>
                        <p v-if="form.errors.reason_ids" class="mt-1 text-xs text-red-400">{{ form.errors.reason_ids }}</p>
                        <p v-if="form.errors['reason_ids.0']" class="mt-1 text-xs text-red-400">{{ form.errors['reason_ids.0'] }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
                        >
                            新增黑名單
                        </button>
                    </div>
                </form>
            </section>

            <section v-else class="space-y-0">
                <AdminDataTable
                    :headers="recordTableHeaders"
                    :is-empty="blacklists.data.length === 0"
                    :col-span="5"
                    empty-text="目前沒有黑名單資料"
                >
                    <template #rows>
                        <tr v-for="item in blacklists.data" :key="item.id" class="transition-colors hover:bg-a-surface-hover">
                            <td class="px-4 py-3 text-sm text-a-text-body">{{ item.borrower_identity_code }}</td>
                            <td class="px-4 py-3 text-sm text-a-text-body">{{ item.borrower_name || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-a-text-muted">{{ item.borrower_department || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-a-text-body">{{ item.banned_until || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-a-text-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="reason in item.reasons"
                                        :key="`${item.id}-${reason}`"
                                        class="rounded-md border border-a-divider bg-a-badge px-2 py-0.5 text-xs"
                                    >
                                        {{ reason }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template #footer>
                        <AdminPagination :pagination="blacklists" />
                    </template>
                </AdminDataTable>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { AdminDataTable, AdminPagination } from '@/components/admin';
import type { PaginatedData } from '@/types';

interface BlacklistReasonOption {
    id: number;
    reason: string;
}

interface BlacklistListItem {
    id: number;
    borrower_identity_code: string;
    borrower_name: string | null;
    borrower_department: string | null;
    banned_until: string | null;
    reasons: string[];
}

const props = defineProps<{
    blacklists: PaginatedData<BlacklistListItem>;
    blacklistReasons: BlacklistReasonOption[];
    defaultBannedUntil: string;
}>();

const activeView = ref<'manage' | 'records'>('manage');

const recordTableHeaders = [
    { label: '學號' },
    { label: '姓名' },
    { label: '系所' },
    { label: '停權至' },
    { label: '原因' },
];

const form = useForm<{
    identity_code: string;
    reason_ids: number[];
    banned_until: string;
}>({
    identity_code: '',
    reason_ids: [],
    banned_until: props.defaultBannedUntil,
});

const toggleReason = (reasonId: number) => {
    if (form.reason_ids.includes(reasonId)) {
        form.reason_ids = form.reason_ids.filter((id) => id !== reasonId);
        return;
    }
    form.reason_ids = [...form.reason_ids, reasonId];
};

const submitBlacklist = () => {
    form.post('/admin/users/blacklist', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('identity_code', 'reason_ids');
            form.banned_until = props.defaultBannedUntil;
        },
    });
};
</script>
