<template>
    <SidebarBase width="narrow" visibility="md" :background-image="buildingBg"
        background-overlay="bg-gradient-to-b from-slate-900/80 via-slate-800/75 to-slate-900/90">
        
        <template #header>
            <div class="flex items-center gap-3 px-1">
                <div class="relative">
                    <img :src="logoImg" alt="Logo"
                        class="h-9 w-9 rounded-xl object-cover shadow-lg ring-1 ring-white/25" />
                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border border-slate-900 bg-emerald-400"></span>
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold tracking-wide text-white">
                        教室借用系統
                    </h2>
                    <p class="text-xs text-white/45 tracking-wider">v{{ appVersion }}</p>
                </div>
            </div>
        </template>

        <div class="flex h-full min-h-0 flex-1 flex-col overflow-hidden">
            <div class="mb-4">
                <button
                    @click="$emit('select-overview')"
                    class="group relative flex w-full cursor-pointer items-center gap-2.5 overflow-hidden rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200"
                    :class="isOverviewActive
                        ? 'bg-white text-slate-800 shadow-lg shadow-black/20'
                        : 'bg-white/8 text-white/80 hover:bg-white/14 hover:text-white'"
                >
                    <span
                        class="absolute left-0 top-1/2 h-4 w-0.5 -translate-y-1/2 rounded-r-full bg-white transition-all duration-200"
                        :class="isOverviewActive ? 'opacity-0' : 'opacity-0 group-hover:opacity-40'"
                    ></span>
                    <svg class="h-3.5 w-3.5 shrink-0" :class="isOverviewActive ? 'text-slate-600' : 'text-white/60'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
                    </svg>
                    <span>教室總覽</span>
                </button>
            </div>

            <div class="mb-4 flex items-center gap-2">
                <div class="h-px flex-1 bg-white/10"></div>
                <span class="text-[10px] font-medium tracking-[0.15em] text-white/30 uppercase">Buildings</span>
                <div class="h-px flex-1 bg-white/10"></div>
            </div>

            <div class="sidebar-scroll -mr-1 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div class="flex flex-col gap-5 pb-2">
                    <div v-for="(building, bIndex) in buildings" :key="bIndex">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-md bg-white/10 text-[10px] font-bold text-white/60">
                                {{ bIndex + 1 }}
                            </span>
                            <h3 class="min-w-0 truncate text-xs font-semibold tracking-widest text-white/55 uppercase">
                                {{ building.name }}
                            </h3>
                        </div>

                        <ul class="flex flex-col gap-1 pl-1">
                            <li
                                v-for="room in building.rooms"
                                :key="room.id"
                                @click="$emit('select-room', room)"
                                class="room-item group relative flex w-full cursor-pointer items-center gap-2 overflow-hidden rounded-lg px-3.5 py-2 text-sm font-medium transition-all duration-150"
                                :class="activeRoomCode === room.code
                                    ? 'bg-white text-slate-800 shadow-md shadow-black/15'
                                    : 'text-white/70 hover:bg-white/10 hover:text-white'"
                            >
                                <span
                                    class="absolute left-0 top-1/2 h-3.5 w-0.5 -translate-y-1/2 rounded-r-full transition-all duration-150"
                                    :class="activeRoomCode === room.code
                                        ? 'bg-slate-400 opacity-60'
                                        : 'bg-white opacity-0 group-hover:opacity-50'"
                                ></span>
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full transition-all duration-150"
                                    :class="activeRoomCode === room.code
                                        ? 'bg-slate-500'
                                        : 'bg-white/20 group-hover:bg-white/50'"
                                ></span>
                                <span class="truncate">{{ room.code }}</span>
                                <svg
                                    v-if="activeRoomCode === room.code"
                                    class="ml-auto h-3 w-3 shrink-0 text-slate-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex flex-col items-center gap-1.5">
                <div class="h-px w-12 bg-white/15 rounded-full"></div>
                <p class="text-center text-[10px] leading-relaxed tracking-wide text-white/30">
                    © 2026 國立虎尾科技大學<br />資訊工程系 系辦公室
                </p>
            </div>
        </template>
    </SidebarBase>
</template>

<script setup lang="ts">
import { SidebarBase } from '@/layouts';
import type { Building, Room } from '@/types';
import buildingBg from '@img/building-g3.png';
import logoImg from '@img/2339815.jpg';

const appVersion = __APP_VERSION__;

withDefaults(
    defineProps<{
        buildings: Building[];
        activeRoomCode?: string | null;
        isOverviewActive?: boolean;
    }>(),
    {
        activeRoomCode: null,
        isOverviewActive: false,
    },
);

defineEmits<{
    (e: 'select-room', room: Room): void;
    (e: 'select-overview'): void;
}>();
</script>