<template>
    <div class="bg-a-surface-2 rounded-xl border border-a-border-2 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-a-text-2">{{ title }}</p>
                <div v-if="isLoading" class="mt-2 h-9 w-24 overflow-hidden rounded-md bg-a-surface-active/30 relative">
                    <div
                        class="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-slate-500/20 to-transparent">
                    </div>
                </div>
                <p v-else class="text-3xl font-bold text-a-text">{{ value }}</p>
            </div>

            <div :class="['p-3 rounded-lg border', theme.bg, theme.border]">
                <slot name="icon">
                    <svg :class="['h-6 w-6', theme.text]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </slot>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    title: string;
    value?: string | number | null;
    color?: 'primary' | 'info' | 'success' | 'warning';
}>(), {
    color: 'primary',
});

const isLoading = computed(() => {
    return props.value === undefined || props.value === null;
});

const theme = computed(() => {
    const themeMap = {
        primary: { bg: 'bg-primary/20', border: 'border-primary/30', text: 'text-primary-light' },
        info: { bg: 'bg-info/20', border: 'border-info/30', text: 'text-info-light' },
        success: { bg: 'bg-success/20', border: 'border-success/30', text: 'text-success-light' },
        warning: { bg: 'bg-warning/20', border: 'border-warning/30', text: 'text-warning-light' },
    };
    return themeMap[props.color] || themeMap.primary;
});
</script>
