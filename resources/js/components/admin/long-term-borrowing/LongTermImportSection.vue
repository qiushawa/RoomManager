<template>
    <section class="flex flex-col gap-5">
        <Transition name="fade">
            <div
                v-if="importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError"
                class="flex items-start gap-3 rounded-xl border border-red-500/15 bg-red-500/8 px-4 py-3"
            >
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-400/70" viewBox="0 0 16 16" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8zm7.25-3.25a.75.75 0 011.5 0v4a.75.75 0 01-1.5 0v-4zm.75 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs text-red-400/80">
                    {{ importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError }}
                </span>
            </div>
        </Transition>

        <div class="flex flex-col gap-3">
            <ImportBuildingPanel
                v-for="buildingCode in buildingOrder.filter((b) => (classroomsByBuilding[b]?.length ?? 0) > 0)"
                :key="buildingCode"
                :building-code="buildingCode"
                :building-label="buildingLabels[buildingCode] ?? buildingCode"
                :rooms="classroomsByBuilding[buildingCode]"
                :selected-classroom-set="selectedClassroomSet"
                @select-all="selectAllInBuilding"
                @toggle-room="toggleClassroomSelection"
            />
        </div>

        <Transition name="fade">
            <div
                v-if="isAwaitingImportConfirmation"
                class="flex items-center gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3"
            >
                <svg class="h-4 w-4 shrink-0 text-emerald-400/70" viewBox="0 0 16 16" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8zm4.72 3.72a.75.75 0 001.06 1.06L8 10.56l2.22 2.22a.75.75 0 101.06-1.06L9.06 9.5l2.22-2.22a.75.75 0 00-1.06-1.06L8 8.44 5.78 6.22a.75.75 0 00-1.06 1.06L6.94 9.5l-2.22 2.22z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs text-emerald-400/80">已完成預覽，可點「查看預覽」進入彈窗確認後匯入。</span>
            </div>
        </Transition>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-a-divider pt-4">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg border border-a-border-2 px-3 py-1.5 text-xs text-a-text-muted transition-colors hover:border-a-border-card hover:text-a-text"
                    @click="clearSelectedClassrooms"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 4h12M5 4V2h6v2M6 7v5M10 7v5M3 4l1 9h8l1-9"/>
                    </svg>
                    清空選取
                </button>

                <button
                    type="button"
                    :disabled="revokingImports || selectedImportedClassrooms.length === 0"
                    class="flex items-center gap-1.5 rounded-lg border border-red-500/25 bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-500 transition-colors hover:border-red-500/40 hover:bg-red-500/15 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="revokeSelectedImports"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h4m-7 3h10m-8 0v7a1 1 0 001 1h4a1 1 0 001-1V5"/>
                    </svg>
                    {{ revokingImports ? '撤回中...' : `撤回已匯入(${selectedImportedClassrooms.length})` }}
                </button>
            </div>

            <div class="flex items-center gap-2.5">
                <Transition name="fade">
                    <span
                        v-if="selectedClassroomIds.length > 0"
                        class="rounded-full bg-primary/15 px-2.5 py-1 text-[11px] font-medium tabular-nums text-primary"
                    >
                        已選 {{ selectedClassroomIds.length }} 間
                    </span>
                </Transition>

                <button
                    type="button"
                    :disabled="previewLoading || importForm.processing || selectedClassroomIds.length === 0"
                    class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-medium text-white shadow-lg shadow-primary/20 transition-all hover:bg-primary-dark hover:shadow-primary/30 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="handleImportAction"
                >
                    <svg
                        v-if="previewLoading || importForm.processing"
                        class="h-3.5 w-3.5 animate-spin"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/>
                    </svg>
                    {{ importActionLabel }}
                </button>
            </div>
        </div>

        <BaseModal :show="importPreviewModalOpen" size="xl" @close="closeImportPreviewModal">
            <div
                class="border-b border-a-divider px-6 py-4"
                :class="isDark ? 'bg-a-surface-2' : 'bg-transparent'"
            >
                <h3 class="text-base font-bold text-a-text">匯入課表預覽</h3>
                <p class="mt-1 text-sm text-a-text-muted">共 {{ previewSchedules.length }} 筆，確認內容後再正式匯入。</p>
            </div>

            <div class="max-h-[70vh] overflow-y-auto px-6 py-4">
                <ImportPreviewTable :classrooms="classrooms" :preview-schedules="previewSchedules" />
            </div>

            <div
                class="flex items-center justify-end gap-2 border-t border-a-divider px-6 py-4"
                :class="isDark ? 'bg-a-surface' : 'bg-transparent'"
            >
                <button
                    type="button"
                    class="rounded-lg border border-a-border-2 px-4 py-2 text-sm text-a-text-muted transition-colors hover:border-a-border-card hover:text-a-text"
                    @click="closeImportPreviewModal"
                >
                    關閉
                </button>
                <button
                    type="button"
                    :disabled="importForm.processing || previewSchedules.length === 0"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="submitImport"
                >
                    {{ importForm.processing ? '匯入中...' : '確認匯入' }}
                </button>
            </div>
        </BaseModal>
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { BaseModal } from '@/components';
import { ImportBuildingPanel, ImportPreviewTable } from '@/components/admin';
import { useAdminTheme } from '@/composables';
import type {
    BuildingCode,
    BuildingOption,
    ClassroomOption,
    PreviewSchedule,
} from '@/types';
import { getRoomBuildingCode } from '@/utils';

