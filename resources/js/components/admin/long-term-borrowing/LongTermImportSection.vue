<template>
    <section class="space-y-5 text-a-text" :aria-busy="busy">
        <div
            class="rounded-2xl border border-a-border-card bg-a-surface p-5 sm:p-6"
        >
            <h3 class="text-lg font-bold">教室課表匯入</h3>
            <p class="mt-1 text-sm text-a-text-muted">
                選擇學期與教室，預覽學校課表，再確認匯入。
            </p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-sm font-medium"
                    >匯入學期
                    <select v-model="semesterId" :disabled="busy" class="field">
                        <option value="">請選擇學期</option>
                        <option
                            v-for="semester in semesters"
                            :key="semester.id"
                            :value="semester.id"
                        >
                            {{ semester.label }}
                        </option>
                    </select>
                </label>
                <label class="text-sm font-medium"
                    >搜尋教室<input
                        v-model="roomSearch"
                        type="search"
                        class="field"
                        placeholder="輸入教室代碼或名稱"
                /></label>
                <label class="text-sm font-medium"
                    >大樓<select v-model="buildingFilter" class="field">
                        <option value="">全部大樓</option>
                        <option
                            v-for="building in buildingOptions"
                            :key="building.code"
                            :value="building.code"
                        >
                            {{ building.label }}
                        </option>
                    </select></label
                >
                <label class="text-sm font-medium"
                    >匯入狀態<select v-model="statusFilter" class="field">
                        <option value="">全部教室</option>
                        <option value="new">尚未匯入</option>
                        <option value="imported">已匯入</option>
                    </select></label
                >
            </div>
            <p v-if="selectedSemester" class="mt-3 text-xs text-a-text-muted">
                學期日期：{{ selectedSemester.start_date }}～{{
                    selectedSemester.end_date
                }}。匯入狀態以此學期為準。
            </p>
            <p v-else class="mt-3 text-sm text-amber-500">
                請先選擇學期；若清單為空，請至系統設定建立。
            </p>
        </div>

        <p
            v-if="errorMessage"
            role="alert"
            class="rounded-xl border border-red-500/25 bg-red-500/5 p-4 text-sm text-red-500"
        >
            {{ errorMessage }}
        </p>
        <p
            v-if="successMessage"
            role="status"
            class="rounded-xl border border-emerald-500/25 bg-emerald-500/5 p-4 text-sm text-emerald-500"
        >
            {{ successMessage }}
        </p>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h4 class="font-semibold">
                1. 選擇教室
                <span class="text-sm font-normal text-a-text-muted"
                    >顯示 {{ filteredClassrooms.length }}／{{
                        classrooms.length
                    }}
                    間</span
                >
            </h4>
            <div class="flex gap-3 text-sm">
                <button
                    type="button"
                    :disabled="
                        busy || !semesterId || !filteredClassrooms.length
                    "
                    class="text-primary disabled:opacity-40"
                    @click="toggleVisibleSelection"
                >
                    {{
                        allVisibleSelected
                            ? '取消目前顯示的教室'
                            : '全選目前顯示的教室'
                    }}
                </button>
                <button
                    type="button"
                    :disabled="busy || !selectedClassroomIds.length"
                    class="text-a-text-muted disabled:opacity-40"
                    @click="clearSelectedClassrooms"
                >
                    清空全部選取
                </button>
            </div>
        </div>
        <div
            v-if="selectedRooms.length"
            class="rounded-xl border border-primary/20 bg-primary/5 p-3"
        >
            <p class="mb-2 text-xs text-a-text-muted">
                已選 {{ selectedRooms.length }} 間（包含篩選後隱藏的教室）
            </p>
            <div class="flex max-h-28 flex-wrap gap-2 overflow-y-auto">
                <button
                    v-for="room in selectedRooms"
                    :key="room.id"
                    type="button"
                    :disabled="busy"
                    :aria-label="`取消選取 ${room.code}`"
                    class="rounded-full border border-primary/20 bg-a-surface px-3 py-1 text-xs text-primary disabled:opacity-50"
                    @click="toggleClassroomSelection(room)"
                >
                    {{ room.code }} ×
                </button>
            </div>
        </div>
        <div class="space-y-3">
            <ImportBuildingPanel
                v-for="buildingCode in visibleBuildings"
                :key="buildingCode"
                :building-code="buildingCode"
                :building-label="buildingLabels[buildingCode] ?? buildingCode"
                :rooms="classroomsByBuilding[buildingCode]"
                :selected-classroom-set="selectedClassroomSet"
                :disabled="busy || !semesterId"
                @select-all="selectAllInBuilding"
                @toggle-room="toggleClassroomSelection"
            />
            <p
                v-if="!filteredClassrooms.length"
                class="rounded-xl border border-dashed border-a-border-2 p-8 text-center text-sm text-a-text-muted"
            >
                沒有符合條件的教室。<button
                    type="button"
                    class="ml-2 text-primary"
                    @click="
                        roomSearch = '';
                        buildingFilter = '';
                        statusFilter = '';
                    "
                >
                    清除篩選
                </button>
            </p>
        </div>

        <section
            v-if="previewLoading || isAwaitingImportConfirmation || previewError"
            ref="previewSection"
            class="scroll-mt-6 space-y-4 rounded-2xl border border-a-border-card bg-a-surface p-5 sm:p-6"
        >
            <h4 class="font-semibold">2. 預覽與確認</h4>
            <p v-if="errorMessage" role="alert" class="text-sm text-red-500">{{ errorMessage }}</p>
            <p
                v-if="previewLoading"
                role="status"
                class="animate-pulse text-sm text-a-text-muted"
            >
                正在向學校取得
                {{
                    selectedClassroomIds.length
                }}
                間教室的課表，可能需要一些時間…
            </p>
            <template v-else-if="isAwaitingImportConfirmation">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm">
                        {{ selectedSemester?.label }} ·
                        {{ previewRoomCount }} 間教室 ·
                        {{ previewSchedules.length }} 筆課表
                    </p>
                    <button
                        type="button"
                        :disabled="busy"
                        class="text-sm text-primary disabled:opacity-40"
                        @click="previewImport"
                    >
                        重新取得預覽
                    </button>
                </div>
                <p class="text-sm text-a-text-muted">
                    確認後將更新所選教室本學期的匯入課表，包含先前的人工修改；手動借用會保留。
                </p>
                <p
                    v-if="roomsWithoutSchedules.length"
                    class="rounded-lg bg-amber-500/10 p-3 text-sm text-amber-600"
                >
                    以下教室未取得課表，會保留原紀錄：{{
                        roomsWithoutSchedules
                            .map((room) => room.code)
                            .join('、')
                    }}
                </p>
                <ImportPreviewTable
                    :classrooms="classrooms"
                    :preview-schedules="previewSchedules"
                />
            </template>
        </section>

        <div
            class="sticky bottom-3 z-20 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-a-border-2 bg-a-surface p-4 shadow-lg"
        >
            <div>
                <p class="text-sm font-semibold">
                    {{ selectedSemester?.label || '尚未選擇學期' }} · 已選
                    {{ selectedClassroomIds.length }} 間
                </p>
                <p class="mt-1 text-xs text-a-text-muted">
                    {{
                        importForm.processing
                            ? '正在匯入，請稍候…'
                            : isAwaitingImportConfirmation
                              ? '請確認上方預覽後匯入。'
                              : '選好教室後，先取得課表預覽。'
                    }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    v-if="previewLoading"
                    type="button"
                    class="text-sm text-a-text-muted"
                    @click="cancelPreview"
                >
                    取消預覽
                </button>
                <button
                    type="button"
                    :disabled="
                        !semesterId || busy || !selectedClassroomIds.length
                    "
                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40"
                    @click="
                        isAwaitingImportConfirmation
                            ? submitImport()
                            : handleImportAction()
                    "
                >
                    {{
                        importForm.processing
                            ? '匯入中…'
                            : previewLoading
                              ? '取得課表中…'
                              : isAwaitingImportConfirmation
                                ? `確認匯入 ${previewRoomCount} 間教室`
                                : '取得課表預覽'
                    }}
                </button>
            </div>
        </div>
        <details class="rounded-xl border border-a-border-2 p-4">
            <summary class="cursor-pointer text-sm text-a-text-muted">
                撤回已匯入課表
            </summary>
            <p class="my-3 text-sm text-a-text-muted">
                僅撤回已選教室在
                {{
                    selectedSemester?.label || '所選學期'
                }}
                的匯入課表，手動借用會保留。
            </p>
            <button
                type="button"
                :disabled="
                    !semesterId || busy || !selectedImportedClassrooms.length
                "
                class="rounded-lg border border-red-500/30 px-4 py-2 text-sm text-red-500 disabled:opacity-40"
                @click="revokeSelectedImports"
            >
                {{
                    revokingImports
                        ? '撤回中…'
                        : `撤回 ${selectedImportedClassrooms.length} 間已匯入教室`
                }}
            </button>
        </details>
    </section>
</template>
<script setup lang="ts">
import { ImportBuildingPanel, ImportPreviewTable } from '@/components/admin';
import { requestError } from '@/composables/useLongTermRecords';
import type {
    BuildingCode,
    BuildingOption,
    ClassroomOption,
    PreviewSchedule,
    SemesterOption,
} from '@/types';
import { getRoomBuildingCode, withBase } from '@/utils';
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    classrooms: ClassroomOption[];
    buildingOptions: BuildingOption[];
    semesters: SemesterOption[];
    defaultSemesterId: number | null;
}>();

