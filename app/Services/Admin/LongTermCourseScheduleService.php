<?php

namespace App\Services\Admin;

use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Models\Semester;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LongTermCourseScheduleService
{
    /**
     * @return array<int, array{code:string,match_prefixes:array<int,string>,category:string,building:string,label:string}>
     */
    private function importMappings(): array
    {
        $rawMappings = config('school.buildings', []);
        if (! is_array($rawMappings)) {
            return [];
        }

        return collect($rawMappings)
            ->filter(fn ($item) => is_array($item) && ! empty($item['code']) && ! empty($item['category']) && ! empty($item['building']))
            ->map(function ($item) {
                $prefixes = collect($item['match_prefixes'] ?? [])
                    ->filter(fn ($prefix) => is_string($prefix) && $prefix !== '')
                    ->map(fn ($prefix) => strtoupper($prefix))
                    ->values()
                    ->all();

                return [
                    'code' => strtoupper((string) $item['code']),
                    'match_prefixes' => $prefixes,
                    'category' => strtoupper((string) $item['category']),
                    'building' => strtoupper((string) $item['building']),
                    'label' => (string) ($item['label'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{code:string,match_prefixes:array<int,string>,category:string,building:string,label:string}|null
     */
    private function findImportMappingByBuildingCode(string $buildingCode): ?array
    {
        $normalized = strtoupper($buildingCode);

        return collect($this->importMappings())
            ->first(fn ($mapping) => $mapping['code'] === $normalized);
    }

    public function resolveCurrentOrNearestFutureSemester(): ?Semester
    {
        $currentSemester = Semester::findByDate(now());
        if ($currentSemester) {
            return $currentSemester;
        }

        return Semester::query()
            ->whereDate('start_date', '>', now()->toDateString())
            ->orderBy('start_date')
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public function buildPeriodToSlotIdMap(bool $includeLunchBreak = false): array
    {
        $query = TimeSlot::query()->orderBy('start_time');
        if (! $includeLunchBreak) {
            $query->where('name', '!=', '午休');
        }

        $slots = $query->get(['id']);

        $map = [];
        foreach ($slots->values() as $index => $slot) {
            $map[$index + 1] = (int) $slot->id;
        }

        return $map;
    }

    public function extractBuildingCode(string $roomCode): ?string
    {
        $upper = strtoupper($roomCode);
        foreach ($this->importMappings() as $mapping) {
            foreach ($mapping['match_prefixes'] as $prefix) {
                if (str_starts_with($upper, $prefix)) {
                    return $mapping['code'];
                }
            }
        }

        return null;
    }

    /**
     * @param Collection<int, Classroom> $classrooms
     * @param array<int, int> $periodToSlotId
     * @return array<int, array<string, mixed>>
     */
    public function fetchImportedSchedulesForClassrooms(Semester $semester, Collection $classrooms, array $periodToSlotId): array
    {
        $groupedClassrooms = $classrooms
            ->groupBy(fn ($room) => $this->extractBuildingCode((string) $room->code) ?? '__UNKNOWN_BUILDING__');

        if ($groupedClassrooms->has('__UNKNOWN_BUILDING__')) {
            $supported = collect($this->importMappings())
                ->pluck('code')
                ->implode('、');
            throw new \RuntimeException("教室代碼無法判斷大樓，目前支援：{$supported}。");
        }

        $year = (int) $semester->academic_year;
        $seme = (int) $semester->semester;
        $importUrl = config('services.nfu_schedule_import.url');

        $allRows = [];

        foreach ($groupedClassrooms as $buildingCode => $roomsInBuilding) {
            $roomsInBuilding = $roomsInBuilding->values();
            $mapping = $this->findImportMappingByBuildingCode((string) $buildingCode);
            if (! $mapping) {
                throw new \RuntimeException('找不到匯入大樓映射設定：' . (string) $buildingCode);
            }

            $payload = [
                'year' => $year,
                'seme' => $seme,
                'category' => $mapping['category'],
                'building' => $mapping['building'],
                'classrooms' => $roomsInBuilding
                    ->map(fn ($room) => "{$room->code},{$room->code}-{$room->name}")
                    ->values()
                    ->all(),
            ];

            try {
                $response = Http::timeout(45)
                    ->acceptJson()
                    ->post($importUrl, $payload);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf('課表匯入服務連線失敗（%s 棟），請確認匯入服務是否啟動。', strtoupper((string) $buildingCode))
                );
            }

            if (! $response->successful()) {
                throw new \RuntimeException(
                    sprintf('課表匯入服務回應錯誤（%s 棟）：HTTP %d', strtoupper((string) $buildingCode), $response->status())
                );
            }

            $rows = $this->normalizeImportedSchedules(
                $response->json(),
                $semester,
                $roomsInBuilding,
                $periodToSlotId
            );

            $allRows = array_merge($allRows, $rows);
        }

        return $allRows;
    }

    /**
     * @param Collection<int, int> $classroomIds
     * @param array<int, array<string, mixed>> $rows
     */
    public function replaceSemesterSchedulesForClassrooms(Semester $semester, Collection $classroomIds, array $rows): void
    {
        DB::transaction(function () use ($semester, $classroomIds, $rows): void {
            CourseSchedule::where('semester_id', (int) $semester->id)
                ->whereIn('classroom_id', $classroomIds->all())
                ->delete();

            if (! empty($rows)) {
                foreach ($rows as $row) {
                    $timeSlotIds = $row['time_slot_ids'] ?? [];
                    unset($row['time_slot_ids']);

                    $schedule = CourseSchedule::create($row);
                    $schedule->timeSlots()->sync($timeSlotIds);
                }
            }
        });
    }

    /**
     * @param mixed $raw
     * @param EloquentCollection<int, Classroom> $classrooms
     * @param array<int, int> $periodToSlotId
     * @return array<int, array<string, mixed>>
     */
    private function normalizeImportedSchedules($raw, Semester $semester, EloquentCollection $classrooms, array $periodToSlotId): array
    {
        if (! is_array($raw)) {
            return [];
        }

        if (! $semester->start_date || ! $semester->end_date) {
            return [];
        }

        $semesterId = (int) $semester->id;

        $semesterStartDate = $semester->start_date->format('Y-m-d');
        $semesterEndDate = $semester->end_date->format('Y-m-d');

        $now = now();
        $result = [];
        $dedup = [];

        foreach ($raw as $classroomSchedule) {
            if (! is_array($classroomSchedule)) {
                continue;
            }

            $cid = strtoupper(trim((string) ($classroomSchedule['cid'] ?? '')));
            if ($cid === '') {
                continue;
            }

            $classroom = $classrooms->first(fn ($room) => strtoupper((string) $room->code) === $cid);
            if (! $classroom) {
                continue;
            }

            $records = $classroomSchedule['r'] ?? [];
            if (! is_array($records)) {
                continue;
            }

            foreach ($records as $record) {
                if (! is_array($record)) {
                    continue;
                }

                $dayOfWeek = (int) ($record['d'] ?? 0);
                if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                    continue;
                }

                $periods = collect($record['p'] ?? [])
                    ->map(fn ($n) => (int) $n)
                    ->filter(fn ($n) => $n > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                if (empty($periods)) {
                    continue;
                }

                $timeSlotIds = collect($periods)
                    ->map(fn ($period) => $periodToSlotId[(int) $period] ?? null)
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($timeSlotIds)) {
                    continue;
                }

                $courseName = Str::limit(trim((string) ($record['n'] ?? '未命名課程')), 100, '');
                $teacherName = trim((string) ($record['i'] ?? ''));
                $teacherName = $teacherName === '' ? null : Str::limit($teacherName, 50, '');

                $hash = implode('|', [
                    $semesterId,
                    (int) $classroom->id,
                    $courseName,
                    $teacherName,
                    $dayOfWeek,
                    implode(',', $timeSlotIds),
                ]);

                if (isset($dedup[$hash])) {
                    continue;
                }
                $dedup[$hash] = true;

                $result[] = [
                    'semester_id' => $semesterId,
                    'classroom_id' => (int) $classroom->id,
                    'course_name' => $courseName,
                    'teacher_name' => $teacherName,
                    'day_of_week' => $dayOfWeek,
                    'type' => 'course',
                    'time_slot_ids' => $timeSlotIds,
                    'start_date' => $semesterStartDate,
                    'end_date' => $semesterEndDate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $result;
    }

}