const props = defineProps<{
    classrooms: ClassroomOption[];
    buildingOptions: BuildingOption[];
}>();

const { isDark } = useAdminTheme();

const selectedClassroomIds = ref<number[]>([]);
const importErrorMessage = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const previewSchedules = ref<PreviewSchedule[]>([]);
const isAwaitingImportConfirmation = ref(false);
const importPreviewModalOpen = ref(false);
const revokingImports = ref(false);

const buildingOrder = computed<BuildingCode[]>(() => props.buildingOptions.map((item) => item.code));
const buildingLabels = computed<Record<string, string>>(() => {
    return props.buildingOptions.reduce<Record<string, string>>((acc, item) => {
        acc[item.code] = item.label;
        return acc;
    }, {});
});

const importForm = useForm<{ classroom_ids: number[] }>({
    classroom_ids: [],
});

const selectedClassroomSet = computed(() => new Set(selectedClassroomIds.value.map((id) => Number(id))));
const selectedImportedClassrooms = computed(() => {
    return props.classrooms.filter((room) => selectedClassroomSet.value.has(room.id) && room.has_imported);
});

const importActionLabel = computed(() => {
    if (previewLoading.value) return '預覽中...';
    if (importForm.processing) return '匯入中...';
    if (isAwaitingImportConfirmation.value) return '查看預覽';
    return `預覽匯入 ${selectedClassroomIds.value.length} 間教室`;
});

const classroomsByBuilding = computed<Record<string, ClassroomOption[]>>(() => {
    const grouped: Record<string, ClassroomOption[]> = {};

    for (const code of buildingOrder.value) {
        grouped[code] = props.classrooms.filter((room) => getRoomBuildingCode(room) === code);
    }

    return grouped;
});

const importServerError = computed(() => {
    const errors = importForm.errors as Record<string, string | undefined>;
    return errors.import ?? '';
});

watch(selectedClassroomIds, () => {
    previewSchedules.value = [];
    previewError.value = '';
    isAwaitingImportConfirmation.value = false;
    importPreviewModalOpen.value = false;
});

function toggleClassroomSelection(room: ClassroomOption) {
    importErrorMessage.value = '';
    const selected = new Set(selectedClassroomIds.value);
    if (selected.has(room.id)) {
        selected.delete(room.id);
        selectedClassroomIds.value = Array.from(selected);
        return;
    }
    selected.add(room.id);
    selectedClassroomIds.value = Array.from(selected);
}

