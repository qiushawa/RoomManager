<?php

namespace App\Services\Admin;

use App\Models\CourseSchedule;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualLongTermBorrowingService
{
    public function __construct(
        private readonly LongTermCourseScheduleService $longTermCourseScheduleService,
        private readonly ManualLongTermConflictService $manualLongTermConflictService,
    ) {
    }

    /**
     * @param array<string,mixed> $validated
     * @return array{created_count:int}
     */
    public function create(array $validated, Semester $currentSemester): array
    {
        $periodToSlotId = $this->longTermCourseScheduleService->buildPeriodToSlotIdMap(true);
        $analysis = $this->manualLongTermConflictService->analyzeConflicts($validated, $currentSemester, $periodToSlotId);
        if (! empty($validated['conflict_resolution']) || ! empty($validated['slot_resolutions'])) {
            throw ValidationException::withMessages(['periods' => '手動新增不支援修改或覆蓋既有占用。']);
        }
        if (! empty($analysis['conflicts'])) {
            throw ValidationException::withMessages(['periods' => '所選節次已有占用，請調整日期區間或節次。若需修改長期借用，請至「長期借用紀錄」。']);
        }
        $selectedByDay = $analysis['selected_by_day'];

        $slotGroupsByDay = [];
        foreach ($selectedByDay as $weekday => $periodIndexes) {
            $slotGroupsByDay[$weekday] = $this->buildSlotGroupsFromPeriods($periodIndexes, $periodToSlotId);
        }

        $rows = [];
        $now = now();

        foreach ($slotGroupsByDay as $weekday => $slotGroups) {
            foreach ($slotGroups as $slotIds) {
                $rows[] = [
                    'semester_id' => (int) $currentSemester->id,
                    'classroom_id' => (int) $validated['classroom_id'],
                    'type' => 'manual',
                    'teacher_name' => (string) $validated['teacher_name'],
                    'course_name' => (string) ($validated['course_name'] ?? ''),
                    'day_of_week' => (int) $weekday,
                    'time_slot_ids' => $slotIds,
                    'start_date' => (string) $validated['start_date'],
                    'end_date' => (string) $validated['end_date'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($rows)) {
            throw ValidationException::withMessages(['periods' => '所選節次皆與既有課表衝突，沒有可新增的時段。']);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $timeSlotIds = $row['time_slot_ids'] ?? [];
                unset($row['time_slot_ids']);

                $schedule = CourseSchedule::create($row);
                $schedule->timeSlots()->sync($timeSlotIds);
            }
        });

        return [
            'created_count' => count($rows),
        ];
    }

    /**
     * @param array<int,int> $periods
     * @param array<int,int> $periodToSlotId
     * @return array<int,array<int,int>>
     */
    private function buildSlotGroupsFromPeriods(array $periods, array $periodToSlotId): array
    {
        $indexes = collect($periods)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($indexes)) {
            return [];
        }

        $groups = [];
        $chunkStart = $indexes[0];
        $prev = $indexes[0];

        for ($i = 1; $i < count($indexes); $i++) {
            $current = $indexes[$i];
            if ($current !== $prev + 1) {
                $slotIds = [];
                for ($p = $chunkStart; $p <= $prev; $p++) {
                    if (isset($periodToSlotId[$p])) {
                        $slotIds[] = (int) $periodToSlotId[$p];
                    }
                }

                if (! empty($slotIds)) {
                    $groups[] = $slotIds;
                }
                $chunkStart = $current;
            }
            $prev = $current;
        }

        $slotIds = [];
        for ($p = $chunkStart; $p <= $prev; $p++) {
            if (isset($periodToSlotId[$p])) {
                $slotIds[] = (int) $periodToSlotId[$p];
            }
        }

        if (! empty($slotIds)) {
            $groups[] = $slotIds;
        }

        return $groups;
    }

}
