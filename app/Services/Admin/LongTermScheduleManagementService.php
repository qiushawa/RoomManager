<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\CourseSchedule;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LongTermScheduleManagementService
{
    /** Check actual recurring dates; exclude the edited row or courses being replaced. */
    public function assertAvailable(array $data, Semester $semester, ?int $excludeId = null, bool $replacingCourses = false): void
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $slots = array_map('intval', $data['time_slot_ids']);
        $dates = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isoWeekday() === (int) $data['day_of_week']) {
                $dates[] = $day->toDateString();
            }
        }
        if (! $dates) {
            throw ValidationException::withMessages(['conflict' => '日期範圍內沒有所選星期，請調整日期。']);
        }

        $existing = CourseSchedule::with(['semester', 'timeSlots'])
            ->where('classroom_id', $data['classroom_id'])
            ->where('semester_id', $semester->id)
            ->where('day_of_week', $data['day_of_week'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->when($replacingCourses, fn ($q) => $q->where('type', '!=', 'course'))
            ->get();
        foreach ($existing as $row) {
            $from = ($row->start_date ?? $row->semester->start_date)->toDateString();
            $to = ($row->end_date ?? $row->semester->end_date)->toDateString();
            $overlap = array_intersect($slots, $row->timeSlots->modelKeys());
            if ($overlap && collect($dates)->contains(fn ($date) => $date >= $from && $date <= $to)) {
                $labels = $row->timeSlots->whereIn('id', $overlap)->pluck('name')->implode('、');
                throw ValidationException::withMessages(['conflict' => "與長期紀錄「{$row->course_name}」衝突（{$from}～{$to}，{$labels}），請先處理。"]);
            }
        }
        $bookingDate = BookingDate::with(['booking', 'timeSlots'])
            ->whereIn('date', $dates)
            ->whereHas('booking', fn ($q) => $q->where('classroom_id', $data['classroom_id'])->whereIn('status_enum', Booking::activeStatusEnums()))
            ->whereHas('timeSlots', fn ($q) => $q->whereIn('time_slots.id', $slots))
            ->first();
        if ($bookingDate) {
            $labels = $bookingDate->timeSlots->whereIn('id', $slots)->pluck('name')->implode('、');
            throw ValidationException::withMessages(['conflict' => "與短期借用 #{$bookingDate->booking_id} 衝突（{$bookingDate->date->toDateString()}，{$labels}），請先處理。"]);
        }
    }
}