const roomSearch = ref('');
const buildingFilter = ref('');
const statusFilter = ref('');
const successMessage = ref('');
const previewSection = ref<HTMLElement | null>(null);
let previewController: AbortController | null = null;
const semesterId = ref<number | ''>(props.defaultSemesterId ?? '');
const selectedSemester = computed(() =>
    props.semesters.find((s) => s.id === Number(semesterId.value)),
);
const classrooms = computed(() =>
    props.classrooms.map((room) => ({
        ...room,
        has_imported:
            room.imported_semester_ids?.includes(Number(semesterId.value)) ??
            false,
    })),
);
let previewVersion = 0;
onBeforeUnmount(() => {
    previewVersion++;
    previewController?.abort();
});
watch(semesterId, () => {
    clearSelectedClassrooms();
});

const selectedClassroomIds = ref<number[]>([]);
const importErrorMessage = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const previewSchedules = ref<PreviewSchedule[]>([]);
const isAwaitingImportConfirmation = ref(false);
const revokingImports = ref(false);

const buildingOrder = computed<BuildingCode[]>(() =>
    props.buildingOptions.map((item) => item.code),
);
const buildingLabels = computed<Record<string, string>>(() => {
    return props.buildingOptions.reduce<Record<string, string>>((acc, item) => {
        acc[item.code] = item.label;
        return acc;
    }, {});
});

