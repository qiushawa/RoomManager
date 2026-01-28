<template>
    <div
        class="hidden w-64 flex-col border-l border-gray-200 bg-white p-6 lg:flex"
    >
        <h3 class="mb-6 text-sm font-bold uppercase tracking-wider text-gray-400">
            申請進度
        </h3>

        <div class="flex flex-col">
            
            <div class="flex group">
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
                        :class="visualStep > 1 ? 'bg-[#4a90e2]' : 'bg-gray-100'"
                    ></div>
                </div>
                <div class="pb-8 pt-2">
                    <div
                        class="text-base font-bold transition-colors duration-300"
                        :class="getStepTextClass(1)"
                    >
                        選擇教室
                    </div>
                    <div class="mt-1 text-xs text-gray-500 break-words">
                        {{ targetRoom ? targetRoom.name : '尚未選擇' }}
                    </div>
                </div>
            </div>

            <div class="flex group">
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
                        :class="visualStep > 2 ? 'bg-[#4a90e2]' : 'bg-gray-100'"
                    ></div>
                </div>
                <div class="pb-8 pt-2">
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

            <div class="flex group">
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
                    <div class="mt-1 text-xs text-gray-400">
                        最後確認
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Room, Step } from '@/types';

const props = defineProps<{
    targetRoom: Room | null;
    currentStep: Step;
    selectedCount: number;
}>();

// 計算目前的步驟
const visualStep = computed(() => {
    if (!props.targetRoom) return 1;    // 還沒選教室 -> Step 1
    if (props.currentStep === 1) return 2; // 選了教室，正在選時段 -> Step 2
    return 3;                           // 進入填表單 -> Step 3
});

// 圓圈樣式邏輯
const getStepCircleClass = (step: number) => {
    if (visualStep.value === step) {
        return 'border-[#4a90e2] text-[#4a90e2] bg-white shadow-[0_0_0_4px_rgba(74,144,226,0.15)] z-10';
    }
    if (visualStep.value > step) {
        return 'border-[#4a90e2] bg-[#4a90e2] text-white z-10';
    }
    return 'border-gray-200 text-gray-300 bg-white';
};

const getStepTextClass = (step: number) => {
    if (visualStep.value === step) return 'text-[#4a90e2]';
    if (visualStep.value > step) return 'text-gray-800';
    return 'text-gray-400';
};
</script>