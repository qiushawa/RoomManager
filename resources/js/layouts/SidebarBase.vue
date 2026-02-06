<template>
    <aside
        class="sticky top-0 flex h-auto w-full shrink-0 flex-col overflow-hidden md:h-dvh"
        :class="[sidebarWidthClass, visibilityClass]"
    >
        <!-- 背景層 -->
        <div v-if="hasBackground" class="absolute inset-0">
            <slot name="background">
                <img
                    v-if="backgroundImage"
                    :src="backgroundImage"
                    alt=""
                    class="h-full w-full object-cover"
                />
                <div
                    v-if="backgroundOverlay"
                    class="absolute inset-0"
                    :class="backgroundOverlay"
                />
            </slot>
        </div>

        <!-- Header -->
        <div
            v-if="$slots.header"
            class="relative z-10 flex h-[88px] shrink-0 items-center border-b px-4"
            :class="headerClass"
        >
            <slot name="header" />
        </div>

        <!-- 主內容區 -->
        <div class="relative z-10 flex-1 overflow-y-auto" :class="contentClass">
            <slot />
        </div>

        <!-- Footer -->
        <div
            v-if="$slots.footer"
            class="relative z-10 flex h-[88px] shrink-0 items-center justify-center border-t px-3"
            :class="footerClass"
        >
            <slot name="footer" />
        </div>
    </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue';

export interface SidebarBaseProps {
    /**
     * 寬度預設值：使用響應式斷點
     * - 'narrow': md:w-44 lg:w-52 xl:w-60 2xl:w-72
     * - 'medium': w-56 xl:w-64
     * - 'wide': w-64 xl:w-80
     * - 自定義 class 字串
     */
    width?: 'narrow' | 'medium' | 'wide' | string;

    /**
     * 可見性控制
     * - 'always': 始終顯示
     * - 'md': md 以上顯示
     * - 'lg': lg 以上顯示
     */
    visibility?: 'always' | 'md' | 'lg';

    /**
     * 背景圖片 URL
     */
    backgroundImage?: string;

    /**
     * 背景疊加層 class（如漸層）
     */
    backgroundOverlay?: string;

    /**
     * Header 區塊額外 class
     */
    headerClass?: string;

    /**
     * 內容區塊額外 class
     */
    contentClass?: string;

    /**
     * Footer 區塊額外 class
     */
    footerClass?: string;
}

const props = withDefaults(defineProps<SidebarBaseProps>(), {
    width: 'narrow',
    visibility: 'md',
    backgroundImage: undefined,
    backgroundOverlay: undefined,
    headerClass: 'border-white/10 bg-black/20 backdrop-blur-sm',
    contentClass: 'p-2 pt-3 md:p-4',
    footerClass: 'border-white/10 bg-black/30 backdrop-blur-sm',
});

const hasBackground = computed(
    () => props.backgroundImage || props.backgroundOverlay,
);

const sidebarWidthClass = computed(() => {
    const widthMap: Record<string, string> = {
        narrow: 'md:w-44 lg:w-52 xl:w-60 2xl:w-72',
        medium: 'w-56 xl:w-64',
        wide: 'w-64 xl:w-80',
    };
    return widthMap[props.width] || props.width;
});

const visibilityClass = computed(() => {
    const visibilityMap: Record<string, string> = {
        always: 'flex',
        md: 'hidden md:flex',
        lg: 'hidden lg:flex',
    };
    return visibilityMap[props.visibility] || 'flex';
});
</script>