const importForm = useForm<{
    classroom_ids: number[];
    semester_id: number | '';
}>({
    semester_id: semesterId.value,
    classroom_ids: [],
});

const selectedClassroomSet = computed(
    () => new Set(selectedClassroomIds.value.map((id) => Number(id))),
);
const selectedImportedClassrooms = computed(() => {
    return classrooms.value.filter(
        (room) => selectedClassroomSet.value.has(room.id) && room.has_imported,
    );
});

const busy = computed(
    () =>
        previewLoading.value || importForm.processing || revokingImports.value,
);
const errorMessage = computed(
    () =>
        importErrorMessage.value ||
        importForm.errors.classroom_ids ||
        importServerError.value ||
        previewError.value,
);
const selectedRooms = computed(() =>
    classrooms.value.filter((room) => selectedClassroomSet.value.has(room.id)),
);
const filteredClassrooms = computed(() =>
    classrooms.value.filter((room) => {
        const query = roomSearch.value.trim().toLocaleLowerCase();
        return (
            (!query ||
                `${room.code} ${room.name}`
                    .toLocaleLowerCase()
                    .includes(query)) &&
            (!buildingFilter.value ||
                getRoomBuildingCode(room) === buildingFilter.value) &&
            (!statusFilter.value ||
                (statusFilter.value === 'imported'
                    ? room.has_imported
                    : !room.has_imported))
        );
    }),
);
const visibleBuildings = computed(() =>
    Object.keys(classroomsByBuilding.value).filter(
        (code) => classroomsByBuilding.value[code].length,
    ),
);
const allVisibleSelected = computed(
    () =>
        filteredClassrooms.value.length > 0 &&
        filteredClassrooms.value.every((room) =>
            selectedClassroomSet.value.has(room.id),
        ),
);
const previewRoomCount = computed(
    () => new Set(previewSchedules.value.map((row) => row.classroom_id)).size,
);
const roomsWithoutSchedules = computed(() =>
    selectedRooms.value.filter(
        (room) =>
            !previewSchedules.value.some((row) => row.classroom_id === room.id),
    ),
);
function toggleVisibleSelection() {
    if (busy.value || !semesterId.value) return;
    const ids = new Set(filteredClassrooms.value.map((room) => room.id));
    selectedClassroomIds.value = allVisibleSelected.value
        ? selectedClassroomIds.value.filter((id) => !ids.has(id))
        : [...new Set([...selectedClassroomIds.value, ...ids])];
}
function cancelPreview() {
    previewController?.abort();
    previewVersion++;
    previewLoading.value = false;
}
async function revealPreview() {
    await nextTick();
    previewSection.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}

