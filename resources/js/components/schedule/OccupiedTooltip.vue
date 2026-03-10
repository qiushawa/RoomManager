<template>
    <div
        class="pointer-events-none absolute left-1/2 z-50 flex w-44 -translate-x-1/2 flex-col rounded-md bg-gray-800/95 p-2.5 text-white shadow-xl backdrop-blur-sm transition-opacity duration-200 opacity-0 group-hover:opacity-100 box-border"
        :class="showBelow ? 'top-full mt-1.5' : 'bottom-full mb-1.5'"
    >
        <div class="shrink-0 border-b border-gray-600/70 pb-1.5 text-center text-xs font-bold text-gray-100 tracking-wider">
            {{ statusLabel }}
        </div>

        <div class="flex flex-1 flex-col justify-start gap-2 pt-2 text-left overflow-hidden">
            <template v-if="isObject">
                <div v-if="displayTitle" class="flex flex-col">
                    <span class="text-[10px] font-medium leading-tight text-gray-400">課程</span>
                    <span class="truncate text-xs text-gray-100" :title="displayTitle">
                        {{ displayTitle }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-[10px] font-medium leading-tight text-gray-400">指導老師</span>
                    <span class="truncate text-xs text-gray-100" :title="itemData?.instructor">
                        {{ itemData?.instructor || '-' }}
                    </span>
                </div>

                <div v-if="itemData?.applicant" class="flex flex-col">
                    <span class="text-[10px] font-medium leading-tight text-gray-400">申請人</span>
                    <span class="truncate text-xs text-gray-100" :title="itemData?.applicant">
                        {{ itemData.applicant || '-' }}
                    </span>
                </div>
            </template>

            <template v-else>
                <div class="flex py-2 items-center justify-center text-xs text-gray-400 italic">
                    (無詳細資訊)
                </div>
            </template>
        </div>

        <div
            class="absolute left-1/2 -ml-1.5 border-[6px] border-transparent"
            :class="showBelow ? 'bottom-full border-b-gray-800/95' : 'top-full border-t-gray-800/95'"
        ></div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { STATUS_LABELS } from '@/constants';
import type { OccupiedStatus, OccupiedItem } from '@/types';

const props = defineProps<{
    item: string | OccupiedItem | null;
    showBelow?: boolean;
}>();

const isObject = computed(() => typeof props.item === 'object' && props.item !== null);

const itemData = computed(() => {
    return isObject.value ? (props.item as OccupiedItem) : null;
});

const status = computed<OccupiedStatus | null>(() => {
    if (!props.item) return null;
    if (typeof props.item === 'string') return props.item as OccupiedStatus;
    return (props.item as OccupiedItem).status;
});

const statusLabel = computed(() => {
    if (!status.value) return '';
    return STATUS_LABELS[status.value] || '已佔用';
});

const displayTitle = computed(() => {
    if (!itemData.value) return '-';
    // 若是「已借出(approved)」，則隱藏事由 (這會涉及隱私所以不公開顯示)
    if (status.value !== 'course') return '';
    return itemData.value.title || '-';
});
</script>
