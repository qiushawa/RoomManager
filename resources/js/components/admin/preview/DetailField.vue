<template>
    <div>
        <p class="text-xs text-a-text-dim mb-0.5">{{ label }}</p>
        <p class="text-sm text-a-text-body">{{ value }}</p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    label: string;
    value: string;
}>();
</script>
