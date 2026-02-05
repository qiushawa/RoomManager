<template>
    <BaseModal :show="show" size="md" @close="$emit('close')">
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4"
        >
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        申請資料填寫
                    </h2>
                    <p class="text-md text-gray-500">
                        {{ targetRoom?.code }} - {{ targetRoom?.name }}
                    </p>
                </div>
            </div>
            <button
                @click="$emit('close')"
                class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
            >
                ✕
            </button>
        </div>

        <!-- Content -->
        <div class="max-h-[65vh] overflow-y-auto p-6">
            <!-- 借用資訊 -->
            <div class="mb-6 grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500">借用日期</label>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        {{ selectedSlots.length > 0 ? selectedSlots[0].date.split('-').join('/') : '-' }}
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500">已選時段</label>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        {{ selectedSlots.length }} 節
                    </div>
                </div>
            </div>

            <!-- 選取時段標籤 -->
            <div class="mb-6">
                <label class="mb-2 block text-xs font-bold text-gray-500">借用時段</label>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(slot, idx) in selectedSlots"
                        :key="idx"
                        class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700"
                    >
                        {{ slot.label }}
                    </span>
                </div>
            </div>

            <!-- 表單欄位 -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <FormInput
                    v-model="localForm.name"
                    label="姓名"
                    placeholder="完整姓名"
                    required
                />
                <FormInput
                    v-model="localForm.identity_code"
                    label="學號/員工編號"
                    placeholder="40123456"
                    required
                />
                <FormInput
                    v-model="localForm.email"
                    type="email"
                    label="Email"
                    placeholder="請輸入常用郵件"
                    required
                />
                <FormInput
                    v-model="localForm.phone"
                    type="tel"
                    label="電話"
                    placeholder="請輸入聯絡電話"
                    required
                />
                <FormInput
                    v-model="localForm.department"
                    label="科系"
                    placeholder="請輸入科系"
                />
                <FormInput
                    v-model="localForm.teacher"
                    label="指導老師"
                    placeholder="老師姓名"
                />
            </div>

            <div class="mt-4">
                <FormTextarea
                    v-model="localForm.reason"
                    label="借用事由"
                    placeholder="請填寫詳細事由..."
                    :rows="3"
                    required
                />
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-6 py-4">
            <p class="text-xs text-gray-400">
                <span class="text-red-500">*</span> 為必填欄位
            </p>
            <div class="flex gap-3">
                <button
                    @click="$emit('close')"
                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100"
                >
                    取消
                </button>
                <button
                    @click="handleSubmit"
                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-blue-700"
                >
                    送出申請
                </button>
            </div>
        </div>
    </BaseModal>
</template>

<script setup lang="ts">
/**
 * BookingFormModal - 借用申請表單彈窗
 *
 * 完整的申請表單彈窗，包含：
 * - 借用資訊摘要（日期、時段）
 * - 申請人資料欄位
 * - 送出/取消按鈕
 *
 * @emits close - 關閉彈窗
 * @emits submit - 送出表單
 */
import { BaseModal, FormInput, FormTextarea } from '@/components/ui';
import type { ApplicantForm, Room, SelectedSlot } from '@/types';
import { reactive, watch } from 'vue';

const props = defineProps<{
    /** 是否顯示彈窗 */
    show: boolean;
    /** 目標教室 */
    targetRoom: Room | null;
    /** 已選取的時段 */
    selectedSlots: SelectedSlot[];
    /** 表單資料 */
    form: ApplicantForm;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', form: ApplicantForm): void;
    (e: 'update:form', value: ApplicantForm): void;
}>();

const localForm = reactive({ ...props.form });

watch(
    () => props.form,
    (newVal) => {
        Object.assign(localForm, newVal);
    },
    { deep: true },
);

watch(
    localForm,
    (newVal) => {
        emit('update:form', newVal);
    },
    { deep: true },
);

const handleSubmit = () => {
    emit('submit', localForm);
};
</script>
