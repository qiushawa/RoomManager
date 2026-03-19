<template>
    <div class="flex flex-col items-center justify-center gap-2 text-gray-300">
        <!-- 簡易骨架線條模擬圖表 -->
        <svg width="80" height="48" viewBox="0 0 80 48" fill="none" class="opacity-40">
            <rect x="4" y="28" width="12" height="16" rx="2" fill="currentColor" />
            <rect x="22" y="16" width="12" height="28" rx="2" fill="currentColor" />
            <rect x="40" y="8" width="12" height="36" rx="2" fill="currentColor" />
            <rect x="58" y="20" width="12" height="24" rx="2" fill="currentColor" />
        </svg>
        <span class="text-xs font-medium">{{ label }}</span>
    </div>
</template>

<script setup lang="ts">
defineProps<{ label?: string }>();
</script>
