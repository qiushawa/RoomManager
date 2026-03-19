<template>
    <SidebarBase width="narrow" visibility="md" :background-image="buildingBg"
        background-overlay="bg-gradient-to-b from-slate-900/75 via-slate-800/70 to-slate-900/85">
        <template #header>
            <div class="flex items-center gap-3">
                <img :src="logoImg" alt="Logo"
                    class="h-10 w-10 rounded-xl object-cover shadow-lg ring-2 ring-white/30" />
                <h2 class="text-base font-bold leading-tight text-white">
                    教室借用系統
                </h2>
            </div>
        </template>
        <div v-for="(building, bIndex) in buildings" :key="bIndex" class="mt-5 first:mt-0">
            <h3 class="mb-3 border-b border-white/20 pb-1 text-sm font-medium text-white/80">
                {{ building.name }}
            </h3>
            <div class="flex">
                <div class="flex w-[15%] shrink-0 justify-end pr-3">
                    <div class="h-full w-px bg-gradient-to-b from-transparent via-white/30 to-transparent"></div>
                </div>
                <ul class="flex min-w-0 flex-1 flex-col items-stretch gap-2">
                    <li v-for="room in building.rooms" :key="room.id" @click="$emit('select-room', room)"
                        class="group flex w-full cursor-pointer items-center border font-bold transition-all duration-200 rounded-lg px-6 py-2.5 text-sm"
                        :class="[
                            activeRoomCode === room.code
                                ? 'border-white/40 bg-white text-slate-800 shadow-lg'
                                : 'border-white/15 bg-white/10 text-white backdrop-blur-md hover:border-white/30 hover:bg-white/20',
                        ]">
                        <span class="truncate">{{ room.code }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <template #footer>
            <p class="text-center text-[10px] leading-relaxed text-white/40">
                © 2026 國立虎尾科技大學<br />資訊工程系 系辦公室
            </p>
        </template>
    </SidebarBase>
</template>

<script setup lang="ts">
/**
 * ClassroomNavigator - 教室導覽選單
 *
 * 左側邊欄元件，用於顯示可借用的教室清單並讓使用者選擇目標教室。
 * 按建築物分類展示，支援選中狀態高亮。
 *
 * @emits select-room - 當使用者點選教室時觸發
 */
import { SidebarBase } from '@/layouts';
import type { Building, Room } from '@/types';
import buildingBg from '@img/building-g3.png';
import logoImg from '@img/2339815.jpg';

withDefaults(
    defineProps<{
        /** 建築物清單，包含各建築內的教室 */
        buildings: Building[];
        /** 當前選中教室的代碼，用於高亮顯示 */
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