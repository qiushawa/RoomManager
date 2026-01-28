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
        // 查詢教室並預載入 (範圍內的預約 & 課表)
        $classroom = Classroom::with([
            'bookings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->whereNotIn('status', [2, 3]); // 排除 2:拒絕, 3:取消
            },
            'courseSchedules'
        ])->find($classroomId);

        if (!$classroom) {
            return [];
        }

        // 獲取範圍內的假日設定
        $holidays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();

        $occupiedData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $occupiedSlots = collect();

            // -! 實驗性功能
            // 檢查假日 (若為假日且未釋出，則全天佔用)
            $holiday = $holidays->firstWhere('date', $dateStr);
            
            if ($holiday && !$holiday->is_release_slot) {
                $occupiedData[$dateStr] = $this->timeSlots->pluck('name')->toArray();
                $currentDate->addDay();
                continue;
            }

            // 檢查固定課表 (day_of_week: 1=週一 ... 7=週日)
            $dayOfWeek = $currentDate->dayOfWeekIso;
            $courses = $classroom->courseSchedules->where('day_of_week', $dayOfWeek);

            foreach ($courses as $course) {
                $slots = $this->getSlotsInRange($course->start_slot_id, $course->end_slot_id);
                $occupiedSlots = $occupiedSlots->merge($slots);
            }

            // 檢查單次預約
            $bookings = $classroom->bookings->where('date', $dateStr);

            foreach ($bookings as $booking) {
                $slots = $this->getSlotsInRange($booking->start_time_slot_id, $booking->end_time_slot_id);
                $occupiedSlots = $occupiedSlots->merge($slots);
            }

            // 寫入結果
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