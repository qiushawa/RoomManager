<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\PreviewManualLongTermBorrowingConflictsRequest;
use App\Http\Requests\Admin\StoreManualLongTermBorrowingRequest;
use App\Models\Booking;
use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\TimeSlot;
use App\Services\Admin\BookingRejectionService;
use App\Services\Admin\LongTermCourseScheduleService;
use App\Services\Admin\ManualLongTermBorrowingService;
use App\Services\Admin\ManualLongTermConflictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminLongTermBorrowingController extends Controller
{
    public function __construct(
        private readonly LongTermCourseScheduleService $longTermCourseScheduleService,
        private readonly ManualLongTermConflictService $manualLongTermConflictService,
        private readonly ManualLongTermBorrowingService $manualLongTermBorrowingService,
        private readonly BookingRejectionService $bookingRejectionService,
    ) {
    }

    public function longTermBorrowing(Request $request)
    {
        $currentSemester = Semester::findByDate(now());
        $importSemester = $this->longTermCourseScheduleService->resolveCurrentOrNearestFutureSemester();

        $hasImportedIds = [];
        if ($importSemester) {
            $hasImportedIds = CourseSchedule::where('semester_id', $importSemester->id)
                ->where('type', 'course')
                ->pluck('classroom_id')
                ->unique()
                ->toArray();
        }

        $classrooms = Classroom::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(function ($room) use ($hasImportedIds) {
                $room->has_imported = in_array($room->id, $hasImportedIds);
                $room->building_code = $this->longTermCourseScheduleService->extractBuildingCode((string) $room->code);

                return $room;
            });

        $timeSlots = TimeSlot::query()
            ->orderBy('start_time')
            ->get(['id', 'name']);

        $manualRecords = [];
        if ($currentSemester) {
            $manualRecords = CourseSchedule::with(['classroom', 'timeSlots'])
                ->where('semester_id', $currentSemester->id)
                ->whereIn('type', ['manual', 'borrowed'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($r) {
                    $slotNames = $r->timeSlots
                        ->sortBy('start_time')
                        ->pluck('name')
                        ->values();

                    return [
                        'id' => $r->id,
                        'classroom_code' => $r->classroom?->code ?? '—',
                        'classroom_name' => $r->classroom?->name ?? '—',
                        'type' => $r->type,
                        'teacher_name' => $r->teacher_name,
                        'course_name' => $r->course_name,
                        'day_of_week' => $r->day_of_week,
                        'start_slot' => $slotNames->first() ?? '—',
                        'end_slot' => $slotNames->last() ?? '—',
                        'start_date' => $r->start_date?->format('Y-m-d'),
                        'end_date' => $r->end_date?->format('Y-m-d'),
                    ];
                })
                ->toArray();
        }

        return Inertia::render('Admin/LongTermBorrowing', [
            'classrooms' => $classrooms,
            'timeSlots' => $timeSlots,
            'manualRecords' => $manualRecords,
            'semesterEndDate' => $currentSemester?->end_date?->format('Y-m-d'),
            'importConfig' => [
                'year' => (int) ($importSemester?->academic_year ?? Setting::get('course_import_year', (string) ($currentSemester?->academic_year ?? 114))),
                'seme' => (int) ($importSemester?->semester ?? Setting::get('course_import_seme', (string) ($currentSemester?->semester ?? 2))),
                'category' => (string) Setting::get('course_import_category', 'B'),
                'building' => (string) Setting::get('course_import_building', 'GC,粽三館'),
            ],
        ]);
    }

    public function previewCourseSchedules(Request $request)
    {
        $validated = $request->validate([
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        $classroomIds = collect($validated['classroom_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->get(['id', 'code', 'name']);

        if ($classrooms->isEmpty()) {
            return back()->withErrors(['classroom_ids' => '找不到可預覽的教室。']);
        }

        $semester = $this->longTermCourseScheduleService->resolveCurrentOrNearestFutureSemester();
        if (! $semester) {
            return back()->withErrors([
                'import' => '找不到目前或未來學期，請先建立學期資料。',
            ]);
        }

        $periodToSlotId = $this->longTermCourseScheduleService->buildPeriodToSlotIdMap();
        if (empty($periodToSlotId)) {
            return back()->withErrors(['import' => '時段資料不足，請先建立 time_slots。']);
        }

        try {
            $importedSchedules = $this->longTermCourseScheduleService->fetchImportedSchedulesForClassrooms(
                $semester,
                $classrooms,
                $periodToSlotId
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'import' => $e->getMessage(),
            ]);
        }

        $semesterStartDate = $semester->start_date?->format('Y-m-d');
        $semesterEndDate = $semester->end_date?->format('Y-m-d');

        return response()->json([
            'schedules' => collect($importedSchedules)->map(function ($item) {
                return [
                    'classroom_id' => $item['classroom_id'],
                    'time_slot_ids' => $item['time_slot_ids'],
                    'day_of_week' => $item['day_of_week'],
                    'course_name' => collect([$item['course_name'], $item['teacher_name']])->filter()->join(' - '),
                    'start_date' => $item['start_date'] ?? null,
                    'end_date' => $item['end_date'] ?? null,
                ];
            })->values(),
            'semester_range' => [
                'start_date' => $semesterStartDate,
                'end_date' => $semesterEndDate,
            ],
        ]);
    }

    public function importCourseSchedules(Request $request)
    {
        $validated = $request->validate([
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        $classroomIds = collect($validated['classroom_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->get(['id', 'code', 'name']);

        if ($classrooms->isEmpty()) {
            return back()->withErrors(['classroom_ids' => '找不到可匯入的教室。']);
        }

        $semester = $this->longTermCourseScheduleService->resolveCurrentOrNearestFutureSemester();
        if (! $semester) {
            return back()->withErrors([
                'import' => '找不到目前或未來學期，請先建立學期資料。',
            ]);
        }

        $periodToSlotId = $this->longTermCourseScheduleService->buildPeriodToSlotIdMap();
        if (empty($periodToSlotId)) {
            return back()->withErrors(['import' => '時段資料不足，請先建立 time_slots。']);
        }

        try {
            $rows = $this->longTermCourseScheduleService->fetchImportedSchedulesForClassrooms(
                $semester,
                $classrooms,
                $periodToSlotId
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'import' => $e->getMessage(),
            ]);
        }

        $this->longTermCourseScheduleService->replaceSemesterSchedulesForClassrooms($semester, $classroomIds, $rows);

        return back()->with('success', '課表匯入完成，共新增 ' . count($rows) . ' 筆課程。');
    }

    public function previewManualLongTermBorrowingConflicts(PreviewManualLongTermBorrowingConflictsRequest $request)
    {
        $validated = $request->validated();

        $currentSemester = Semester::findByDate(now());
        if (! $currentSemester) {
            return response()->json([
                'message' => '目前沒有設定中的學期，請先建立學期資料。',
                'conflicts' => [],
                'summary' => [
                    'total' => 0,
                    'schedule' => 0,
                    'approved_short_term' => 0,
                    'pending_short_term' => 0,
                ],
            ], 422);
        }

        $periodToSlotId = $this->longTermCourseScheduleService->buildPeriodToSlotIdMap(true);
        $analysis = $this->manualLongTermConflictService->analyzeConflicts($validated, $currentSemester, $periodToSlotId);

        return response()->json([
            'message' => '衝突分析完成。',
            'conflicts' => $analysis['conflicts'],
            'summary' => [
                'total' => count($analysis['conflicts']),
                'schedule' => $analysis['schedule_conflict_count'],
                'approved_short_term' => $analysis['approved_short_term_count'],
                'pending_short_term' => $analysis['pending_short_term_count'],
            ],
        ]);
    }

    public function resolveManualLongTermConflict(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:cancel_slot,review_pending,reject_and_override,defer_to_short_term,override_with_long_term'],
            'booking_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $action = (string) $validated['action'];

        if ($action === 'review_pending') {
            return response()->json([
                'message' => '請前往審核清單處理未審核短期借用。',
                'redirect' => '/admin/reviews?from=long-term-borrowing',
            ]);
        }

        if ($action === 'cancel_slot' || $action === 'defer_to_short_term') {
            return response()->json([
                'message' => '衝突處理已套用。',
            ]);
        }

        $bookingId = (int) ($validated['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            return response()->json([
                'message' => '缺少短期借用識別資訊，請重新整理後再試。',
            ], 422);
        }

        $managerId = (int) (auth()->guard('admin')->id() ?? 0);

        DB::transaction(function () use ($bookingId, $managerId): void {
            $this->bookingRejectionService->rejectBookingsByIds([$bookingId], $managerId, Booking::activeStatusEnums());
        });

        return response()->json([
            'message' => '衝突處理已執行，該短期借用已整筆駁回。',
        ]);
    }

    public function storeManualLongTermBorrowing(StoreManualLongTermBorrowingRequest $request)
    {
        $validated = $request->validated();

        $currentSemester = Semester::findByDate(now());
        if (! $currentSemester) {
            return back()->withErrors(['semester' => '目前沒有設定中的學期，請先建立學期資料。']);
        }

        try {
            $result = $this->manualLongTermBorrowingService->create(
                $validated,
                $currentSemester,
                (int) (auth()->guard('admin')->id() ?? 0)
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $message = '長期借用記錄已新增，共 ' . $result['created_count'] . ' 筆。';
        if ($result['rejected_count'] > 0) {
            $message .= $result['has_slot_resolutions']
                ? ' 已同步駁回 ' . $result['rejected_count'] . ' 筆短期借用申請。'
                : ' 已覆蓋並拒絕 ' . $result['rejected_count'] . ' 筆未審核短期借用。';
        }

        return back()->with('success', $message);
    }

    public function revokeClassroomImport(Classroom $classroom)
    {
        $targetSemester = $this->longTermCourseScheduleService->resolveCurrentOrNearestFutureSemester();
        if (! $targetSemester) {
            return back()->withErrors(['revoke' => '目前沒有設定中的學期。']);
        }

        $deleted = CourseSchedule::where('semester_id', $targetSemester->id)
            ->where('classroom_id', $classroom->id)
            ->where('type', 'course')
            ->delete();

        return back()->with('success', "已撤回 {$classroom->code} 的課表匯入，共刪除 {$deleted} 筆。");
    }

    public function revokeManualLongTermBorrowing(CourseSchedule $schedule)
    {
        $currentSemester = Semester::findByDate(now());
        if (! $currentSemester) {
            return back()->withErrors(['revoke' => '目前沒有設定中的學期。']);
        }

        if ((int) $schedule->semester_id !== (int) $currentSemester->id || ! in_array($this->manualLongTermConflictService->resolveScheduleType($schedule->type), ['manual', 'borrowed'], true)) {
            return back()->withErrors(['revoke' => '僅能撤回本學期手動新增的長期借用記錄。']);
        }

        $schedule->delete();

        return back()->with('success', '已撤回一筆手動長期借用記錄。');
    }

}
