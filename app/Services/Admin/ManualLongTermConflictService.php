<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Models\CourseSchedule;
use App\Models\Semester;

class ManualLongTermConflictService
{
    /**
     * @param array<string,mixed> $validated
     * @param array<int,int> $periodToSlotId
     * @return array<string,mixed>
     */
    public function analyzeConflicts(array $validated, Semester $semester, array $periodToSlotId): array
    {
        $dayOfWeeks = collect($validated['day_of_week'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();
        $selectedByDay = $this->buildSelectedPeriodsByDay($validated, $dayOfWeeks);
        $selectedSlotIdsByDay = [];

        foreach ($selectedByDay as $weekday => $periods) {
            $selectedSlotIdsByDay[(int) $weekday] = collect($periods)
                ->map(fn ($period) => $periodToSlotId[(int) $period] ?? null)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $hasSelectableSlot = collect($selectedSlotIdsByDay)
            ->contains(fn ($slotIds) => ! empty($slotIds));

        if (! $hasSelectableSlot) {
            return [
                'conflicts' => [],
                'schedule_conflict_count' => 0,
                'approved_short_term_count' => 0,
                'pending_short_term_count' => 0,
                'pending_conflict_booking_ids' => [],
                'approved_conflict_booking_ids' => [],
                'selected_by_day' => $selectedByDay,
            ];
        }

        $slotIdToPeriod = [];
        foreach ($periodToSlotId as $period => $slotId) {
            $slotIdToPeriod[(int) $slotId] = (int) $period;
        }

        $semesterStart = $semester->start_date?->format('Y-m-d');
        $semesterEnd = $semester->end_date?->format('Y-m-d');
        $semesterOverlapsRequest = $semesterStart
            && $semesterEnd
            && $semesterStart <= (string) ($validated['end_date'] ?? '')
            && $semesterEnd >= (string) ($validated['start_date'] ?? '');

        $rows = CourseSchedule::with(['semester', 'timeSlots'])
            ->where('semester_id', (int) $semester->id)
            ->where('classroom_id', (int) ($validated['classroom_id'] ?? 0))
            ->whereIn('day_of_week', $dayOfWeeks)
            ->where(function ($query) use ($validated, $semesterOverlapsRequest) {
                $query
                    ->where(function ($manual) use ($validated) {
                        $manual
                            ->whereNotNull('start_date')
                            ->whereNotNull('end_date')
                            ->whereDate('start_date', '<=', $validated['end_date'])
                            ->whereDate('end_date', '>=', $validated['start_date']);
                    });

                if ($semesterOverlapsRequest) {
                    $query->orWhere(function ($imported) {
                        $imported->where('type', 'course');
                    });
                }
            })
            ->get();

        $conflicts = [];
        $scheduleConflictCount = 0;

        foreach ($rows as $row) {
            $weekday = (int) $row->day_of_week;
            $selectedSlotIds = $selectedSlotIdsByDay[$weekday] ?? [];
            if (empty($selectedSlotIds)) {
                continue;
            }

            $existingSlotIds = $row->timeSlots
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            if (empty($existingSlotIds)) {
                continue;
            }

            $overlapSlotIds = array_values(array_intersect($selectedSlotIds, $existingSlotIds));
            if (empty($overlapSlotIds)) {
                continue;
            }

            $overlapPeriods = collect($overlapSlotIds)
                ->map(fn ($slotId) => $slotIdToPeriod[(int) $slotId] ?? null)
                ->filter()
                ->map(fn ($period) => (int) $period)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (empty($overlapPeriods)) {
                continue;
            }

            $type = $this->resolveScheduleType($row->type);
            $scheduleConflictCount++;

            $conflicts[] = [
                'id' => (int) $row->id,
                'conflict_kind' => 'schedule',
                'day_of_week' => $weekday,
                'start_slot' => (string) ($row->timeSlots->sortBy('start_time')->pluck('name')->first() ?? ''),
                'end_slot' => (string) ($row->timeSlots->sortBy('start_time')->pluck('name')->last() ?? ''),
                'start_date' => $row->start_date?->format('Y-m-d') ?? $semester->start_date?->format('Y-m-d'),
                'end_date' => $row->end_date?->format('Y-m-d') ?? $semester->end_date?->format('Y-m-d'),
                'type' => $type,
                'source_label' => $this->manualConflictSourceLabel($type),
                'course_name' => (string) ($row->course_name ?? ''),
                'teacher_name' => (string) ($row->teacher_name ?? ''),
                'is_protected' => true,
                'overlap_periods' => $overlapPeriods,
                'conflict_dates' => [],
                'booking_id' => null,
                'booking_status' => null,
                'applicant_name' => '',
            ];
        }

        $shortTermAnalysis = $this->analyzeShortTermBookingConflicts(
            (int) ($validated['classroom_id'] ?? 0),
            (string) ($validated['start_date'] ?? ''),
            (string) ($validated['end_date'] ?? ''),
            $selectedSlotIdsByDay,
            $slotIdToPeriod
        );

        $conflicts = [...$conflicts, ...$shortTermAnalysis['conflicts']];

        return [
            'conflicts' => $conflicts,
            'schedule_conflict_count' => $scheduleConflictCount,
            'approved_short_term_count' => $shortTermAnalysis['approved_count'],
            'pending_short_term_count' => $shortTermAnalysis['pending_count'],
            'pending_conflict_booking_ids' => $shortTermAnalysis['pending_booking_ids'],
            'approved_conflict_booking_ids' => $shortTermAnalysis['approved_booking_ids'],
            'selected_by_day' => $selectedByDay,
        ];
    }

    public function resolveScheduleType(?string $type): string
    {
        return in_array($type, ['course', 'manual', 'borrowed'], true) ? $type : 'manual';
    }

    /**
     * @param array<int,array<int,int>> $selectedSlotIdsByDay
     * @param array<int,int> $slotIdToPeriod
     * @return array<string,mixed>
     */
    private function analyzeShortTermBookingConflicts(
        int $classroomId,
        string $startDate,
        string $endDate,
        array $selectedSlotIdsByDay,
        array $slotIdToPeriod
    ): array {
        $bookings = Booking::with(['borrower', 'bookingDates.timeSlots'])
            ->where('classroom_id', $classroomId)
            ->whereIn('status_enum', Booking::activeStatusEnums())
            ->whereHas('bookingDates', function ($query) use ($startDate, $endDate) {
                $query->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate);
            })
            ->get();

        $aggregated = [];

        foreach ($bookings as $booking) {
            foreach ($booking->bookingDates as $bookingDate) {
                $dateText = $bookingDate->date?->format('Y-m-d');
                if (! $dateText || $dateText < $startDate || $dateText > $endDate) {
                    continue;
                }

                $weekday = (int) date('N', strtotime($dateText));
                $selectedSlotIds = $selectedSlotIdsByDay[$weekday] ?? [];
                if (empty($selectedSlotIds)) {
                    continue;
                }

                $bookingSlotIds = $bookingDate->timeSlots
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($bookingSlotIds)) {
                    continue;
                }

                $overlapSlotIds = array_values(array_intersect($selectedSlotIds, $bookingSlotIds));
                if (empty($overlapSlotIds)) {
                    continue;
                }

                $bookingId = (int) $booking->id;
                if (! isset($aggregated[$bookingId])) {
                    $aggregated[$bookingId] = [
                        'booking' => $booking,
                        'periods' => [],
                        'dates' => [],
                        'slots' => [],
                    ];
                }

                foreach ($overlapSlotIds as $slotId) {
                    $period = $slotIdToPeriod[(int) $slotId] ?? null;
                    if ($period) {
                        $aggregated[$bookingId]['periods'][] = (int) $period;
                        $aggregated[$bookingId]['slots'][] = [
                            'slot_key' => $this->buildManualConflictSlotKey($weekday, (int) $period),
                            'day_of_week' => $weekday,
                            'period' => (int) $period,
                            'date' => $dateText,
                            'booking_date_id' => (int) $bookingDate->id,
                            'time_slot_id' => (int) $slotId,
                        ];
                    }
                }

                $aggregated[$bookingId]['dates'][] = $dateText;
            }
        }

        $approvedCount = 0;
        $pendingCount = 0;
        $approvedBookingIds = [];
        $pendingBookingIds = [];
        $conflicts = [];

        foreach ($aggregated as $bookingId => $item) {
            /** @var Booking $booking */
            $booking = $item['booking'];
            $statusEnum = (string) $booking->status_enum;
            $isPending = $statusEnum === Booking::STATUS_PENDING;

            if ($isPending) {
                $pendingCount++;
                $pendingBookingIds[] = (int) $bookingId;
            } else {
                $approvedCount++;
                $approvedBookingIds[] = (int) $bookingId;
            }

            $overlapPeriods = collect($item['periods'])
                ->map(fn ($period) => (int) $period)
                ->filter(fn ($period) => $period > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $conflictDates = collect($item['dates'])
                ->filter(fn ($date) => is_string($date) && $date !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $conflictSlots = collect($item['slots'])
                ->filter(fn ($slot) => is_array($slot))
                ->unique(fn ($slot) => ($slot['booking_date_id'] ?? '0') . ':' . ($slot['time_slot_id'] ?? '0'))
                ->values()
                ->all();

            $conflicts[] = [
                'id' => 1000000000 + (int) $bookingId,
                'conflict_kind' => $isPending ? 'short_term_pending' : 'short_term_approved',
                'day_of_week' => 0,
                'start_slot' => '',
                'end_slot' => '',
                'start_date' => $conflictDates[0] ?? null,
                'end_date' => $conflictDates[count($conflictDates) - 1] ?? null,
                'type' => 'borrowed',
                'source_label' => $isPending ? '未審核短期借用' : '已審核短期借用',
                'course_name' => '',
                'teacher_name' => (string) ($booking->teacher ?? ''),
                'is_protected' => ! $isPending,
                'overlap_periods' => $overlapPeriods,
                'conflict_dates' => $conflictDates,
                'booking_id' => (int) $bookingId,
                'booking_status' => $statusEnum,
                'applicant_name' => (string) ($booking->borrower?->name ?? ''),
                'conflict_slots' => $conflictSlots,
            ];
        }

        return [
            'conflicts' => $conflicts,
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'approved_booking_ids' => array_values(array_unique($approvedBookingIds)),
            'pending_booking_ids' => array_values(array_unique($pendingBookingIds)),
        ];
    }

    /**
     * @param array<string,mixed> $validated
     * @param array<int,int> $dayOfWeeks
     * @return array<int,array<int,int>>
     */
    private function buildSelectedPeriodsByDay(array $validated, array $dayOfWeeks): array
    {
        $selectedByDay = [];

        $rawByDay = $validated['periods_by_day'] ?? null;
        if (is_array($rawByDay)) {
            foreach ($dayOfWeeks as $weekday) {
                $selectedByDay[(int) $weekday] = collect($rawByDay[(string) $weekday] ?? $rawByDay[(int) $weekday] ?? [])
                    ->map(fn ($p) => (int) $p)
                    ->filter(fn ($p) => $p > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }

            return $selectedByDay;
        }

        $selectedPeriods = collect($validated['periods'] ?? [])
            ->map(fn ($p) => (int) $p)
            ->filter(fn ($p) => $p > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach ($dayOfWeeks as $weekday) {
            $selectedByDay[(int) $weekday] = $selectedPeriods;
        }

        return $selectedByDay;
    }

    private function manualConflictSourceLabel(string $type): string
    {
        return match ($type) {
            'course' => '課表匯入',
            'borrowed' => '一般借用',
            default => '手動課程',
        };
    }

    private function buildManualConflictSlotKey(int $dayOfWeek, int $period): string
    {
        return $dayOfWeek . ':' . $period;
    }
}
