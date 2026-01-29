<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\Holiday;
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
        // 若未傳入 holidays，則自行查詢 (相容單一查詢)
        if (is_null($holidays)) {
            $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
        }

        // 確保關聯已載入 (防呆)
        if (!$room->relationLoaded('bookings')) {
            $room->load([
                'bookings' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->whereNotIn('status', [2, 3]);
                }
            ]);
        }
        if (!$room->relationLoaded('courseSchedules')) {
            $room->load('courseSchedules');
        }

        $occupiedData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $occupiedSlots = collect();

            // 檢查假日
            $holiday = $holidays->firstWhere('date', $dateStr);
            if ($holiday && !$holiday->is_release_slot) {
                $occupiedData[$dateStr] = $this->timeSlots->pluck('name')->toArray();
                $currentDate->addDay();
                continue;
            }

            // 檢查固定課表
            $dayOfWeek = $currentDate->dayOfWeekIso;
            $courses = $room->courseSchedules->where('day_of_week', $dayOfWeek);
            foreach ($courses as $course) {
                $slots = $this->getSlotsInRange($course->start_slot_id, $course->end_slot_id);
                $occupiedSlots = $occupiedSlots->merge($slots);
            }

            // 檢查單次預約
            $bookings = $room->bookings->where('date', $dateStr);
            foreach ($bookings as $booking) {
                $slots = $this->getSlotsInRange($booking->start_slot_id, $booking->end_slot_id);
                $occupiedSlots = $occupiedSlots->merge($slots);
            }

            if ($occupiedSlots->isNotEmpty()) {
                $occupiedData[$dateStr] = $occupiedSlots->unique()->values()->toArray();
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
