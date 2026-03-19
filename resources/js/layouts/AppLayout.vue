<template>
    <div
        class="flex h-dvh w-full flex-row overflow-hidden bg-slate-100 font-sans text-gray-700"
    >
        <!-- 左側邊欄插槽 -->
        <slot name="left-sidebar" />

        <!-- 主內容區 -->
        <main
            class="relative flex h-full flex-1 flex-col overflow-hidden bg-white shadow-xl"
        >
            <slot name="main" />
        </main>

        <!-- 右側邊欄插槽 -->
        <slot name="right-sidebar" />

        <!-- 額外插槽（如步驟指示器等） -->
        <slot name="extra" />
    </div>
</template>

<script setup lang="ts">
/**
 * AppLayout - 應用程式主佈局模板
 *
 * 提供三欄式佈局結構：
 * - left-sidebar: 左側邊欄（如教室選擇）
 * - main: 主內容區
 * - right-sidebar: 右側邊欄（如資訊面板）
 * - extra: 額外元素（如浮動指示器）
 *
 * 使用範例：
 * <AppLayout>
 *   <template #left-sidebar>
 *     <SidebarBase>...</SidebarBase>
 *   </template>
 *   <template #main>
 *     <MainContent />
 *   </template>
 *   <template #right-sidebar>
 *     <PanelBase>...</PanelBase>
 *   </template>
 * </AppLayout>
 */
</script>