const classroomsByBuilding = computed<Record<string, ClassroomOption[]>>(() => {
    const grouped: Record<string, ClassroomOption[]> = {};

    for (const code of buildingOrder.value) {
        grouped[code] = filteredClassrooms.value.filter(
            (room) => getRoomBuildingCode(room) === code,
        );
    }

    return grouped;
});

const importServerError = computed(() => {
    const errors = importForm.errors as Record<string, string | undefined>;
    return errors.semester_id || errors.conflict || errors.import || '';
});

watch(selectedClassroomIds, () => {
    previewController?.abort();
    previewVersion++;
    previewLoading.value = false;
    previewSchedules.value = [];
    previewError.value = '';
    importForm.clearErrors();
    isAwaitingImportConfirmation.value = false;
});

function toggleClassroomSelection(room: ClassroomOption) {
    if (busy.value) return;
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
    if (busy.value) return;
    importErrorMessage.value = '';
    const ids = classroomsByBuilding.value[buildingCode].map((room) => room.id);
    const allSelected = ids.every((id) => selectedClassroomSet.value.has(id));
    selectedClassroomIds.value = allSelected
        ? selectedClassroomIds.value.filter((id) => !ids.includes(id))
        : Array.from(new Set([...selectedClassroomIds.value, ...ids]));
}

function clearSelectedClassrooms() {
    if (busy.value) return;
    previewVersion++;
    previewLoading.value = false;
    selectedClassroomIds.value = [];
    importErrorMessage.value = '';
    previewSchedules.value = [];
    previewError.value = '';
    isAwaitingImportConfirmation.value = false;
}

