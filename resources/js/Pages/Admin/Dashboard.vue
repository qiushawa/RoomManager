<template>
    <AdminLayout>
        <template #header>{{ meta.title }}</template>
        <div class="mb-6 rounded-lg bg-white p-6">
            <!-- defer 未載入 -->
            <div v-if="!defer" class="animate-pulse text-gray-400">
                載入中...
            </div>
            <!-- defer 已載入 -->
            <div v-else class="text-gray-800">
                <h2 class="mb-2 text-lg font-bold">Defer 資料：</h2>
                <p>{{ defer }}</p>
            </div>

            <!-- lazy 未載入 -->
            <div v-if="!lazy" class="animate-pulse text-gray-400 mt-4">
                Lazy 資料尚未載入，請點擊下方按鈕。
                <button @click="loadLazyData" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">載入 Lazy 資料</button>
            </div>
            <!-- lazy 已載入 -->
            <div v-else class="text-gray-800 mt-4">
                <h2 class="mb-2 text-lg font-bold">Lazy 資料：</h2>
                <p>{{ lazy }}</p>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';

defineProps<{
    meta: {
        title: string;
    };
    defer?: string;
    lazy?: string;
}>();

const loadLazyData = () => {
    router.reload({
        only: ['lazy'],
    });
};
</script>
