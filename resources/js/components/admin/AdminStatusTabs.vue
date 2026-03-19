<template>
    <div class="flex items-center gap-1 rounded-lg border border-a-border-2 bg-a-input p-1">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            @click="selectTab(tab.value)"
            :class="[
                'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                modelValue === tab.value
                    ? 'bg-a-surface-active text-a-text shadow-sm'
                    : 'text-a-text-muted hover:text-a-text-body',
            ]"
        >
            {{ tab.label }}
        </button>
    </div>
</template>

<script setup lang="ts">
import type { StatusTabOption } from '@/constants';

defineProps<{
    tabs: StatusTabOption[];
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'select', value: string): void;
}>();

const selectTab = (value: string) => {
    emit('update:modelValue', value);
    emit('select', value);
};
</script>
