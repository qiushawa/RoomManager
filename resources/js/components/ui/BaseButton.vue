<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
        :class="[variantClasses, sizeClasses]"
    >
        <slot></slot>
    </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
        size?: 'sm' | 'md' | 'lg';
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
    }
);

const variantClasses = computed(() => {
    const variants = {
        primary:
            'bg-blue-600 text-white shadow-md hover:bg-blue-700 focus:ring-blue-500',
        secondary:
            'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-400',
        outline:
            'border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 focus:ring-gray-400',
        ghost:
            'text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:ring-gray-400',
        danger:
            'bg-red-600 text-white shadow-md hover:bg-red-700 focus:ring-red-500',
    };
    return variants[props.variant];
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-2.5 text-base',
    };
    return sizes[props.size];
});
</script>
