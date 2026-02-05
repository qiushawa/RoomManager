<template>
    <aside
        class="relative shrink-0 flex-col border-l border-gray-200"
        :class="[panelWidthClass, visibilityClass, panelBgClass]"
    >
        <!-- Header（可選） -->
        <div
            v-if="$slots.header"
            class="flex h-[88px] shrink-0 items-center border-b border-gray-200 px-4"
            :class="headerClass"
        >
            <slot name="header" />
        </div>

        <!-- 主內容區 -->
        <div class="flex-1 overflow-y-auto" :class="contentClass">
            <slot />
        </div>

        <!-- Footer（可選） -->
        <div
            v-if="$slots.footer"
            class="h-[88px] shrink-0 border-t border-gray-200 p-4"
            :class="footerClass"
        >
            <slot name="footer" />
        </div>
    </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue';

export interface PanelBaseProps {
    /**
     * 寬度預設值
     * - 'narrow': w-44 xl:w-56
     * - 'medium': w-56 xl:w-72
     * - 'wide': w-72 xl:w-96
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
     * 背景樣式
     * - 'white': 純白色
     * - 'gradient': 漸層背景
     * - 自定義 class 字串
     */
    background?: 'white' | 'gradient' | string;

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

const props = withDefaults(defineProps<PanelBaseProps>(), {
    width: 'narrow',
    visibility: 'lg',
    background: 'gradient',
    headerClass: 'bg-white',
    contentClass: 'p-5',
    footerClass: 'bg-white',
});

const panelWidthClass = computed(() => {
    const widthMap: Record<string, string> = {
        narrow: 'w-44 xl:w-56',
        medium: 'w-56 xl:w-72',
        wide: 'w-72 xl:w-96',
    };
    return widthMap[props.width] || props.width;
});

const visibilityClass = computed(() => {
    const visibilityMap: Record<string, string> = {
        always: 'flex h-dvh',
        md: 'hidden md:flex h-dvh',
        lg: 'hidden lg:flex h-dvh',
    };
    return visibilityMap[props.visibility] || 'flex h-dvh';
});

const panelBgClass = computed(() => {
    const bgMap: Record<string, string> = {
        white: 'bg-white',
        gradient: 'bg-gradient-to-b from-slate-50 to-white',
    };
    return bgMap[props.background] || props.background;
});
</script>
