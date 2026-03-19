<template>
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click="emit('cancel')"
            >
                <div class="w-full max-w-sm rounded-xl border border-a-border-card bg-a-bg p-6 shadow-2xl" @click.stop>
                    <h3 class="text-base font-semibold text-a-text">{{ title }}</h3>
                    <p class="mt-2 text-sm text-a-text-2">{{ message }}</p>
                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-a-border-2 bg-a-surface-2 px-4 py-2 text-xs font-medium text-a-text-2 transition-colors hover:bg-a-surface-hover"
                            @click="emit('cancel')"
                        >
                            {{ cancelText }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-red-500 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-red-600"
                            @click="emit('confirm')"
                        >
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        message: string;
        confirmText?: string;
        cancelText?: string;
    }>(),
    {
        title: '確認刪除',
        confirmText: '確認刪除',
        cancelText: '取消',
    },
);

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();
</script>
