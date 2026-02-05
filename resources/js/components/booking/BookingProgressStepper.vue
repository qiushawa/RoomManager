<template>
    <div
        class="hidden w-64 flex-col border-l border-gray-200 bg-white p-6 lg:flex"
    >
        <h3
            class="mb-6 text-sm font-bold tracking-wider text-gray-400 uppercase"
        >
            申請進度
        </h3>

        <div class="flex flex-col">
            <div class="group flex">
                <div class="mr-4 flex flex-col items-center">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold transition-all duration-300"
                        :class="getStepCircleClass(1)"
                    >
                        <span v-if="visualStep > 1">✓</span>
                        <span v-else>1</span>
                    </div>
                    <div
                        class="h-16 w-0.5 transition-colors duration-500"
                        :class="visualStep > 1 ? 'bg-primary' : 'bg-gray-100'"
                    ></div>
                </div>
                <div class="pt-2 pb-8">
                    <div
                        class="text-base font-bold transition-colors duration-300"
                        :class="getStepTextClass(1)"
                    >
                        選擇教室
                    </div>
                    <div class="mt-1 text-xs break-words text-gray-500">
                        {{ targetRoom ? targetRoom.name : '尚未選擇' }}
                    </div>
                </div>
            </div>

            <div class="group flex">
                <div class="mr-4 flex flex-col items-center">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold transition-all duration-300"
                        :class="getStepCircleClass(2)"
                    >
                        <span v-if="visualStep > 2">✓</span>
                        <span v-else>2</span>
                    </div>
                    <div
                        class="h-16 w-0.5 transition-colors duration-500"
                        :class="visualStep > 2 ? 'bg-primary' : 'bg-gray-100'"
                    ></div>
                </div>
                <div class="pt-2 pb-8">
                    <div
                        class="text-base font-bold transition-colors duration-300"
                        :class="getStepTextClass(2)"
                    >
                        選擇時段
                    </div>
                    <div
                        class="mt-1 text-xs text-gray-500"
                        v-if="selectedCount > 0"
                    >
                        已選 {{ selectedCount }} 節
                    </div>
                    <div class="mt-1 text-xs text-gray-400" v-else>
                        請點擊時段
                    </div>
                </div>
            </div>

            <div class="group flex">
                <div class="mr-4 flex flex-col items-center">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold transition-all duration-300"
                        :class="getStepCircleClass(3)"
                    >
                        3
                    </div>
                </div>
                <div class="pt-2">
                    <div
                        class="text-base font-bold transition-colors duration-300"
                        :class="getStepTextClass(3)"
                    >
                        填寫表單
                    </div>
                    <div class="mt-1 text-xs text-gray-400">最後確認</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * BookingProgressStepper - 預約進度步驟器
 *
 * 顯示預約流程的三個步驟：選擇教室 → 選擇時段 → 填寫表單。
 * 根據當前狀態高亮對應步驟並顯示已完成的步驟。
 */
import type { Room, Step } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    /** 目前選中的教室，null 表示尚未選擇 */
    targetRoom: Room | null;
    /** 當前步驟 (1 或 2) */
    currentStep: Step;
    /** 已選取的時段數量 */
    selectedCount: number;
}>();

// 計算視覺上的步驟：有教室 = 完成步驟1
const visualStep = computed(() => {
    if (!props.targetRoom) return 1;
    if (props.currentStep === 1) return 2;
    return 3;
});

const getStepCircleClass = (step: number): string => {
    if (visualStep.value > step) {
        return 'bg-primary border-primary text-white';
    } else if (visualStep.value === step) {
        return 'border-primary text-primary';
    }
    return 'border-gray-200 text-gray-300';
};

const getStepTextClass = (step: number): string => {
    if (visualStep.value >= step) {
        return 'text-gray-800';
    }
    return 'text-gray-300';
};
</script>
