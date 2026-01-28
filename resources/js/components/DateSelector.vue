<template>
    <div class="relative">
        <button
            type="button"
            @click="openCalendar"
            class="group flex min-w-[160px] items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-1.5 shadow-sm transition-all duration-200 hover:border-blue-400 hover:bg-blue-50 hover:shadow-md active:scale-95"
            title="點擊切換日期"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-gray-400 transition-colors group-hover:text-blue-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>

            <span
                class="flex-1 text-center text-sm font-bold text-gray-700 transition-colors group-hover:text-blue-700"
            >
                {{ formattedDate }}
            </span>
            
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-300 group-hover:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <input
            ref="dateInput"
            type="date"
            :value="dateString"
            @input="handleInput"
            class="absolute bottom-0 left-0 h-0 w-0 opacity-0 pointer-events-none"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

defineProps<{
    formattedDate: string;  // 顯示用
    dateString: string;     // Input value 用
}>();

const emit = defineEmits<{
    (e: 'update', value: string): void;
}>();

const dateInput = ref<HTMLInputElement | null>(null);

// 觸發日曆
const openCalendar = () => {
    try {
        dateInput.value?.showPicker();
    } catch (err) {
        dateInput.value?.click();
    }
};

const handleInput = (e: Event) => {
    const val = (e.target as HTMLInputElement).value;
    if (val) {
        emit('update', val);
    }
};
</script>