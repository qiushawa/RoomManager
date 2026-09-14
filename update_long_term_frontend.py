from pathlib import Path
def edit(name, fn):
    p=Path(name); p.write_text(fn(p.read_text(encoding='utf-8')), encoding='utf-8')

def page(s):
    s=s.replace('已儲存記錄', '長期借用紀錄')
    start=s.index('            <section v-else-if="activeMode === \'records\'"')
    end=s.index('            <LongTermImportSection', start)
    s=s[:start]+'''            <LongTermRecordsSection v-else-if="activeMode === 'records'"
                :semesters="semesters" :default-semester-id="defaultSemesterId" :classrooms="recordClassrooms"
                :building-options="buildingOptions" :time-slots="timeSlots" @changed="router.reload({ only: ['classrooms'] })" />

'''+s[end:]
    s=s.replace(':building-options="buildingOptions"\n            />', ':building-options="buildingOptions"\n                :semesters="semesters" :default-semester-id="defaultSemesterId"\n            />')
    s=s.replace('LongTermImportSection, ManualRecordList', 'LongTermImportSection, LongTermRecordsSection')
    s=s.replace('    ManualRecord,', '    SemesterOption,')
    s=s.replace('    manualRecords: ManualRecord[];', '    semesters: SemesterOption[];\n    defaultSemesterId: number | null;\n    recordClassrooms: ClassroomOption[];')
    s=s.replace("const activeMode = ref<'manual' | 'import' | 'records'>('manual');", "const initialMode = new URL(window.location.href).searchParams.get('mode');\nconst activeMode = ref<'manual' | 'import' | 'records'>(initialMode === 'records' || initialMode === 'import' ? initialMode : 'manual');\nwatch(activeMode, (mode) => {\n    const url = new URL(window.location.href); url.searchParams.set('mode', mode);\n    window.history.replaceState(window.history.state, '', url);\n});")
    start=s.index('function revokeManualRecord(')
    s=s[:start]+s[s.index('</script>',start):]
    s=s.replace('<form class="space-y-8" @submit.prevent="handleManualSubmit">', '<form class="space-y-8" @submit.prevent="handleManualSubmit">\n                        <p v-if="manualConflictError" role="alert" class="text-red-500">{{ manualConflictError }}</p>')
    return s
edit('resources/js/Pages/Admin/LongTermBorrowing.vue',page)
edit('resources/js/components/admin/long-term-borrowing/index.ts',lambda s:s+"\nexport { default as LongTermRecordsSection } from './LongTermRecordsSection.vue';\n")
edit('resources/js/components/admin/index.ts',lambda s:s.replace('\tLongTermImportSection,','\tLongTermImportSection,\n\tLongTermRecordsSection,'))

