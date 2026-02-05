<template>
    <div class="z-20 min-h-[88px] p-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] md:h-[88px] md:p-4">
        <div
            class="flex h-full w-full flex-col items-center justify-between gap-3 md:flex-row md:gap-0"
        >
            <div
                v-if="currentStep === 1"
                class="flex w-full flex-col items-center gap-4 md:w-auto md:flex-row"
            >
                <div class="flex items-center gap-2">
                    <button
                        @click="$emit('change-week', -1)"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition-colors hover:bg-gray-50 hover:text-blue-600 active:scale-95"
                        title="上一週"
                    >
                        <span class="text-xl leading-none">‹</span>
                    </button>

                    <DatePicker
                        :formatted-date="formattedDate"
                        :date-string="currentDateString"
                        @update="(val) => $emit('update-date', val)"
                    />

                    <button
                        @click="$emit('change-week', 1)"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition-colors hover:bg-gray-50 hover:text-blue-600 active:scale-95"
                        title="下一週"
                    >
                        <span class="text-xl leading-none">›</span>
                    </button>

                    <button
                        @click="$emit('reset-today')"
                        class="ml-2 rounded px-2 py-1 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-blue-600"
                    >
                        回今天
                    </button>
                </div>

                <div class="hidden flex-wrap items-center justify-center gap-2 text-xs text-gray-500 md:flex md:gap-3">
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-sm border border-gray-300 bg-success"
                        ></span>
                        選取中
                    </span>
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-warning"
                        ></span>
                        已借出
                    </span>
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-info"
                        ></span>
                        申請中
                    </span>
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-neutral"
                        ></span>
                        課程使用
                    </span>
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-sm border border-gray-300 bg-white"
                        ></span>
                        可申請
                    </span>
                </div>
            </div>

            <div v-else class="hidden md:block"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * ScheduleToolbar - 時段表工具列
 *
 * 底部工具列，包含：
 * - 週次切換按鈕
 * - 日期選擇器
 * - 時段狀態圖例
 *
 * @emits change-week - 切換週次，參數為 -1 或 1
 * @emits reset-today - 重設為今天
 * @emits update-date - 更新選取日期
 */
import { DatePicker } from '@/components/schedule';
import type { Step } from '@/types';

withDefaults(
    defineProps<{
        /** 當前步驟 */
        currentStep: Step;
        /** 已選取時段數量 */
        selectedCount?: number;
        /** 格式化後的日期顯示文字 */
        formattedDate?: string;
        /** 日期字串 (YYYY-MM-DD) */
        currentDateString?: string;
    }>(),
    {
        selectedCount: 0,
        formattedDate: '',
        currentDateString: '',
    },
);

defineEmits<{
    (e: 'change-week', offset: number): void;
    (e: 'reset-today'): void;
    (e: 'update-date', value: string): void;
    (e: 'next-step'): void;
    (e: 'prev-step'): void;
    (e: 'submit'): void;
}>();
</script>
