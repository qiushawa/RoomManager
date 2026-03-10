<template>
    <div class="relative flex h-full flex-1 flex-col bg-white">
        <!-- 頂部 Header：模仿選擇教室後的樣式 -->
        <header
            class="z-10 flex h-[88px] shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
            <div>
                <span class="text-xs tracking-wider uppercase text-gray-400">Welcome</span>
                <h2 class="text-2xl font-bold text-primary">
                    教室借用系統
                </h2>
            </div>

            <a href="/admin/login"
                class="flex items-center gap-1.5 rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-gray-500 transition-all hover:bg-gray-50 hover:text-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                管理員登入
            </a>
        </header>

        <!-- 中間區域：使用說明與教學 -->
        <div class="flex flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50">
            <div class="mx-auto w-full max-w-4xl py-12 px-6 lg:px-8">
                <!-- 歡迎頭部 -->
                <div class="mb-12 text-center">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <svg class="h-10 w-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl text-center">
                        歡迎使用教室借用系統
                    </h1>
                    <p class="mt-4 text-center text-lg text-slate-500">
                        請閱讀以下簡單的三個步驟，即可快速完成您的教室借用申請。
                    </p>
                </div>

                <!-- 借用流程教學 - 垂直時間軸樣式 -->
                <div class="relative mx-auto max-w-2xl">
                    <!-- 中央連接線 (隱藏於小螢幕) -->
                    <div class="absolute left-[27px] top-6 bottom-6 hidden w-px bg-slate-200 sm:block"></div>

                    <div class="space-y-12">
                        <!-- 第一步 -->
                        <div class="relative flex flex-col sm:flex-row gap-6">
                            <div class="z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100 shadow-sm ring-4 ring-white">
                                <span class="text-lg font-bold text-blue-600">1</span>
                            </div>
                            <div class="flex-1 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-xl font-bold text-slate-900">選擇想借用的教室</h3>
                                </div>
                                <p class="mb-4 text-slate-600 leading-relaxed">
                                    在畫面<strong>左側選單</strong>中，展開對應的教學大樓，並點擊您需要借用的教室編號。
                                </p>
                                <div class="rounded-lg bg-blue-50/50 p-4 border border-blue-100/50">
                                    <div class="flex items-center gap-2 text-sm text-blue-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <strong>小提示：</strong>點擊後即可查看該教室這週的最新排課與借用狀況唷！
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 第二步 -->
                        <div class="relative flex flex-col sm:flex-row gap-6">
                            <div class="z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 shadow-sm ring-4 ring-white">
                                <span class="text-lg font-bold text-indigo-600">2</span>
                            </div>
                            <div class="flex-1 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-xl font-bold text-slate-900">點擊空白時段</h3>
                                </div>
                                <p class="mb-4 text-slate-600 leading-relaxed">
                                    在中間呈現的課表中，針對<strong>空白且可借用的時段</strong>直接點擊滑鼠。您可以連續點擊多個相鄰時段。
                                </p>
                                <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <div class="h-4 w-4 rounded-md border border-slate-200 bg-white shadow-sm"></div>
                                        <span class="text-slate-600">空白表示可借用</span>
                                    </div>
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <div class="h-4 w-4 rounded-md border border-blue-500 bg-blue-500 shadow-sm"></div>
                                        <span class="text-slate-600">點擊後變為已選取</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 第三步 -->
                        <div class="relative flex flex-col sm:flex-row gap-6">
                            <div class="z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-violet-100 shadow-sm ring-4 ring-white">
                                <span class="text-lg font-bold text-violet-600">3</span>
                            </div>
                            <div class="flex-1 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-xl font-bold text-slate-900">填寫表單送出</h3>
                                </div>
                                <p class="mb-4 text-slate-600 leading-relaxed">
                                    選好時段後，點擊畫面右側的<strong>「填寫申請表單」</strong>按鈕。依序填寫您的聯絡資訊與使用事由，核對無誤後即可送出申請。
                                </p>
                                <div class="rounded-lg bg-orange-50/50 p-4 border border-orange-100/50">
                                    <div class="flex flex-col gap-1 text-sm text-orange-800">
                                        <div class="flex items-center gap-2 font-bold mb-1">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            借用注意事項
                                        </div>
                                        <ul class="list-inside list-disc pl-2 space-y-1 text-orange-700/90 ml-5">
                                            <li>申請送出後需等待系辦管理員審核。</li>
                                            <li>使用完畢請務必復原冷氣、燈光等各項設備。</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 底部佔位區：模仿 ScheduleToolbar -->
        <div class="relative z-20 flex h-[88px] min-h-[88px] shrink-0 items-center justify-center border-t border-gray-200 bg-white">
            <span class="flex items-center gap-2 text-sm text-gray-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-gray-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-gray-400"></span>
                </span>
                等待選擇教室...
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * WelcomeGuide - 歡迎導覽頁面
 *
 * 當使用者尚未選擇教室時顯示的預設頁面。
 * 包含完整的系統介紹與借用流程教學。
 */
</script>
