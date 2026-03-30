<template>
    <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-a-border px-4 py-3">
        <p class="text-xs text-a-text-dim">共 {{ pagination.total }} 筆，第 {{ pagination.current_page }} / {{ pagination.last_page }} 頁</p>
        <div class="flex gap-1">
            <Link
                v-for="link in pagination.links"
                :key="link.label"
                :href="link.url ?? ''"
                :class="[
                    'rounded-md px-3 py-1 text-xs transition-colors',
                    link.active
                        ? 'bg-primary/40 text-a-text font-medium'
                        : link.url
                            ? 'text-a-text-muted hover:bg-a-surface-hover hover:text-a-text-body'
                            : 'cursor-not-allowed text-a-text-dim',
                ]"
                preserve-state
                preserve-scroll
            >
                <span v-html="link.label"></span>
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginatedData } from '@/types';

defineProps<{
    pagination: Pick<PaginatedData<unknown>, 'current_page' | 'last_page' | 'total' | 'links'>;
}>();
</script>