async function previewImport() {
    if (busy.value) return;
    importForm.clearErrors();
    successMessage.value = '';
    previewController?.abort();
    previewController = new AbortController();
    if (!semesterId.value) {
        previewError.value = '請先選擇學期。';
        return;
    }
    const version = ++previewVersion;
    importErrorMessage.value = '';
    previewError.value = '';
    if (selectedClassroomIds.value.length === 0) {
        previewError.value = '請至少選擇一間教室。';
        return;
    }
    previewLoading.value = true;
    void revealPreview();
    previewSchedules.value = [];
    isAwaitingImportConfirmation.value = false;
    try {
        const payloadIds = selectedClassroomIds.value.map((id) => Number(id));
        const response = await window.axios.post(
            withBase('/admin/long-term-borrowing/preview'),
            {
                semester_id: semesterId.value,
                classroom_ids: payloadIds,
            },
            { signal: previewController.signal },
        );
        if (version !== previewVersion) return;
        const schedules = (response?.data?.schedules ??
            []) as PreviewSchedule[];
        previewSchedules.value = schedules;
        if (schedules.length === 0) {
            previewError.value = '預覽成功，但未取得可匯入課表。';
            return;
        }
        isAwaitingImportConfirmation.value = true;
        void revealPreview();
    } catch (error: unknown) {
        if (version === previewVersion)
            previewError.value = requestError(error);
    } finally {
        if (version === previewVersion) previewLoading.value = false;
    }
}

async function handleImportAction() {
    importErrorMessage.value = '';

    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }

    if (
        isAwaitingImportConfirmation.value &&
        previewSchedules.value.length > 0
    ) {
        void revealPreview();
        return;
    }

    await previewImport();
}

function submitImport() {
    if (busy.value || !isAwaitingImportConfirmation.value) return;
    importErrorMessage.value = '';
    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }
    if (!semesterId.value || previewSchedules.value.length === 0) {
        importErrorMessage.value = '請先完成課表預覽，再進行匯入。';
        return;
    }
    importForm.semester_id = semesterId.value;
    importForm.classroom_ids = selectedClassroomIds.value.map((id) =>
        Number(id),
    );
    importForm.post(withBase('/admin/long-term-borrowing/import'), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = `${selectedSemester.value?.label} 課表匯入完成。`;
            selectedClassroomIds.value = [];
            previewSchedules.value = [];
            previewError.value = '';
            isAwaitingImportConfirmation.value = false;
        },
        onError: (errors) => {
            importErrorMessage.value =
                Object.values(errors).join('；') || '匯入失敗，請稍後再試。';
            isAwaitingImportConfirmation.value = true;
            void revealPreview();
        },
    });
}

async function revokeSelectedImports() {
    if (busy.value || !semesterId.value) return;
    successMessage.value = '';
    const targetRooms = selectedImportedClassrooms.value;
    if (targetRooms.length === 0) {
        importErrorMessage.value = '請先勾選至少一間已匯入教室。';
        return;
    }

    const roomCodes = targetRooms.map((room) => room.code).join('、');
    if (
        !confirm(
            `確定要撤回以下教室的課表匯入嗎？\n${roomCodes}\n此操作將刪除其 ${selectedSemester.value?.label} 所有匯入記錄。`,
        )
    ) {
        return;
    }

    revokingImports.value = true;
    importErrorMessage.value = '';

    const failedCodes: string[] = [];
    for (const room of targetRooms) {
        try {
            await window.axios.delete(
                withBase(`/admin/long-term-borrowing/import/${room.id}`),
                { data: { semester_id: semesterId.value } },
            );
        } catch {
            failedCodes.push(room.code);
        }
    }

    revokingImports.value = false;

    if (failedCodes.length > 0) {
        importErrorMessage.value = `部分撤回失敗：${failedCodes.join('、')}`;
    }

    const successfulIds = targetRooms
        .filter((room) => !failedCodes.includes(room.code))
        .map((room) => room.id);
    selectedClassroomIds.value = selectedClassroomIds.value.filter(
        (id) => !successfulIds.includes(id),
    );
    if (successfulIds.length)
        successMessage.value = `已撤回 ${successfulIds.length} 間教室的匯入課表。`;
    router.reload({ only: ['classrooms'] });
}
</script>

<style scoped>
.field {
    display: block;
    width: 100%;
    margin-top: 0.5rem;
    border: 1px solid var(--color-a-border-2);
    border-radius: 0.65rem;
    padding: 0.65rem 0.75rem;
    background: var(--color-a-surface);
    font-weight: 400;
}
.field:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}
</style>
