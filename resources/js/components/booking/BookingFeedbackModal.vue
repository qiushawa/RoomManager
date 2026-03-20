<template>
    <BaseModal :show="show" size="sm" @close="$emit('close')">
        <div class="border-b border-gray-100 px-6 py-4">
            <h3 class="text-lg font-bold" :class="titleClass">{{ title }}</h3>
        </div>

        <div class="px-6 py-5">
            <p class="text-sm leading-6 whitespace-pre-wrap text-gray-700">{{ message }}</p>
        </div>

        <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-6 py-4">
            <button
                type="button"
                class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                @click="$emit('close')"
            >
                我知道了
            </button>
        </div>
    </BaseModal>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { BaseModal } from '@/components/ui';

const props = withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        message: string;
        type?: 'success' | 'error' | 'warning';
    }>(),
    {
        type: 'success',
    },
);

defineEmits<{
    (e: 'close'): void;
}>();

const titleClass = computed(() => {
    if (props.type === 'error') return 'text-red-600';
    if (props.type === 'warning') return 'text-amber-600';
    return 'text-emerald-600';
});
</script>
