<template>
    <Teleport to="body">
        <Transition name="modal" appear>
            <div
                v-if="show"
                :data-admin-theme="resolvedTheme"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <!-- Backdrop -->
                <Transition name="backdrop" appear>
                    <div
                        v-if="show"
                        class="absolute inset-0"
                        :class="isDarkTheme ? 'bg-black/50' : 'bg-slate-900/30'"
                        @click="$emit('close')"
                    ></div>
                </Transition>

                <!-- Modal -->
                <Transition name="dialog" appear>
                    <div
                        v-if="show"
                        class="relative z-10 w-full overflow-hidden rounded-2xl border border-a-border-card bg-a-bg text-a-text shadow-2xl will-change-transform"
                        :class="sizeClass"
                    >
                        <slot></slot>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAdminTheme } from '@/composables';
import { withBase } from '@/utils';

const props = withDefaults(
    defineProps<{
        show: boolean;
        size?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        size: 'md',
    }
);

defineEmits<{
    (e: 'close'): void;
}>();

const { theme, isDark } = useAdminTheme();

const isAdminRoute = computed(() => {
    if (typeof window === 'undefined') return false;
    return window.location.pathname.startsWith(withBase('/admin'));
});

const resolvedTheme = computed<'dark' | 'light'>(() => (isAdminRoute.value ? theme.value : 'light'));
const isDarkTheme = computed(() => isAdminRoute.value && isDark.value);

const sizeClass = computed(() => {
    const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-2xl',
        lg: 'max-w-4xl',
        xl: 'max-w-[720px]',
    };
    return sizes[props.size];
});
</script>
