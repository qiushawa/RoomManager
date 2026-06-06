<template>
    <div class="relative flex-1 min-w-[200px] max-w-sm">
        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-a-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
            :value="modelValue"
            @input="handleInput"
            @keydown.enter="emit('enter')"
            type="text"
            :placeholder="placeholder"
            class="w-full rounded-lg border border-a-border-2 bg-a-input py-2 pl-9 pr-4 text-sm text-a-text-body placeholder-a-text-dim outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
        />
    </div>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'enter'): void;
}>();

const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};
</script>
