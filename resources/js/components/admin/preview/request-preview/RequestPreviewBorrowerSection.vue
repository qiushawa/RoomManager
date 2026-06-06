<template>
    <div class="border-b border-a-divider px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-a-surface-2 text-sm font-semibold text-a-text-2">
                {{ request.borrower?.name?.charAt(0) }}
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="text-base font-semibold text-a-text">{{ request.borrower?.name }}</span>
                    <span class="text-[13px] text-a-text-muted">{{ request.borrower?.identity_code }}</span>
                </div>
                <div class="mt-0.5 flex items-center gap-3 text-[13px] text-a-text-muted">
                    <span v-if="request.borrower?.phone">{{ request.borrower.phone }}</span>
                    <span v-if="request.borrower?.email" class="max-w-[180px] truncate">{{ request.borrower.email }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AdminBookingItem } from '@/types';

defineProps<{
    request: AdminBookingItem;
}>();
</script>
