<template>
    <div class="flex items-start justify-between border-b border-a-border px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold leading-snug text-a-text">預約申請審核</h3>
                <p class="mt-0.5 text-[13px] text-a-text-muted">申請編號 #{{ request.id }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-0.5">
            <button
                class="ml-1 rounded-lg p-1.5 text-a-text-muted transition-colors hover:bg-a-surface-2 hover:text-a-text"
                @click="$emit('close')"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AdminBookingItem } from '@/types';

defineProps<{
    request: AdminBookingItem;
}>();

defineEmits<{
    close: [];
}>();
</script>
