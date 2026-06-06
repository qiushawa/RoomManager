<template>
    <div class="flex h-full flex-col overflow-hidden rounded-2xl border border-a-border-card bg-a-surface shadow-sm transition-all duration-200 hover:border-primary/30">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-a-divider bg-a-surface-2 px-5 py-3.5">
            <div class="flex items-center gap-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/15 text-[11px] font-bold tracking-wide text-primary">
                    {{ buildingCode }}
                </span>
                <span class="text-sm font-semibold text-a-text">{{ buildingLabel }}</span>
                <span class="rounded-full border border-a-divider bg-a-surface px-2 py-0.5 text-[11px] tabular-nums text-a-text-dim">
                    {{ rooms.length }}
                </span>
            </div>
            <button
                type="button"
                class="rounded-lg border border-a-border-2 px-3 py-1.5 text-[11px] font-medium text-a-text-muted transition-all hover:border-primary/40 hover:bg-primary/10 hover:text-primary"
                @click="emit('select-all', buildingCode)"
            >
                全選
            </button>
        </div>

        <!-- Room Grid -->
        <div class="grid flex-1 auto-rows-fr grid-cols-2 gap-2 p-4 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
            <div v-for="room in rooms" :key="room.id" class="h-full">
                <label
                    class="group/card relative flex h-full cursor-pointer flex-col gap-1 rounded-xl border p-3 transition-all duration-150"
                    :class="selectedClassroomSet.has(room.id)
                        ? 'border-primary/40 bg-primary/10 shadow-[0_0_0_1px_rgba(var(--primary-rgb),0.2)]'
                        : 'border-a-border-2 bg-a-surface hover:border-primary/30 hover:bg-primary/5'"
                >
                    <input
                        type="checkbox"
                        class="sr-only"
                        :checked="selectedClassroomSet.has(room.id)"
                        @change="emit('toggle-room', room)"
                    />

                    <!-- Top row: code + checkbox indicator -->
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate text-xs font-semibold tracking-wide text-a-text">
                            {{ room.code }}
                        </span>
                        <div
                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-all duration-150"
                            :class="selectedClassroomSet.has(room.id)
                                ? 'border-primary bg-primary'
                                : 'border-a-border-2 bg-a-surface-2'"
                        >
                            <svg v-if="selectedClassroomSet.has(room.id)" class="h-2.5 w-2.5 text-white" viewBox="0 0 10 8" fill="none">
                                <path d="M1 4L3.5 6.5L9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Room name -->
                    <span class="truncate text-[11px] leading-relaxed text-a-text-muted">{{ room.name }}</span>

                    <!-- Imported badge -->
                    <span
                        v-if="room.has_imported"
                        class="mt-auto w-fit rounded-md border border-amber-400/25 bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-medium text-amber-500"
                    >
                        已匯入
                    </span>
                </label>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { BuildingCode, ClassroomOption } from '@/types';

defineProps<{
    buildingCode: BuildingCode;
    buildingLabel: string;
    rooms: ClassroomOption[];
    selectedClassroomSet: Set<number>;
}>();

const emit = defineEmits<{
    (e: 'select-all', code: BuildingCode): void;
    (e: 'toggle-room', room: ClassroomOption): void;
}>();
</script>