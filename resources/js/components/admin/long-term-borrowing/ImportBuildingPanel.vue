<template>
    <div class="rounded-2xl border border-a-border-card bg-a-surface">
        <div class="flex items-center justify-between border-b border-a-divider px-5 py-3">
            <div class="flex items-center gap-3">
                <h4 class="text-sm font-semibold text-a-text">{{ buildingLabel }}</h4>
                <span class="text-xs text-a-text-dim">{{ rooms.length }} 間</span>
            </div>
            <button
                type="button"
                class="rounded-md border border-a-border-2 px-3 py-1 text-xs text-a-text-2 transition-colors hover:bg-a-surface-hover disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="rooms.length === 0"
                @click="emit('select-all', buildingCode)"
            >
                全選
            </button>
        </div>

        <div v-if="rooms.length === 0" class="px-5 py-6 text-center text-xs text-a-text-dim">
            此大樓目前沒有可匯入教室
        </div>

        <div v-else class="grid grid-cols-2 gap-2 p-4 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
            <div
                v-for="room in rooms"
                :key="room.id"
                class="flex flex-col gap-1.5"
            >
                <label
                    class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-3 py-2.5 transition-colors"
                    :class="selectedClassroomSet.has(room.id)
                        ? 'border-primary/40 bg-primary/8'
                        : 'border-a-divider hover:bg-a-surface-hover'"
                >
                    <input
                        type="checkbox"
                        class="h-3.5 w-3.5 shrink-0 rounded border-a-border-2 bg-a-input text-primary focus:ring-primary/30"
                        :checked="selectedClassroomSet.has(room.id)"
                        @change="emit('toggle-room', room)"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium text-a-text">{{ room.code }}</p>
                        <p class="truncate text-[11px] text-a-text-dim">{{ room.name }}</p>
                    </div>
                    <span
                        v-if="room.has_imported"
                        class="shrink-0 rounded border border-amber-400/30 bg-amber-500/10 px-1.5 py-0.5 text-[10px] text-amber-300"
                    >
                        已匯入
                    </span>
                </label>

                <button
                    v-if="room.has_imported"
                    type="button"
                    class="w-full rounded border border-red-500/30 bg-red-500/8 px-2 py-1 text-[11px] text-red-400 transition-colors hover:bg-red-500/20"
                    @click="emit('revoke-room', room)"
                >
                    撤回匯入
                </button>
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
    (e: 'revoke-room', room: ClassroomOption): void;
}>();
</script>
