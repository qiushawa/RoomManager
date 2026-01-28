<template>
    <div class="z-20 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <div
            class="mx-auto flex max-w-full flex-col items-center justify-between gap-4 md:flex-row md:gap-0"
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

                    <DateSelector
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

                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-[#6c8ebf]"
                        ></span>
                        已選擇
                    </span>
                    <span class="flex items-center gap-1">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-[#5cb85c]"
                        ></span>
                        已借用
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

            <div
                class="flex w-full items-center justify-between gap-4 border-t border-gray-100 pt-3 md:w-auto md:justify-end md:border-t-0 md:pt-0"
            >
                <div class="text-sm whitespace-nowrap text-gray-600">
                    <span v-if="currentStep === 1 && selectedCount === 0"
                        >請勾選時段</span
                    >
                    <span v-else-if="currentStep === 1"
                        >已選
                        <strong class="text-blue-600">{{
                            selectedCount
                        }}</strong>
                        節</span
                    >
                </div>

                <div class="flex gap-3">
                    <template v-if="currentStep === 1">
                        <button
                            @click="$emit('next-step')"
                            :disabled="selectedCount === 0"
                            class="flex items-center gap-2 rounded px-6 py-2 font-bold text-white transition-all duration-200"
                            :class="
                                selectedCount > 0
                                    ? 'bg-[#4a90e2] shadow-md hover:bg-blue-600'
                                    : 'cursor-not-allowed bg-gray-300'
                            "
                        >
                            下一步
                            <span v-if="selectedCount > 0"
                                >({{ selectedCount }})</span
                            >
                        </button>
                    </template>

                    <template v-else>
                        <button
                            @click="$emit('prev-step')"
                            class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-800"
                        >
                            回上一步
                        </button>
                        <button
                            @click="$emit('submit')"
                            class="rounded bg-[#5cb85c] px-6 py-2 font-bold text-white shadow-md hover:bg-green-600"
                        >
                            送出申請
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import DateSelector from '@/components/DateSelector.vue';
import type { Step } from '@/types';

withDefaults(
    defineProps<{
        currentStep: Step;
        selectedCount?: number;
        formattedDate?: string;
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
    (e: 'next-step'): void;
    (e: 'prev-step'): void;
    (e: 'submit'): void;
    (e: 'update-date', date: string): void;
}>();
</script>