def imports(s):
    s=s.replace('<section class="flex flex-col gap-5">', '''<section class="flex flex-col gap-5">
        <label class="block text-sm font-medium">匯入學期
            <select v-model="semesterId" :disabled="importForm.processing || revokingImports" class="mt-2 block rounded-lg border border-a-border-2 bg-a-surface px-3 py-2">
                <option value="">請選擇學期</option>
                <option v-for="semester in semesters" :key="semester.id" :value="semester.id">{{ semester.label }}（{{ semester.start_date }}～{{ semester.end_date }}）</option>
            </select>
        </label>
        <p v-if="!semesterId" class="text-amber-500">沒有預設學期，請選擇學期；若清單為空，請先至系統設定建立。</p>''')
    s=s.replace("import { computed, ref, watch }", "import { computed, ref, watch, onBeforeUnmount }")
    s=s.replace("import { getRoomBuildingCode, withBase }", "import { requestError } from '@/composables/useLongTermRecords';\nimport { getRoomBuildingCode, withBase }")
    s=s.replace('    PreviewSchedule,', '    PreviewSchedule,\n    SemesterOption,')
    s=s.replace('    buildingOptions: BuildingOption[];', '    buildingOptions: BuildingOption[];\n    semesters: SemesterOption[];\n    defaultSemesterId: number | null;')
    s=s.replace('const { isDark } = useAdminTheme();', '''const { isDark } = useAdminTheme();
const semesterId = ref<number | ''>(props.defaultSemesterId ?? '');
const selectedSemester = computed(() => props.semesters.find(s => s.id === Number(semesterId.value)));
const classrooms = computed(() => props.classrooms.map(room => ({ ...room, has_imported: room.imported_semester_ids?.includes(Number(semesterId.value)) ?? false })));
let previewVersion = 0;
onBeforeUnmount(() => { previewVersion++; });
watch(semesterId, () => { clearSelectedClassrooms(); });''')
    s=s.replace('props.classrooms.filter(', 'classrooms.value.filter(')
    s=s.replace('useForm<{ classroom_ids: number[] }>({', "useForm<{ classroom_ids: number[]; semester_id: number | '' }>({\n    semester_id: semesterId.value,")
    s=s.replace("return errors.import ?? '';", "return errors.semester_id || errors.conflict || errors.import || '';")
    s=s.replace('watch(selectedClassroomIds, () => {', 'watch(selectedClassroomIds, () => {\n    previewVersion++;\n    previewLoading.value = false;')
    s=s.replace(':disabled="previewLoading || importForm.processing || selectedClassroomIds.length === 0"', ':disabled="!semesterId || revokingImports || previewLoading || importForm.processing || selectedClassroomIds.length === 0"')
    s=s.replace(':disabled="revokingImports || selectedImportedClassrooms.length === 0"', ':disabled="!semesterId || previewLoading || importForm.processing || revokingImports || selectedImportedClassrooms.length === 0"')
    s=s.replace('共 {{ previewSchedules.length }} 筆，確認內容後再正式匯入。', '{{ selectedSemester?.label }} · 共 {{ previewSchedules.length }} 筆。重新匯入將覆寫所選教室的匯入課表及其人工修改，手動借用會保留。')
    s=s.replace('async function previewImport() {', "async function previewImport() {\n    if (!semesterId.value) { previewError.value = '請先選擇學期。'; return; }\n    const version = ++previewVersion;")
    s=s.replace("const response = await window.axios.post(withBase('/admin/long-term-borrowing/preview'), {", "const response = await window.axios.post(withBase('/admin/long-term-borrowing/preview'), {\n            semester_id: semesterId.value,")
    s=s.replace('        const schedules = (response', '        if (version !== previewVersion) return;\n        const schedules = (response')
    a=s.index('    } catch (error: any) {',s.index('async function previewImport'))
    b=s.index('\n}\n',a)
    s=s[:a]+'''    } catch (error: unknown) {
        if (version === previewVersion) previewError.value = requestError(error);
    } finally {
        if (version === previewVersion) previewLoading.value = false;
    }'''+s[b:]
    s=s.replace('    importForm.classroom_ids =', '    importForm.semester_id = semesterId.value;\n    importForm.classroom_ids =')
    s=s.replace('    if (previewSchedules.value.length === 0) {', '    if (!semesterId.value || previewSchedules.value.length === 0) {')
    s=s.replace('此操作將刪除其本學期所有匯入記錄。', '此操作將刪除其 ${selectedSemester.value?.label} 所有匯入記錄。')
    s=s.replace('await window.axios.delete(withBase(`/admin/long-term-borrowing/import/${room.id}`));', 'await window.axios.delete(withBase(`/admin/long-term-borrowing/import/${room.id}`), { data: { semester_id: semesterId.value } });')
    s=s.replace('function clearSelectedClassrooms() {', 'function clearSelectedClassrooms() {\n    previewVersion++; previewLoading.value = false;')
    return s
edit('resources/js/components/admin/long-term-borrowing/LongTermImportSection.vue',imports)
