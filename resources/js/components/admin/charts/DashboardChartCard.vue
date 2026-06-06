<template>
    <div class="admin-panel flex h-80 flex-col gap-4 p-5">
        <div class="flex shrink-0 items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-4 w-1 rounded-full" :class="accentClass"></div>
                <h3 class="text-sm font-semibold text-a-text-body">{{ title }}</h3>
            </div>
            <span
                class="rounded-md border px-2.5 py-1 text-xs font-medium"
                :class="badgeClass"
            >
                {{ badgeText }}
            </span>
        </div>

        <div class="min-h-0 flex-1">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        badgeText: string;
        accentClass: string;
        badgeClass: string;
    }>(),
    {
        badgeText: '—',
    },
);
</script>
