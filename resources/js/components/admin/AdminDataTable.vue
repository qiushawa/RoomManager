<template>
    <div class="admin-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-a-border">
                        <th
                            v-for="header in headers"
                            :key="header.label"
                            :class="[
                                'px-4 py-3 text-xs font-semibold uppercase tracking-wider text-a-text-muted',
                                header.align === 'center' ? 'text-center' : 'text-left',
                            ]"
                        >
                            {{ header.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-a-divider">
                    <tr v-if="isEmpty">
                        <td :colspan="colSpan" class="px-4 py-12 text-center text-a-text-dim">{{ emptyText }}</td>
                    </tr>
                    <slot v-else name="rows" />
                </tbody>
            </table>
        </div>

        <slot name="footer" />
    </div>
</template>

<script setup lang="ts">
import type { AdminDataTableHeader } from '@/types';

defineProps<{
    headers: AdminDataTableHeader[];
    isEmpty: boolean;
    colSpan: number;
    emptyText: string;
}>();
</script>
