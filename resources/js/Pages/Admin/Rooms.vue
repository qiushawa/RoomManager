<template>

    <Head title="教室管理 | Admin" />
    <AdminLayout title="教室管理">
        <div
            class="bg-a-surface-2 rounded-xl border border-a-border-2 overflow-hidden min-h-[400px] flex items-center justify-center">
            <div class="text-center p-8">
                <svg class="mx-auto h-12 w-12 text-primary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2-2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-a-text">教室管理模組</h3>
                <p class="mt-2 text-sm text-a-text-2">待開發：此處可對教室詳細資訊、開放時段等進行修改與建檔。</p>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
