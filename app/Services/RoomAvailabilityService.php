<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\Holiday;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RoomAvailabilityService
{
    /** @var Collection */
    protected $timeSlots;

    private const LEVEL_MANUAL_LONG_TERM = 15;

    public function __construct()
    {
        // 載入所有時段並依開始時間排序，用於計算區間
        $this->timeSlots = TimeSlot::orderBy('start_time')->get();
    }

    // --- 主要邏輯 ---

    /**
     * 取得特定教室、日期範圍內的佔用狀況
     */
    public function getOccupiedData(int $classroomId, Carbon $startDate, Carbon $endDate, ?int $excludeScheduleId = null, bool $includeAllOccupants = false): array
    {
        $room = Classroom::find($classroomId);
        if (!$room) {
            return [];
        }

        if ($excludeScheduleId !== null) {
            $room->load(['courseSchedules' => fn ($query) => $query->where('id', '!=', $excludeScheduleId)]);
        }

        return $this->calculateOccupancy($room, $startDate, $endDate, null, $includeAllOccupants);
    }

    /** Aggregate every occurrence in the effective range by weekday and period. */
    public function getRecurringOccupiedData(int $classroomId, Carbon $startDate, Carbon $endDate, ?int $excludeScheduleId = null): array
    {
        $daily = $this->getOccupiedData($classroomId, $startDate, $endDate, $excludeScheduleId, true);
        $grouped = [];
        foreach ($daily as $date => $slots) {
            $weekday = Carbon::parse($date)->isoWeekday();
            foreach ($slots as $slot => $item) {
                $entries = is_array($item) ? ($item['occupants'] ?? [$item]) : [['status' => $item, 'title' => '不可借用日']];
                foreach ($entries as $entry) {
                    $key = json_encode([$entry['status'], $entry['title'] ?? '', $entry['instructor'] ?? '', $entry['applicant'] ?? '']);
                    $grouped[$weekday][$slot][$key] ??= [
                        'status' => $entry['status'], 'title' => $entry['title'] ?? '已佔用',
                        'instructor' => $entry['instructor'] ?? '', 'applicant' => $entry['applicant'] ?? '', 'dates' => [],
                    ];
                    $grouped[$weekday][$slot][$key]['dates'][] = $date;
                }
            }
        }
        $result = [];
        foreach ($grouped as $weekday => $slots) {
            foreach ($slots as $slot => $entries) {
                $details = array_values($entries);
                foreach ($details as &$detail) {
                    $detail['dates'] = array_values(array_unique($detail['dates']));
                }
                unset($detail);
                $result[$weekday][$slot] = [
                    'status' => $details[0]['status'],
                    'title' => count($details) > 1 ? count($details).' 項佔用' : $details[0]['title'],
                    'details' => $details,
                ];
            }
        }

        return $result;
    }

    /**
     * 批次取得多間教室的佔用狀況
     * @param Collection<int, Classroom> $rooms 必須預先載入 bookings, courseSchedules (範圍內)
     */
    public function getBatchOccupiedData(Collection $rooms, Carbon $startDate, Carbon $endDate): array
    {
        // 預先取出範圍內的假日 (一次查詢)
        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();

        $result = [];
        foreach ($rooms as $room) {
            $result[$room->code] = $this->calculateOccupancy($room, $startDate, $endDate, $holidays);
        }

        return $result;
    }

    private function calculateOccupancy(Classroom $room, Carbon $startDate, Carbon $endDate, ?Collection $holidays = null, bool $includeAllOccupants = false): array
    {
        if (is_null($holidays)) {
            $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
        }

        $room->loadMissing([
            'bookings.bookingDates.timeSlots',
            'courseSchedules.semester',
            'courseSchedules.timeSlots',
        ]);

        // 取得與日期範圍重疊的學期
        $semesters = Semester::overlapping($startDate, $endDate);

        // 先將短期借用依日期索引，避免每一天都重新掃描所有 booking。
        $bookingsByDate = [];
        $activeBookings = $room->bookings->whereIn('status_enum', Booking::activeStatusEnums());
        foreach ($activeBookings as $booking) {
            $status = $booking->status_enum === Booking::STATUS_PENDING ? Booking::STATUS_PENDING : Booking::STATUS_APPROVED;
            $bookingLevel = (int) ($booking->level ?? Booking::levelForStatus($status));
            $basePayload = [
                'status' => $status,
                'level' => $bookingLevel,
                'title' => $booking->reason,
                'applicant' => $booking->borrower ? $booking->borrower->name : null,
                'instructor' => $booking->teacher,
            ];

            $bookingDates = $booking->bookingDates
                ->filter(fn ($bookingDate) => !empty($bookingDate->date))
                ->values();

            if ($bookingDates->isNotEmpty()) {
                foreach ($bookingDates as $bookingDate) {
                    $dateKey = $bookingDate->date?->format('Y-m-d');
                    if (empty($dateKey)) {
                        continue;
                    }

                    $slots = $bookingDate->timeSlots->pluck('name')->values()->all();
                    if (empty($slots)) {
                        continue;
                    }

                    $bookingsByDate[$dateKey][] = $basePayload + ['slots' => $slots];
                }
            }
        }

        $occupiedData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $occupiedSlots = collect();

            // 檢查假日
            $holiday = $holidays->firstWhere('date', $dateStr);
            if ($holiday && !$holiday->is_release_slot) {
                $occupiedData[$dateStr] = $this->timeSlots->pluck('name')->mapWithKeys(function ($name) {
                    return [$name => 'holiday'];
                })->toArray();
                $currentDate->addDay();
                continue;
            }

            // --- 判斷日期落在哪個學期 ---
            $currentSemester = $semesters->first(function ($sem) use ($currentDate) {
                return $currentDate->between($sem->start_date, $sem->end_date);
            });

            // 檢查固定課表 (僅在學期內，且課表屬於該學期)
            if ($currentSemester) {
                $dayOfWeek = $currentDate->dayOfWeekIso;
                $courses = $room->courseSchedules
                    ->where('semester_id', $currentSemester->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->filter(function ($course) use ($currentDate, $currentSemester) {
                        $effectiveStart = $course->start_date
                            ? Carbon::parse($course->start_date)
                            : Carbon::parse($currentSemester->start_date);
                        $effectiveEnd = $course->end_date
                            ? Carbon::parse($course->end_date)
                            : Carbon::parse($currentSemester->end_date);

                        return $currentDate->between($effectiveStart, $effectiveEnd);
                    });
                foreach ($courses as $course) {
                    $slots = $course->timeSlots->pluck('name')->values()->all();
                    $scheduleLevel = $this->resolveSchedulePriorityLevel((string) ($course->type ?? 'manual'));
                    foreach ($slots as $slot) {
                        $occupiedSlots->push([
                            'slot' => $slot,
                            'status' => 'course',
                            'level' => $scheduleLevel,
                            'title' => $course->course_name,
                            'instructor' => $course->teacher_name
                        ]);
                    }
                }
            }

            // 檢查短期預約（新結構優先，舊資料相容）
            foreach ($bookingsByDate[$dateStr] ?? [] as $bookingData) {
                foreach ($bookingData['slots'] as $slot) {
                    $occupiedSlots->push([
                        'slot' => $slot,
                        'status' => $bookingData['status'],
                        'level' => (int) ($bookingData['level'] ?? Booking::LEVEL_PENDING),
                        'title' => $bookingData['title'],
                        'applicant' => $bookingData['applicant'],
                        'instructor' => $bookingData['instructor']
                    ]);
                }
            }

            if ($occupiedSlots->isNotEmpty()) {
                $bestBySlot = [];
                foreach ($occupiedSlots as $item) {
                    $slotName = (string) ($item['slot'] ?? '');
                    if ($slotName === '') {
                        continue;
                    }

                    $current = $bestBySlot[$slotName] ?? null;
                    if ($current === null) {
                        $bestBySlot[$slotName] = $item;
                        continue;
                    }

                    $currentLevel = (int) ($current['level'] ?? 0);
                    $nextLevel = (int) ($item['level'] ?? 0);
                    if ($nextLevel > $currentLevel) {
                        $bestBySlot[$slotName] = $item;
                    }
                }

                $occupiedData[$dateStr] = collect($bestBySlot)
                    ->mapWithKeys(function ($item, $slotName) {
                        $val = ['status' => $item['status']];
                        if (!empty($item['title'])) $val['title'] = $item['title'];
                        if (!empty($item['applicant'])) $val['applicant'] = $item['applicant'];
                        if (!empty($item['instructor'])) $val['instructor'] = $item['instructor'];
                        return [$slotName => $val];
                    })
                    ->toArray();
                if ($includeAllOccupants) {
                    foreach ($occupiedSlots->groupBy('slot') as $slot => $items) {
                        $occupiedData[$dateStr][$slot]['occupants'] = $items->values()->all();
                    }
                }
            }

            $currentDate->addDay();
        }

        return $occupiedData;
    }

    private function resolveSchedulePriorityLevel(string $type): int
    {
        if ($type === 'course') {
            return Booking::LEVEL_COURSE;
        }

        return self::LEVEL_MANUAL_LONG_TERM;
    }

}
