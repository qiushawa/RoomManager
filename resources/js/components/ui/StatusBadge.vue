<template>
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
        :class="variantClasses"
    >
        <slot></slot>
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info';
    }>(),
    {
        variant: 'default',
    }
);

const variantClasses = computed(() => {
    const variants = {
        default: 'border border-gray-200 bg-gray-50 text-gray-700',
        primary: 'border border-blue-200 bg-blue-50 text-blue-700',
        success: 'border border-green-200 bg-green-50 text-green-700',
        warning: 'border border-yellow-200 bg-yellow-50 text-yellow-700',
        danger: 'border border-red-200 bg-red-50 text-red-700',
        info: 'border border-cyan-200 bg-cyan-50 text-cyan-700',
    };
    return variants[props.variant];
});
</script>