function selectAllInBuilding(buildingCode: BuildingCode) {
    importErrorMessage.value = '';
    const ids = classroomsByBuilding.value[buildingCode].map((room) => room.id);
    selectedClassroomIds.value = Array.from(new Set([...selectedClassroomIds.value, ...ids]));
}

function clearSelectedClassrooms() {
    selectedClassroomIds.value = [];
    importErrorMessage.value = '';
    previewSchedules.value = [];
    previewError.value = '';
    isAwaitingImportConfirmation.value = false;
    importPreviewModalOpen.value = false;
}

function closeImportPreviewModal() {
    importPreviewModalOpen.value = false;
}

async function previewImport() {
    importErrorMessage.value = '';
    previewError.value = '';
    if (selectedClassroomIds.value.length === 0) {
        previewError.value = '請至少選擇一間教室。';
        return;
    }
    previewLoading.value = true;
    previewSchedules.value = [];
    isAwaitingImportConfirmation.value = false;
    try {
        const payloadIds = selectedClassroomIds.value.map((id) => Number(id));
        const response = await window.axios.post('/admin/long-term-borrowing/preview', {
            classroom_ids: payloadIds,
        });
        const schedules = (response?.data?.schedules ?? []) as PreviewSchedule[];
        previewSchedules.value = schedules;
        if (schedules.length === 0) {
            previewError.value = '預覽成功，但未取得可匯入課表。';
            return;
        }
        isAwaitingImportConfirmation.value = true;
        importPreviewModalOpen.value = true;
    } catch (error: any) {
        const backendMessage =
            error?.response?.data?.errors?.import?.[0]
            || error?.response?.data?.errors?.classroom_ids?.[0]
            || error?.response?.data?.message;
        previewError.value = backendMessage || '預覽失敗，請確認匯入服務與參數設定。';
    } finally {
        previewLoading.value = false;
    }
}

async function handleImportAction() {
    importErrorMessage.value = '';

    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }

    if (isAwaitingImportConfirmation.value && previewSchedules.value.length > 0) {
        importPreviewModalOpen.value = true;
        return;
    }

    await previewImport();
}

function submitImport() {
    importErrorMessage.value = '';
    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }
    if (previewSchedules.value.length === 0) {
        importErrorMessage.value = '請先完成課表預覽，再進行匯入。';
        return;
    }
    importForm.classroom_ids = selectedClassroomIds.value.map((id) => Number(id));
    importForm.post('/admin/long-term-borrowing/import', {
        preserveScroll: true,
        onSuccess: () => {
            selectedClassroomIds.value = [];
            previewSchedules.value = [];
            previewError.value = '';
            isAwaitingImportConfirmation.value = false;
            importPreviewModalOpen.value = false;
        },
        onError: () => {
            importErrorMessage.value = '匯入失敗，請確認匯入服務與參數設定。';
            isAwaitingImportConfirmation.value = false;
            importPreviewModalOpen.value = true;
        },
    });
}

async function revokeSelectedImports() {
    const targetRooms = selectedImportedClassrooms.value;
    if (targetRooms.length === 0) {
        importErrorMessage.value = '請先勾選至少一間已匯入教室。';
        return;
    }

    const roomCodes = targetRooms.map((room) => room.code).join('、');
    if (!confirm(`確定要撤回以下教室的課表匯入嗎？\n${roomCodes}\n此操作將刪除其本學期所有匯入記錄。`)) {
        return;
    }

    revokingImports.value = true;
    importErrorMessage.value = '';

    const failedCodes: string[] = [];
    for (const room of targetRooms) {
        try {
            await window.axios.delete(`/admin/long-term-borrowing/import/${room.id}`);
        } catch {
            failedCodes.push(room.code);
        }
    }

    revokingImports.value = false;

    if (failedCodes.length > 0) {
        importErrorMessage.value = `部分撤回失敗：${failedCodes.join('、')}`;
    }

    selectedClassroomIds.value = selectedClassroomIds.value.filter(
        (id) => !targetRooms.some((room) => room.id === id),
    );
    router.reload();
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
