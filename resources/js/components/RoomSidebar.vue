<template>
    <aside
        class="sticky top-0 flex h-auto w-full shrink-0 flex-col overflow-y-auto border-r border-gray-200 bg-gray-50 p-4 md:h-screen md:w-64"
    >
        <div class="mb-6">
            <h2 class="mb-4 px-2 text-xl font-bold text-gray-800">
                教室借用系統
            </h2>
            <p class="mb-4 px-2 text-xs text-gray-500">
                請點擊下方列表選擇教室
            </p>
        </div>

        <div v-for="(building, bIndex) in buildings" :key="bIndex" class="mb-6">
            <h3
                class="mb-3 border-b-2 border-[#6c8ebf] pb-1 text-sm font-medium text-slate-500"
            >
                {{ building.name }}
            </h3>
            <ul class="space-y-2">
                <li
                    v-for="room in building.rooms"
                    :key="room.id"
                    @click="$emit('select-room', room)"
                    class="group flex cursor-pointer items-center justify-between rounded border px-3 py-3 text-sm font-bold transition-all duration-200"
                    :class="[
                        activeRoomCode === room.code
                            ? 'border-[#6c8ebf] bg-[#6c8ebf] text-white shadow-md'
                            : 'border-gray-300 bg-white text-gray-600 hover:border-[#6c8ebf] hover:text-[#6c8ebf]',
                    ]"
                >
                    <span>{{ room.code }}</span>
                    <span v-if="activeRoomCode === room.code" class="text-xl"
                        >›</span
                    >
                </li>
            </ul>
        </div>
    </aside>
</template>

<script setup lang="ts">
import type { Building, Room } from '@/types';

withDefaults(
    defineProps<{
        buildings: Building[];
        activeRoomCode?: string | null;
    }>(),
    {
        activeRoomCode: null,
    },
);

defineEmits<{
    (e: 'select-room', room: Room): void;
}>();
</script>
