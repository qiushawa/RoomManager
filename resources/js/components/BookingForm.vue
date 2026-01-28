<template>
    <div class="animate-fade-in mx-auto max-w-2xl">
        <div class="space-y-6 rounded-lg bg-white p-6">
            <h3 class="border-b pb-2 text-lg font-bold text-gray-800">
                申請資料填寫
            </h3>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >借用日期</label
                    >
                    <div
                        class="w-full rounded border border-gray-200 bg-gray-50 p-2 text-sm text-gray-600"
                    >
                        {{
                            selectedSlots.length > 0
                                ? selectedSlots[0].date.split('-').join('/')
                                : '-'
                        }}
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >教室代號</label
                    >
                    <div
                        class="w-full rounded border border-gray-200 bg-gray-50 p-2 text-sm font-bold text-gray-600"
                    >
                        {{ targetRoom?.code }}
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >姓名</label
                    >
                    <input
                        type="text"
                        v-model="form.name"
                        class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                        placeholder="完整姓名"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >學號/員工編號</label
                    >
                    <input
                        type="text"
                        v-model="form.identity_code"
                        class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                        placeholder="40123456"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >email</label
                    >
                    <input
                        type="email"
                        v-model="form.email"
                        class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                        placeholder="請輸入常用郵件"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >電話</label
                    >
                    <input
                        type="text"
                        v-model="form.phone"
                        class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                        placeholder="請輸入聯絡電話"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >科系</label
                    >
                    <input
                        type="text"
                        v-model="form.department"
                        class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                        placeholder="請輸入科系"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-500"
                        >指導老師</label
                    >
                    <input
                        type="text"
                        v-model="form.teacher"
                        class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                        placeholder="老師姓名"
                    />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-gray-500"
                    >已選時段 ({{ selectedSlots.length }} 節)</label
                >
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(slot, idx) in selectedSlots"
                        :key="idx"
                        class="rounded border border-blue-100 bg-blue-50 px-2 py-1 text-xs text-blue-700"
                    >
                        {{ slot.label }}
                    </span>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-gray-500"
                    >借用事由</label
                >
                <textarea
                    rows="3"
                    v-model="form.reason"
                    class="w-full rounded border border-gray-300 p-2 transition outline-none focus:border-blue-500"
                    placeholder="請填寫詳細事由..."
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ApplicantForm, Room, SelectedSlot } from '@/types';

defineProps<{
    targetRoom: Room;
    selectedSlots: SelectedSlot[];
    form: ApplicantForm;
}>();
</script>
