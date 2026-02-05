<template>
    <div class="animate-fade-in flex h-full flex-1 flex-col bg-white">
        <!-- Header - 與側邊欄 header 高度對齊 -->
        <header class="z-10 flex h-[88px] shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
            <div>
                <span class="text-xs tracking-wider uppercase text-gray-400">歡迎使用</span>
                <h2 class="text-2xl font-bold text-primary">教室借用系統</h2>
            </div>
            <!-- Quick links -->
            <div class="flex items-center gap-2">
                <a
                    href="/bookings/search"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600 transition-all hover:border-primary hover:bg-primary/5 hover:text-primary"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    查詢借用
                </a>
                <a
                    href="/admin/login"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600 transition-all hover:border-primary hover:bg-primary/5 hover:text-primary"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    管理員
                </a>
            </div>
        </header>

        <!-- 主要內容區 -->
        <div class="flex flex-1 overflow-hidden">
            <!-- 左側：借用說明 -->
            <div class="flex-1 overflow-y-auto bg-gradient-to-br from-white to-slate-50 p-8 md:p-12">
                <div class="max-w-2xl">
                    <h1 class="mb-8 border-b pb-4 text-2xl font-light text-gray-800">
                        借用說明與注意事項
                    </h1>

                    <div class="space-y-8 text-gray-600">
                        <section>
                            <h3 class="mb-3 flex items-center text-lg font-bold text-primary">
                                <span class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-white">1</span>
                                借用流程
                            </h3>
                            <div class="space-y-2 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-relaxed">
                                <p>① 從左側選擇要借用的教室</p>
                                <p>② 在時間表中選擇要借用的時段</p>
                                <p>③ 填寫申請表單並提交</p>
                                <p>④ 等待管理員審核</p>
                            </div>
                        </section>
<!--
                        <section>
                            <h3 class="mb-3 flex items-center text-lg font-bold text-primary">
                                <span class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-white">2</span>
                                注意事項
                            </h3>
                            <div class="space-y-2 rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm leading-relaxed">
                                <p>• 借用時段須為連續時段</p>
                                <p>• 請於使用前確認教室狀態</p>
                                <p>• 使用完畢請恢復原狀並關閉設備</p>
                            </div>
                        </section>

                        <section>
                            <h3 class="mb-3 flex items-center text-lg font-bold text-primary">
                                <span class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-white">3</span>
                                時段說明
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                                    <span class="h-3 w-3 rounded bg-success"></span>
                                    <span>可借用</span>
                                </div>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                                    <span class="h-3 w-3 rounded bg-warning"></span>
                                    <span>審核中</span>
                                </div>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                                    <span class="h-3 w-3 rounded bg-info"></span>
                                    <span>課程使用</span>
                                </div>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                                    <span class="h-3 w-3 rounded bg-danger"></span>
                                    <span>已借出</span>
                                </div>
                            </div>
                        </section> -->
                    </div>
                </div>
            </div>

            <!-- 右側：快速開始面板 (lg 以上顯示) -->
            <PanelBase
                width="w-64 xl:w-80"
                visibility="lg"
                background="white"
                content-class="p-6"
            >
                <!-- <div class="flex h-full flex-col">
                    <h3 class="mb-4 text-sm font-bold text-gray-700">快速開始</h3>
                    <div class="flex-1">
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500">請先從左側選擇教室</p>
                            <p class="mt-1 text-xs text-gray-400">選擇後可檢視課表與借用</p>
                        </div>
                    </div>
                </div> -->
            </PanelBase>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * WelcomeGuide - 歡迎導覽頁面
 *
 * 當使用者尚未選擇教室時顯示的預設頁面。
 * 包含系統介紹、借用流程說明與時段狀態圖例。
 */
import { PanelBase } from '@/layouts';
</script>
