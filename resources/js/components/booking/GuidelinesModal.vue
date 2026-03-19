<template>
    <BaseModal :show="show" size="xl" @close="$emit('close')">
        <!-- Header -->
        <div
            class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800">
                    借用須知
                </h2>
                <p class="text-xs text-gray-500">
                    請詳閱以下規定後再進行申請
                </p>
            </div>
        </div>
        <div class="max-h-[70vh] overflow-y-auto p-6">
            <ul class="space-y-5 text-base leading-relaxed text-gray-700">
                <li class="flex gap-3">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">1</span>
                    <span>填寫借用表單之前，請先確認教室是否空堂。</span>
                </li>
                <li class="flex gap-3">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">2</span>
                    <span><strong class="text-red-600">本系師生優先</strong>借用，以<strong class="text-red-600">教學使用</strong>為第一優先順序。</span>
                </li>
                <li class="flex gap-3">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">3</span>
                    <span>
                        <strong class="text-red-600">平日借用 </strong> 請於 5 個工作天前登記，<strong class="text-red-600">假日借用</strong>請於
                        3 個工作天前登記，並請自行查詢是否審核通過。核可後請借用人自行於系辦(上班時間)登記借用鑰匙，需抵押有照片的證件。
                    </span>
                </li>
                <li class="flex gap-3">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">4</span>
                    <span>
                        借用空間使用完畢，借用人需負責電器設備電源及門窗關閉、清潔等工作，若發現有未關閉電源或環境髒亂之情況，<strong
                            class="text-red-600">當學期不得再借用本系教室</strong>。
                    </span>
                </li>
                <li class="flex gap-3">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">5</span>
                    <span>系辦保有審核及撤銷使用的權利。</span>
                </li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
            <button @click="$emit('close')"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100">
                取消
            </button>
            <button @click="confirm"
                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-blue-700">
                確認並繼續
            </button>
        </div>
    </BaseModal>
</template>

<script setup lang="ts">
/**
 * GuidelinesModal - 借用須知彈窗
 *
 * 顯示教室借用規定與注意事項。
 * 使用者必須確認後才能繼續填寫表單。
 *
 * @emits close - 關閉彈窗
 * @emits confirm - 確認已閱讀須知
 */
import { BaseModal } from '@/components/ui';

defineProps<{
    /** 是否顯示彈窗 */
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();

const confirm = () => {
    emit('confirm');
};
</script>
