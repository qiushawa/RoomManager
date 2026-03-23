<template>
    <div class="relative flex h-full flex-1 flex-col bg-white">
        <!-- 頂部 Header -->
        <header
            class="z-10 flex h-[88px] shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
            <div>
                <span class="text-xs tracking-wider uppercase text-gray-400">Welcome</span>
                <h2 class="text-2xl font-bold text-primary">教室借用系統</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="https://qiushawa.github.io/RoomManager/" target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-1.5 rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-gray-500 transition-all hover:bg-gray-50 hover:text-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                    </svg>
                    使用導覽
                </a>
                <a href="/admin/login"
                    class="flex items-center gap-1.5 rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-gray-500 transition-all hover:bg-gray-50 hover:text-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    管理員登入
                </a>
            </div>
        </header>

        <!-- 主要內容 -->
        <div class="flex flex-1 overflow-y-auto overflow-x-hidden bg-slate-50">
            <div class="mx-auto w-full max-w-3xl py-14 px-6">

                <!-- 頁首文字 -->
                <div class="mb-12 text-center">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                        如何借用教室？
                    </h1>
                    <p class="mt-3 text-base text-slate-500">
                        跟著以下三個步驟，即可快速完成申請。
                    </p>
                </div>

                <!-- 三步驟橫向卡片 -->
                <div class="relative">
                    <!-- 步驟連接箭頭 (桌機版) -->
                    <div class="absolute top-[42px] left-[calc(33.33%+4px)] right-[calc(33.33%+4px)] hidden items-center justify-between sm:flex pointer-events-none">
                        <div class="flex-1 flex items-center">
                            <div class="h-px flex-1 border-t-2 border-dashed border-slate-200"></div>
                            <svg class="h-4 w-4 text-slate-300 -ml-px" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="flex-1 flex items-center ml-1">
                            <div class="h-px flex-1 border-t-2 border-dashed border-slate-200"></div>
                            <svg class="h-4 w-4 text-slate-300 -ml-px" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <!-- Step 1 -->
                        <div class="flex flex-col items-center text-center">
                            <div class="relative mb-5 flex h-[84px] w-[84px] items-center justify-center rounded-full bg-blue-50 ring-4 ring-white shadow-sm">
                                <svg class="h-9 w-9 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white shadow">1</span>
                            </div>
                            <h3 class="mb-2 text-base font-bold text-slate-800">選擇教室</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                在<span class="font-medium text-slate-700">左側選單</span>點擊您要借用的教室編號，即可查看本週排課與借用狀況。
                            </p>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex flex-col items-center text-center">
                            <div class="relative mb-5 flex h-[84px] w-[84px] items-center justify-center rounded-full bg-indigo-50 ring-4 ring-white shadow-sm">
                                <svg class="h-9 w-9 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white shadow">2</span>
                            </div>
                            <h3 class="mb-2 text-base font-bold text-slate-800">選取時段</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                在課表中點擊<span class="font-medium text-slate-700">空白可借用的格子</span>。按住拖曳可一次選取多個相鄰時段。
                            </p>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex flex-col items-center text-center">
                            <div class="relative mb-5 flex h-[84px] w-[84px] items-center justify-center rounded-full bg-violet-50 ring-4 ring-white shadow-sm">
                                <svg class="h-9 w-9 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-violet-500 text-xs font-bold text-white shadow">3</span>
                            </div>
                            <h3 class="mb-2 text-base font-bold text-slate-800">填寫並送出申請</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                點擊右側<span class="font-medium text-slate-700">「填寫申請表單」</span>，完成聯絡資訊與使用事由後送出即可。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 注意事項 -->
                <div class="mt-12 rounded-2xl border border-amber-100 bg-amber-50/70 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-bold text-amber-800">借用注意事項</p>
                            <ul class="space-y-1.5 text-sm text-amber-700">
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>
                                    申請送出後需等待系辦管理員審核，請留意審核通知。
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>
                                    使用完畢請務必復原冷氣、燈光等各項設備。
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <!-- 底部狀態列 -->
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