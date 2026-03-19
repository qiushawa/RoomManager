<template>
    <Head title="長期借用管理 | Admin" />
    <AdminLayout title="長期借用管理">
        <div class="mx-auto flex w-full max-w-[1380px] flex-col gap-6 pb-8">

            <!-- 成功訊息 -->
            <p
                v-if="$page.props.flash?.success"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-sm text-emerald-400"
            >
                {{ $page.props.flash.success }}
            </p>

            <!-- 模式切換 Tab -->
            <div class="flex gap-1 border-b border-a-border-2">
                <button
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'manual'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'manual'"
                >
                    手動新增
                </button>
                <button
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="activeMode === 'import'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-a-text-muted hover:text-a-text'"
                    @click="activeMode = 'import'"
                >
                    教室課表匯入
                </button>
            </div>

            <!-- ── 手動新增 ── -->
            <section v-if="activeMode === 'manual'" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]">

                <!-- 表單 -->
                <div class="rounded-2xl border border-a-border-card bg-a-surface p-6">
                    <h3 class="mb-5 text-sm font-semibold text-a-text">新增長期借用記錄</h3>

                    <!-- 後端錯誤 -->
                    <p v-if="manualForm.errors.semester || manualForm.errors.periods" class="mb-4 rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs text-red-400">
                        {{ manualForm.errors.semester || manualForm.errors.periods }}
                    </p>

                    <form class="space-y-5" @submit.prevent="submitManual">

                        <!-- 借用類型 -->
                        <div>
                            <label class="mb-2 block text-xs font-medium text-a-text-muted">借用類型</label>
                            <div class="flex gap-4">
                                <label
                                    v-for="opt in borrowTypeOptions"
                                    :key="opt.value"
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="radio"
                                        :value="opt.value"
                                        v-model="manualForm.borrow_type"
                                        class="h-3.5 w-3.5 shrink-0 border-a-border-2 bg-a-input text-primary focus:ring-primary/30"
                                    />
                                    <span class="text-sm text-a-text-body">{{ opt.label }}</span>
                                </label>
                            </div>
                            <p v-if="manualForm.errors.borrow_type" class="mt-1 text-xs text-red-400">{{ manualForm.errors.borrow_type }}</p>
                        </div>

                        <!-- 教室選擇 -->
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">教室</label>
                            <select
                                v-model="manualForm.classroom_id"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            >
                                <option value="">— 選擇教室 —</option>
                                <option v-for="room in classrooms" :key="room.id" :value="room.id">
                                    {{ room.code }} — {{ room.name }}
                                </option>
                            </select>
                            <p v-if="manualForm.errors.classroom_id" class="mt-1 text-xs text-red-400">{{ manualForm.errors.classroom_id }}</p>
                        </div>

                        <!-- 指導老師 -->
                        <div>
                            <label class="mb-1 block text-xs text-a-text-muted">指導老師</label>
                            <input
                                v-model.trim="manualForm.teacher_name"
                                type="text"
                                placeholder="如 王大明"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                            <p v-if="manualForm.errors.teacher_name" class="mt-1 text-xs text-red-400">{{ manualForm.errors.teacher_name }}</p>
                        </div>

                        <!-- 課程名稱（僅課程使用時顯示） -->
                        <div v-if="manualForm.borrow_type === 2">
                            <label class="mb-1 block text-xs text-a-text-muted">課程名稱</label>
                            <input
                                v-model.trim="manualForm.course_name"
                                type="text"
                                placeholder="如 資料結構"
                                class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body placeholder-a-text-dim outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                            />
                            <p v-if="manualForm.errors.course_name" class="mt-1 text-xs text-red-400">{{ manualForm.errors.course_name }}</p>
                        </div>

                        <!-- 借用星期（多選） -->
                        <div>
                            <label class="mb-2 block text-xs font-medium text-a-text-muted">借用星期（可多選）</label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="day in weekdayOptions"
                                    :key="day.value"
                                    class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                    :class="manualForm.day_of_week.includes(day.value)
                                        ? 'border-primary/40 bg-primary/10 text-primary'
                                        : 'border-a-divider text-a-text-muted hover:bg-a-surface-hover'"
                                >
                                    <input
                                        type="checkbox"
                                        :value="day.value"
                                        v-model="manualForm.day_of_week"
                                        class="hidden"
                                    />
                                    {{ day.label }}
                                </label>
                            </div>
                            <p v-if="manualForm.errors.day_of_week" class="mt-1 text-xs text-red-400">{{ manualForm.errors.day_of_week }}</p>
                        </div>

                        <!-- 開始/結束日期 -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs text-a-text-muted">開始日期</label>
                                <input
                                    v-model="manualForm.start_date"
                                    type="date"
                                    class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                                />
                                <p v-if="manualForm.errors.start_date" class="mt-1 text-xs text-red-400">{{ manualForm.errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-a-text-muted">結束日期</label>
                                <input
                                    v-model="manualForm.end_date"
                                    type="date"
                                    class="w-full rounded-lg border border-a-border-2 bg-a-input px-3 py-2 text-sm text-a-text-body outline-none transition focus:border-primary/50 focus:ring-1 focus:ring-primary/30"
                                />
                                <p v-if="manualForm.errors.end_date" class="mt-1 text-xs text-red-400">{{ manualForm.errors.end_date }}</p>
                            </div>
                        </div>

                        <!-- 節數（勾選） -->
                        <div>
                            <label class="mb-2 block text-xs font-medium text-a-text-muted">節數（可多選）</label>
                            <div class="flex flex-wrap gap-1.5">
                                <label
                                    v-for="(slot, idx) in timeSlots"
                                    :key="slot.id"
                                    class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                                    :class="manualForm.periods.includes(idx + 1)
                                        ? 'border-primary/40 bg-primary/10 text-primary'
                                        : 'border-a-divider text-a-text-muted hover:bg-a-surface-hover'"
                                >
                                    <input
                                        type="checkbox"
                                        :value="idx + 1"
                                        v-model="manualForm.periods"
                                        class="hidden"
                                    />
                                    第 {{ idx + 1 }} 節
                                    <span class="text-[10px] opacity-60">{{ slot.name }}</span>
                                </label>
                            </div>
                            <p v-if="manualForm.errors.periods" class="mt-1 text-xs text-red-400">{{ manualForm.errors.periods }}</p>
                        </div>

                        <!-- 送出 -->
                        <div class="flex justify-end pt-1">
                            <button
                                type="submit"
                                :disabled="manualForm.processing"
                                class="rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ manualForm.processing ? '新增中...' : '新增記錄' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 已儲存記錄清單 -->
                <div class="rounded-2xl border border-a-border-card bg-a-surface">
                    <div class="flex items-center justify-between border-b border-a-divider px-4 py-3">
                        <span class="text-sm font-medium text-a-text">已儲存記錄</span>
                        <span class="rounded-full bg-a-badge px-2 py-0.5 text-xs text-a-text-2">{{ manualRecords.length }}</span>
                    </div>

                    <div v-if="manualRecords.length === 0" class="px-4 py-12 text-center text-xs text-a-text-dim">
                        尚無資料
                    </div>

                    <ul v-else class="divide-y divide-a-divider max-h-[620px] overflow-auto">
                        <li
                            v-for="record in manualRecords"
                            :key="record.id"
                            class="px-4 py-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <!-- 教室 + 類型 badge -->
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-a-text truncate">{{ record.classroom_code }}</p>
                                        <span
                                            class="shrink-0 rounded border px-1.5 py-0.5 text-[10px]"
                                            :class="record.borrow_type === 1
                                                ? 'border-blue-400/30 bg-blue-500/10 text-blue-300'
                                                : 'border-violet-400/30 bg-violet-500/10 text-violet-300'"
                                        >
                                            {{ record.borrow_type === 1 ? '一般' : '課程' }}
                                        </span>
                                    </div>
                                    <!-- 星期 -->
                                    <p class="mt-0.5 text-xs text-a-text-muted">{{ weekdayLabel(record.day_of_week) }}</p>
                                    <!-- 日期範圍 -->
                                    <p class="text-xs text-a-text-dim">{{ record.start_date }} ~ {{ record.end_date }}</p>
                                    <!-- 老師 / 課程 -->
                                    <p class="mt-0.5 text-xs text-a-text-muted truncate">
                                        {{ record.teacher_name }}
                                        <span v-if="record.course_name">｜{{ record.course_name }}</span>
                                    </p>
                                    <!-- 節次 -->
                                    <p class="text-xs text-a-text-dim">{{ record.start_slot }} — {{ record.end_slot }}</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- ── 教室課表匯入 ── -->
            <section v-else class="flex flex-col gap-5">

                <!-- 錯誤訊息 -->
                <p
                    v-if="importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError"
                    class="rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs text-red-400"
                >
                    {{ importErrorMessage || importForm.errors.classroom_ids || importServerError || previewError }}
                </p>

                <!-- 大樓教室選擇 -->
                <div class="space-y-4">
                    <div
                        v-for="buildingCode in buildingOrder"
                        :key="buildingCode"
                        class="rounded-2xl border border-a-border-card bg-a-surface"
                    >
                        <!-- 大樓標題列 -->
                        <div class="flex items-center justify-between border-b border-a-divider px-5 py-3">
                            <div class="flex items-center gap-3">
                                <h4 class="text-sm font-semibold text-a-text">{{ buildingLabels[buildingCode] }}</h4>
                                <span class="text-xs text-a-text-dim">{{ classroomsByBuilding[buildingCode].length }} 間</span>
                            </div>
                            <button
                                type="button"
                                class="rounded-md border border-a-border-2 px-3 py-1 text-xs text-a-text-2 transition-colors hover:bg-a-surface-hover disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="classroomsByBuilding[buildingCode].length === 0 || (selectedBuildingCode !== null && selectedBuildingCode !== buildingCode)"
                                @click="selectAllInBuilding(buildingCode)"
                            >
                                全選
                            </button>
                        </div>

                        <!-- 教室格狀列表 -->
                        <div v-if="classroomsByBuilding[buildingCode].length === 0" class="px-5 py-6 text-center text-xs text-a-text-dim">
                            此大樓目前沒有可匯入教室
                        </div>

                        <div v-else class="grid grid-cols-2 gap-2 p-4 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                            <div
                                v-for="room in classroomsByBuilding[buildingCode]"
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
                                        @change="toggleClassroomSelection(room)"
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

                                <!-- 撤回按鈕（在已匯入卡片下方） -->
                                <button
                                    v-if="room.has_imported"
                                    type="button"
                                    class="w-full rounded border border-red-500/30 bg-red-500/8 px-2 py-1 text-[11px] text-red-400 transition-colors hover:bg-red-500/20"
                                    @click="revokeImport(room)"
                                >
                                    撤回匯入
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 工具列 + 動作按鈕 -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-a-border-2 pt-4">
                    <button
                        type="button"
                        class="text-xs text-a-text-muted underline-offset-2 hover:text-a-text hover:underline"
                        @click="clearSelectedClassrooms"
                    >
                        清空選取
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="previewLoading || selectedClassroomIds.length === 0"
                            class="rounded-lg border border-primary/40 bg-primary/10 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary/20 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="previewImport"
                        >
                            {{ previewLoading ? '預覽中...' : `預覽 ${selectedClassroomIds.length} 間課表` }}
                        </button>
                        <button
                            type="button"
                            :disabled="importForm.processing || previewSchedules.length === 0"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitImport"
                        >
                            {{ importForm.processing ? '匯入中...' : `確認匯入` }}
                        </button>
                    </div>
                </div>

                <!-- 預覽結果 -->
                <div v-if="previewSchedules.length > 0" class="overflow-hidden rounded-xl border border-emerald-500/20 bg-emerald-500/5">
                    <div class="border-b border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400">
                        預覽：{{ previewSchedules.length }} 筆課表 — 確認後才會正式匯入
                    </div>
                    <div class="max-h-80 overflow-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-a-surface-2">
                                <tr class="text-left text-xs text-a-text-muted">
                                    <th class="px-4 py-2 font-medium">教室</th>
                                    <th class="px-4 py-2 font-medium">星期</th>
                                    <th class="px-4 py-2 font-medium">節次</th>
                                    <th class="px-4 py-2 font-medium">課程</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-a-divider">
                                <tr
                                    v-for="(row, index) in previewSchedules"
                                    :key="`${row.classroom_id}-${row.day_of_week}-${row.start_slot_id}-${row.end_slot_id}-${index}`"
                                    class="text-xs"
                                >
                                    <td class="px-4 py-2.5 font-medium text-a-text">{{ getClassroomLabel(row.classroom_id) }}</td>
                                    <td class="px-4 py-2.5 text-a-text-muted">{{ weekdayLabel(row.day_of_week) }}</td>
                                    <td class="px-4 py-2.5 text-a-text-muted">{{ row.start_slot_id }}–{{ row.end_slot_id }} 節</td>
                                    <td class="px-4 py-2.5 text-a-text-muted">{{ row.course_name || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p v-else class="text-center text-xs text-a-text-dim">
                    請先選擇教室後點擊預覽。
                </p>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

type ImportConfig = {
    year: number;
    seme: number;
    category: string;
    building: string;
};

type BuildingCode = 'CB' | 'GC' | 'RA';

type ClassroomOption = {
    id: number;
    code: string;
    name: string;
    has_imported?: boolean;
    building_code?: BuildingCode | null;
};

type TimeSlotOption = {
    id: number;
    name: string;
};

type ManualRecord = {
    id: number;
    classroom_code: string;
    classroom_name: string;
    borrow_type: number;
    teacher_name: string;
    course_name: string;
    day_of_week: number;
    start_slot: string;
    end_slot: string;
    start_date: string | null;
    end_date: string | null;
};

type PreviewSchedule = {
    classroom_id: number;
    start_slot_id: number;
    end_slot_id: number;
    day_of_week: number;
    course_name: string;
};

const props = defineProps<{
    classrooms: ClassroomOption[];
    timeSlots: TimeSlotOption[];
    manualRecords: ManualRecord[];
    importConfig: ImportConfig;
}>();

// ── 常數 ──────────────────────────────────────────────
const buildingOrder: BuildingCode[] = ['CB', 'GC', 'RA'];
const buildingLabels: Record<BuildingCode, string> = {
    CB: '跨領域',
    GC: '綜三館',
    RA: '科研大樓',
};

const borrowTypeOptions = [
    { value: 1, label: '一般借用' },
    { value: 2, label: '課程使用' },
];

const weekdayOptions = [
    { value: 1, label: '週一' },
    { value: 2, label: '週二' },
    { value: 3, label: '週三' },
    { value: 4, label: '週四' },
    { value: 5, label: '週五' },
    { value: 6, label: '週六' },
    { value: 7, label: '週日' },
];

// ── 狀態 ──────────────────────────────────────────────
const activeMode = ref<'manual' | 'import'>('manual');
const selectedClassroomIds = ref<number[]>([]);
const importErrorMessage = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const previewSchedules = ref<PreviewSchedule[]>([]);

// ── 手動表單 ──────────────────────────────────────────
const manualForm = useForm<{
    borrow_type: number;
    classroom_id: number | '';
    teacher_name: string;
    course_name: string;
    day_of_week: number[];
    start_date: string;
    end_date: string;
    periods: number[];
}>({
    borrow_type: 1,
    classroom_id: '',
    teacher_name: '',
    course_name: '',
    day_of_week: [],
    start_date: '',
    end_date: '',
    periods: [],
});

function submitManual() {
    manualForm.post('/admin/long-term-borrowing/manual', {
        preserveScroll: true,
        onSuccess: () => {
            manualForm.reset();
            manualForm.borrow_type = 1;
        },
    });
}

// ── 匯入功能 ──────────────────────────────────────────
const importForm = useForm<{ classroom_ids: number[] }>({
    classroom_ids: [],
});

const selectedClassroomSet = computed(() => new Set(selectedClassroomIds.value.map((id) => Number(id))));

const selectedClassrooms = computed(() => {
    const selected = selectedClassroomSet.value;
    return props.classrooms.filter((room) => selected.has(room.id));
});

const selectedBuildingCode = computed<BuildingCode | null>(() => {
    const selected = selectedClassrooms.value;
    if (selected.length === 0) return null;
    const codes = new Set<BuildingCode>();
    for (const room of selected) {
        const code = getRoomBuildingCode(room);
        if (!code) return null;
        codes.add(code);
    }
    if (codes.size !== 1) return null;
    return Array.from(codes)[0];
});

const classroomsByBuilding = computed<Record<BuildingCode, ClassroomOption[]>>(() => ({
    CB: props.classrooms.filter((room) => getRoomBuildingCode(room) === 'CB'),
    GC: props.classrooms.filter((room) => getRoomBuildingCode(room) === 'GC'),
    RA: props.classrooms.filter((room) => getRoomBuildingCode(room) === 'RA'),
}));

const importServerError = computed(() => {
    const errors = importForm.errors as Record<string, string | undefined>;
    return errors.import ?? '';
});

const classroomLabelMap = computed(() => {
    const map = new Map<number, string>();
    props.classrooms.forEach((room) => map.set(room.id, `${room.code} - ${room.name}`));
    return map;
});

watch(selectedClassroomIds, () => {
    previewSchedules.value = [];
    previewError.value = '';
});

// ── 工具函式 ──────────────────────────────────────────
function inferBuildingCode(code: string): BuildingCode | null {
    const upper = String(code).toUpperCase();
    if (upper.includes('CB')) return 'CB';
    if (upper.includes('GC')) return 'GC';
    if (upper.includes('RA')) return 'RA';
    return null;
}

function getRoomBuildingCode(room: ClassroomOption): BuildingCode | null {
    if (room.building_code === 'CB' || room.building_code === 'GC' || room.building_code === 'RA') {
        return room.building_code;
    }
    return inferBuildingCode(room.code);
}

function toggleClassroomSelection(room: ClassroomOption) {
    importErrorMessage.value = '';
    const targetBuilding = getRoomBuildingCode(room);
    if (!targetBuilding) {
        importErrorMessage.value = '教室代碼無法判斷大樓，僅支援 CB、GC、RA。';
        return;
    }
    const selected = new Set(selectedClassroomIds.value);
    if (selected.has(room.id)) {
        selected.delete(room.id);
        selectedClassroomIds.value = Array.from(selected);
        return;
    }
    const currentBuilding = selectedBuildingCode.value;
    if (currentBuilding && currentBuilding !== targetBuilding) {
        importErrorMessage.value = '只能批量匯入同一大樓教室，請先清空目前選取。';
        return;
    }
    selected.add(room.id);
    selectedClassroomIds.value = Array.from(selected);
}

function selectAllInBuilding(buildingCode: BuildingCode) {
    importErrorMessage.value = '';
    const currentBuilding = selectedBuildingCode.value;
    if (currentBuilding && currentBuilding !== buildingCode) {
        importErrorMessage.value = '只能批量匯入同一大樓教室，請先清空目前選取。';
        return;
    }
    const ids = classroomsByBuilding.value[buildingCode].map((room) => room.id);
    selectedClassroomIds.value = Array.from(new Set([...selectedClassroomIds.value, ...ids]));
}

function clearSelectedClassrooms() {
    selectedClassroomIds.value = [];
    importErrorMessage.value = '';
}

function weekdayLabel(day: number): string {
    const labels = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
    return labels[day - 1] ?? `週${day}`;
}

function getClassroomLabel(classroomId: number): string {
    return classroomLabelMap.value.get(classroomId) ?? `教室 #${classroomId}`;
}

async function previewImport() {
    importErrorMessage.value = '';
    previewError.value = '';
    if (selectedClassroomIds.value.length === 0) {
        previewError.value = '請至少選擇一間教室。';
        return;
    }
    if (!selectedBuildingCode.value) {
        previewError.value = '請選擇同一大樓教室（CB、GC、RA）後再預覽。';
        return;
    }
    previewLoading.value = true;
    previewSchedules.value = [];
    try {
        const payloadIds = selectedClassroomIds.value.map((id) => Number(id));
        const response = await window.axios.post('/admin/long-term-borrowing/preview', {
            classroom_ids: payloadIds,
        });
        const schedules = (response?.data?.schedules ?? []) as PreviewSchedule[];
        previewSchedules.value = schedules;
        if (schedules.length === 0) {
            previewError.value = '預覽成功，但未取得可匯入課表。';
        }
    } catch (error: any) {
        const backendMessage =
            error?.response?.data?.errors?.import?.[0] ||
            error?.response?.data?.errors?.classroom_ids?.[0] ||
            error?.response?.data?.message;
        previewError.value = backendMessage || '預覽失敗，請確認匯入服務與參數設定。';
    } finally {
        previewLoading.value = false;
    }
}

function submitImport() {
    importErrorMessage.value = '';
    if (selectedClassroomIds.value.length === 0) {
        importErrorMessage.value = '請至少選擇一間教室。';
        return;
    }
    if (!selectedBuildingCode.value) {
        importErrorMessage.value = '請僅保留同一大樓教室再匯入。';
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
            previewSchedules.value = [];
        },
        onError: () => {
            importErrorMessage.value = '匯入失敗，請確認匯入服務與參數設定。';
        },
    });
}

function revokeImport(room: ClassroomOption) {
    if (!confirm(`確定要撤回「${room.code}」的課表匯入嗎？此操作將刪除該教室本學期所有匯入的課表記錄。`)) {
        return;
    }
    router.delete(`/admin/long-term-borrowing/import/${room.id}`, {
        preserveScroll: true,
    });
}
</script>
