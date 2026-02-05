<template>
    <div class="space-y-1">
        <label
            v-if="label"
            :for="inputId"
            class="block text-xs font-bold text-gray-500"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <input
            :id="inputId"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            @input="handleInput"
            class="w-full rounded-lg border px-3 py-2 text-sm transition outline-none focus:ring-2"
            :class="[
                hasError
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-100'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-100',
                disabled ? 'cursor-not-allowed bg-gray-100' : 'bg-white',
            ]"
        />

        <p v-if="hasError && errorMessage" class="text-xs text-red-500">
            {{ errorMessage }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        label?: string;
        type?: 'text' | 'email' | 'password' | 'tel' | 'number';
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        hasError?: boolean;
        errorMessage?: string;
        id?: string;
    }>(),
    {
        type: 'text',
        required: false,
        disabled: false,
        hasError: false,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputId = computed(() => props.id || `input-${Math.random().toString(36).slice(2, 9)}`);

const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};
</script>
