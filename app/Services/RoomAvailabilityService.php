<?php

namespace App\Services;

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

    public function __construct()
    {
        // 載入所有時段並依開始時間排序，用於計算區間
        $this->timeSlots = TimeSlot::orderBy('start_time')->get();
    }

    // --- 主要邏輯 ---

    /**
     * 取得特定教室、日期範圍內的佔用狀況
     */
    public function getOccupiedData(int $classroomId, Carbon $startDate, Carbon $endDate): array
    {
        $room = Classroom::find($classroomId);
        if (!$room) {
            return [];
        }

        return $this->calculateOccupancy($room, $startDate, $endDate);
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

    private function calculateOccupancy(Classroom $room, Carbon $startDate, Carbon $endDate, ?Collection $holidays = null): array
    {
        if (is_null($holidays)) {
            $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
        }

        $room->loadMissing([
            'bookings.timeSlots', // 預載多對多時段
            'courseSchedules.semester'
        ]);

        // 取得與日期範圍重疊的學期
        $semesters = Semester::overlapping($startDate, $endDate);

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
                    $slots = $this->getSlotsInRange($course->start_slot_id, $course->end_slot_id);
                    foreach ($slots as $slot) {
                        $occupiedSlots->push([
                            'slot' => $slot,
                            'status' => 'course',
                            'title' => $course->course_name,
                            'instructor' => $course->teacher_name
                        ]);
                    }
                }
            }

            // 檢查單次預約
            $bookings = $room->bookings->where('date', $dateStr)->whereNotIn('status', [2, 3]);
            foreach ($bookings as $booking) {
                $slots = $booking->timeSlots->pluck('name')->toArray();
                $status = $booking->status == 0 ? 'pending' : 'approved';
                foreach ($slots as $slot) {
                    $occupiedSlots->push([
                        'slot' => $slot,
                        'status' => $status,
                        'title' => $booking->reason,
                        'applicant' => $booking->borrower ? $booking->borrower->name : null,
                        'instructor' => $booking->teacher
                    ]);
                }
            }

            if ($occupiedSlots->isNotEmpty()) {
                $occupiedData[$dateStr] = $occupiedSlots->unique('slot')->mapWithKeys(function ($item) {
                    $val = ['status' => $item['status']];
                    if (!empty($item['title'])) $val['title'] = $item['title'];
                    if (!empty($item['applicant'])) $val['applicant'] = $item['applicant'];
                    if (!empty($item['instructor'])) $val['instructor'] = $item['instructor'];
                    return [$item['slot'] => $val];
                })->toArray();
            }

            $currentDate->addDay();
        }

        return $occupiedData;
    }

    // --- 輔助函式 ---

    /**
     * 計算開始與結束 ID 之間的所有時段代號
     */
    private function getSlotsInRange(int $startId, int $endId): array
    {
        // 在已排序的集合中尋找索引位置
        $startIndex = $this->timeSlots->search(fn($t) => $t->id == $startId);
        $endIndex = $this->timeSlots->search(fn($t) => $t->id == $endId);

        if ($startIndex === false || $endIndex === false) {
            return [];
        }

        // 計算切片範圍
        $start = min($startIndex, $endIndex);
        $length = abs($endIndex - $startIndex) + 1;

        return $this->timeSlots->slice($start, $length)->pluck('name')->toArray();
    }
}
