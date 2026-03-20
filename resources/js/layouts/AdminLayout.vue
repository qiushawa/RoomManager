<template>
    <div :data-admin-theme="theme" class="flex h-screen bg-a-bg overflow-hidden font-sans">
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-800 border-r border-slate-700/50 relative overflow-hidden">
            <div class="absolute inset-0 z-0 bg-cover bg-center opacity-30 mix-blend-luminosity"
                :style="{ backgroundImage: `url(${sidebarBg})` }"></div>
            <div class="absolute inset-0 z-0 bg-gradient-to-b from-slate-800/80 to-slate-800/95"></div>

            <div
                class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-slate-700/50 z-10 relative">
                <span class="text-xl font-bold text-white">RoomManager</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1 z-10 relative">
                <Link href="/admin/dashboard" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/dashboard') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    總覽
                </Link>

                <Link href="/admin/reviews" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/reviews') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/reviews') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2l4-4" />
                    </svg>
                    審核列表
                </Link>

                <Link href="/admin/borrowing-records" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/borrowing-records') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/borrowing-records') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    短期借用紀錄
                </Link>

                <Link href="/admin/rooms" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/rooms') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/rooms') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5" />
                    </svg>
                    教室管理
                </Link>

                <Link href="/admin/users" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/users') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/users') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H11a4 4 0 00-4 4v2m10 0H7m8-11a3 3 0 11-6 0a3 3 0 016 0z" />
                    </svg>
                    黑名單管理
                </Link>
                <!-- 長期借用管理 -->
                <!-- 長期借用管理 -->
                <Link href="/admin/long-term-borrowing" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/long-term-borrowing') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/long-term-borrowing') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2 2 4-4" />
                    </svg>

                    長期借用管理
                </Link>
                <Link href="/admin/settings" :class="[
                    'flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors',
                    isActive('/admin/settings') ? 'text-white bg-primary/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/10'
                ]">
                    <svg :class="['mr-3 h-5 w-5 flex-shrink-0', isActive('/admin/settings') ? 'text-white' : 'text-slate-400 group-hover:text-white']"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    系統設定
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-700/50 z-10 relative">
                <Link href="/admin/logout" method="post" as="button" type="button"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-red-400 hover:bg-red-500/10 group transition-colors">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    登出
                </Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 bg-a-bg overflow-hidden">
            <header
                class="h-16 flex items-center justify-between px-8 bg-a-header backdrop-blur-md border-b border-a-border sticky top-0 z-30">
                <h1 class="text-xl font-semibold text-a-text">{{ title }}</h1>

                <div class="flex items-center gap-4">
                    <!-- 主題切換 -->
                    <button @click="toggleTheme"
                        class="text-a-text-muted hover:text-a-text-body transition-colors p-1.5 rounded-lg hover:bg-a-surface-hover"
                        :title="isDark ? '切換為淺色模式' : '切換為深色模式'">
                        <!-- 太陽圖示 (深色時顯示) -->
                        <svg v-if="isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- 月亮圖示 (淺色時顯示) -->
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- 通知鈴鐺 -->
                    <div class="relative" ref="notifRef">
                        <button @click="toggleNotifications"
                            class="text-a-text-muted hover:text-a-text-body relative transition-colors">
                            <template v-if="notifCount > 0">
                                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                </span>
                            </template>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>

                        <!-- 通知下拉面板 -->
                        <Transition enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 translate-y-1 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0 scale-100"
                            leave-to-class="opacity-0 translate-y-1 scale-95">
                            <div v-if="showNotif"
                                class="absolute right-0 top-full mt-2 w-96 rounded-xl bg-a-bg border border-a-border-card shadow-2xl shadow-black/20 z-50 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-a-border">
                                    <span class="text-sm font-semibold text-a-text">待審核申請</span>
                                    <span v-if="notifCount > 0"
                                        class="text-xs bg-red-500/20 text-red-400 border border-red-500/30 rounded-full px-2 py-0.5 font-medium">{{
                                            notifCount }} 筆</span>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <div v-if="notifItems.length === 0"
                                        class="px-4 py-8 text-center text-sm text-a-text-dim">
                                        目前沒有待審核的申請
                                    </div>
                                    <Link v-for="item in notifItems" :key="item.id" href="/admin/reviews"
                                        class="flex gap-3 px-4 py-3 hover:bg-a-surface-hover transition-colors border-b border-a-divider last:border-b-0">
                                        <div
                                            class="shrink-0 mt-0.5 h-8 w-8 rounded-lg bg-blue-500/15 border border-blue-500/20 flex items-center justify-center">
                                            <svg class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-a-text-body truncate">
                                                <span class="font-medium text-a-text">{{ item.borrower_name }}</span>
                                                申請借用
                                                <span class="font-medium text-blue-400">{{ item.classroom_code }}</span>
                                            </p>
                                            <p class="text-xs text-a-text-muted mt-0.5">
                                                {{ item.date }} ・ {{ item.time_slots.join(', ') }}
                                            </p>
                                            <p class="text-xs text-a-text-dim mt-0.5">{{ item.created_at }}</p>
                                        </div>
                                    </Link>
                                </div>
                                <Link v-if="notifCount > 0" href="/admin/reviews"
                                    class="block px-4 py-2.5 text-center text-xs font-medium text-primary-light hover:bg-a-surface-hover transition-colors border-t border-a-border">
                                    查看全部待審核申請
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <div
                        class="h-8 w-8 rounded-full bg-a-surface-2 flex items-center justify-center border border-a-border-2">
                        <span class="text-sm font-medium text-a-text-2">Ad</span>
                    </div>
                </div>
            </header>
            <div class="flex-1 overflow-auto p-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { useAdminTheme } from '@/composables';
import sidebarBg from '../../img/2339815.jpg';

defineProps<{
    title?: string;
}>();

const page = usePage();
const { theme, isDark, toggleTheme } = useAdminTheme();

const isActive = (path: string) => {
    return page.url.startsWith(path);
};

// --- 通知功能 ---
interface NotifItem {
    id: number;
    date: string;
    created_at: string;
    borrower_name: string;
    classroom_code: string;
    time_slots: string[];
}

const showNotif = ref(false);
const notifCount = ref(0);
const notifItems = ref<NotifItem[]>([]);
const notifRef = ref<HTMLElement | null>(null);

async function fetchNotifications() {
    try {
        const res = await window.axios.get('/admin/notifications');
        notifCount.value = res.data.count;
        notifItems.value = res.data.items;
    } catch {
        // 靜默失敗
    }
}

function toggleNotifications() {
    showNotif.value = !showNotif.value;
    if (showNotif.value) fetchNotifications();
}

function handleClickOutside(e: MouseEvent) {
    if (notifRef.value && !notifRef.value.contains(e.target as Node)) {
        showNotif.value = false;
    }
}

let pollTimer: ReturnType<typeof setInterval>;

onMounted(() => {
    fetchNotifications();
    pollTimer = setInterval(fetchNotifications, 30000);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    clearInterval(pollTimer);
    document.removeEventListener('click', handleClickOutside);
});
</script>
