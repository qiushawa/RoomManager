<template>
    <div class="flex h-dvh w-full bg-slate-100">
        <aside
            class="flex h-full flex-col bg-slate-800 text-white transition-all duration-300"
            :class="sidebarCollapsed ? 'w-16' : 'w-64'"
        >
            <div class="flex h-16 items-center justify-between border-b border-slate-700 px-4">
                <Link
                    href="/admin"
                    class="flex items-center gap-2 overflow-hidden"
                >
                    <span
                        v-if="!sidebarCollapsed"
                        class="whitespace-nowrap text-lg font-semibold"
                    >
                        RoomManager
                    </span>
                </Link>

            </div>

            <nav class="flex-1 overflow-y-auto p-3">
                <ul class="space-y-1">
                    <li v-for="item in menuItems" :key="item.name">
                        <Link
                            :href="item.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                            :class="isActive(item.href)
                                ? 'bg-primary text-white'
                                : 'text-slate-300 hover:bg-slate-700 hover:text-white'"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0" />
                            <span v-if="!sidebarCollapsed" class="truncate">{{ item.name }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="border-t border-slate-700 p-3">
                <Link
                    href="/"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
                >
                    <HomeIcon class="h-5 w-5 shrink-0" />
                    <span v-if="!sidebarCollapsed">返回前台</span>
                </Link>
            </div>
        </aside>

        <div class="flex flex-1 flex-col overflow-hidden">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
                <div class="flex items-center gap-4">
                                    <button
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="hidden lg:flex h-8 w-8 items-center justify-center rounded-lg hover:bg-slate-200 transition-colors"
                >
                <!-- 漢堡選單 -->
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="lg:hidden flex h-10 w-10 items-center justify-center rounded-lg hover:bg-gray-100"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">
                        <slot name="header">管理後台</slot>
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <button class="relative flex h-10 w-10 items-center justify-center rounded-lg hover:bg-gray-100 transition-colors">
                        <BellIcon class="h-5 w-5 text-gray-500" />
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500"></span>
                    </button>

                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary flex items-center justify-center text-white font-medium">
                            A
                        </div>
                        <span class="hidden sm:block text-sm font-medium text-gray-700">Admin</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-slate-50 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Home as DashboardIcon,
    Users as UsersIcon,
    Calendar as CalendarIcon,
    Building2 as BuildingIcon,
    Ban as BanIcon,
    Settings as SettingsIcon,
    Home as HomeIcon,
    Bell as BellIcon,
} from 'lucide-vue-next';

// 1. 定義狀態
const sidebarCollapsed = ref(false);

// 2. 掛載時讀取 LocalStorage
onMounted(() => {
    // 檢查是否在瀏覽器環境（雖然通常 setup 都在客戶端執行，但加個保險）
    if (typeof window !== 'undefined') {
        const savedState = localStorage.getItem('admin-sidebar-collapsed');
        if (savedState !== null) {
            // 將字串 'true' 轉為布林值 true
            sidebarCollapsed.value = savedState === 'true';
        }
    }
});

// 3. 監聽變化並寫入 LocalStorage
watch(sidebarCollapsed, (newValue) => {
    localStorage.setItem('admin-sidebar-collapsed', String(newValue));
});

// 當前頁面 URL
const page = usePage();
const currentUrl = computed(() => page.url);

// 判斷是否為當前頁面
const isActive = (href: string) => {
    // 簡單的比對，如果需要處理 query params 或子路徑，可以用 startsWith
    return currentUrl.value === href ;
};

// 導航選單項目
const menuItems = [
    { name: '儀表板', href: '/admin', icon: DashboardIcon },
    { name: '借用申請', href: '/admin/bookings', icon: CalendarIcon },
    { name: '教室管理', href: '/admin/classrooms', icon: BuildingIcon },
    { name: '黑名單管理', href: '/admin/blacklists', icon: BanIcon },
];
</script>