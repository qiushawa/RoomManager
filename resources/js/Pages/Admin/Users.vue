<template>

    <Head title="用戶管理 | Admin" />
    <AdminLayout title="用戶管理">
        <div
            class="bg-a-surface-2 rounded-xl border border-a-border-2 overflow-hidden min-h-[400px] flex items-center justify-center">
            <div class="text-center p-8">
                <svg class="mx-auto h-12 w-12 text-primary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-a-text">用戶管理模組</h3>
                <p class="mt-2 text-sm text-a-text-2">待開發：此處將實作帳號權限維護與使用者列表查詢。</p>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
