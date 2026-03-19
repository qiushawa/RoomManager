<template>
    <PanelBase width="narrow" visibility="lg" background="gradient">
        <!-- 選取摘要區 (default slot) -->
        <h4 class="mb-3 flex items-center gap-2 border-b border-gray-200 pb-2 text-sm font-bold text-gray-700">
            已選取時段
        </h4>

        <div v-if="selectedSlots.length === 0" class="py-8 text-center">
            <p class="text-sm text-gray-400">尚未選取任何時段</p>
            <p class="mt-1 text-xs text-gray-300">點擊左側表格選取</p>
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="(slot, index) in selectedSlots"
                :key="index"
                class="animate-fade-in flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50/50 px-3 py-2 text-sm"
            >
                <span
                    class="mr-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white"
                >
                    {{ index + 1 }}
                </span>
                <span class="text-gray-700">{{ slot.label }}</span>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div v-if="selectedSlots.length === 0" class="flex h-full items-center justify-center">
                <p class="text-sm text-gray-400">選取時段後可進行下一步</p>
            </div>

            <div v-else class="flex h-full flex-col justify-center gap-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">已選取</span>
                    <span class="font-bold text-blue-600">{{ selectedSlots.length }} 節課</span>
                </div>
                <button
                    @click="$emit('next-step')"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:bg-blue-600 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-[0.98]"
                >
                    填寫申請表單
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </template>
    </PanelBase>
</template>

<script setup lang="ts">
/**
 * SelectionSummaryPanel - 選取摘要面板
 *
 * 右側邊欄元件，顯示使用者已選取的時段清單與摘要資訊。
 * 提供「下一步」按鈕以進入表單填寫階段。
 *
 * @emits next-step - 當使用者點擊「填寫申請表單」時觸發
 */
import { PanelBase } from '@/layouts';
import type { Room, SelectedSlot } from '@/types';

defineProps<{
    /** 目前選中的教室資料 */
    room: Room;
    /** 已選取的時段清單 */
    selectedSlots: SelectedSlot[];
}>();

defineEmits<{
    (e: 'next-step'): void;
}>();
</script>
