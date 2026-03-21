<template>

    <Head title="系統設定 | Admin" />
    <AdminLayout title="系統設定">
        <BaseModal :show="showRedirectNotice" size="sm" @close="showRedirectNotice = false">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-amber-600">需要先設定學期</h3>
            </div>
            <div class="px-6 py-5">
                <p class="text-sm leading-6 whitespace-pre-wrap text-gray-700">{{ redirectNoticeMessage }}</p>
            </div>
            <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                    @click="showRedirectNotice = false"
                >
                    我知道了
                </button>
            </div>
        </BaseModal>

        <div class="admin-page-container">
            <p
                v-if="$page.props.flash?.error"
                class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm text-red-400"
            >
                {{ $page.props.flash.error }}
            </p>

            <p
                v-if="$page.props.flash?.success"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-sm text-emerald-400"
            >
                {{ $page.props.flash.success }}
            </p>

            <div class="admin-panel p-5">
                <div class="mb-4 flex items-center justify-between gap-3 border-b border-a-divider pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-a-text">學期資料設定</h3>
                        <p class="mt-1 text-xs text-a-text-muted">需先建立目前或未來的學期資料，後台功能才可操作。</p>
                    </div>
                    <span class="rounded-md border border-a-border-2 bg-a-badge px-2.5 py-1 text-xs text-a-text-2">
                        目前學期：{{ currentSemester ?? '尚未設定' }}
                    </span>
                </div>

                <form class="space-y-4" @submit.prevent="submitSemester">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">學年</label>
                            <input
                                v-model.number="form.academic_year"
                                type="number"
                                min="1"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">學期</label>
                            <select
                                v-model.number="form.semester"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            >
                                <option :value="1">上學期</option>
                                <option :value="2">下學期</option>
                            </select>
                            <p v-if="form.errors.semester" class="mt-1 text-xs text-red-400">{{ form.errors.semester }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">開始日期</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                            <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-400">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">結束日期</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                            <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-400">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
                        >
                            新增學期
                        </button>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto rounded-lg border border-a-border-2">
                    <table class="w-full text-sm">
                        <thead class="border-b border-a-border bg-a-surface-hover">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-a-text-muted">學期</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-a-text-muted">開始日期</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-a-text-muted">結束日期</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-a-divider">
                            <tr v-if="semesters.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-a-text-dim">尚無學期資料</td>
                            </tr>
                            <tr v-for="semester in semesters" :key="semester.id" class="hover:bg-a-surface-hover/40">
                                <td class="px-4 py-2.5 text-a-text-body">{{ semester.display_name }}</td>
                                <td class="px-4 py-2.5 text-a-text-muted">{{ semester.start_date }}</td>
                                <td class="px-4 py-2.5 text-a-text-muted">{{ semester.end_date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { BaseModal } from '@/components';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface SemesterItem {
    id: number;
    display_name: string;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    currentSemester: string | null;
    semesters: SemesterItem[];
}>();

const page = usePage();
const showRedirectNotice = ref(false);
const redirectNoticeMessage = ref('');

watch(
    () => page.props.flash?.error,
    (errorMessage) => {
        if (!errorMessage || typeof errorMessage !== 'string') return;
        redirectNoticeMessage.value = errorMessage;
        showRedirectNotice.value = true;
    },
    { immediate: true },
);

const currentYear = new Date().getFullYear() - 1911;

const form = useForm<{
    academic_year: number;
    semester: 1 | 2;
    start_date: string;
    end_date: string;
}>({
    academic_year: currentYear,
    semester: 1,
    start_date: '',
    end_date: '',
});

const submitSemester = () => {
    form.post('/admin/settings/semesters', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('start_date', 'end_date');
        },
    });
};
</script>
