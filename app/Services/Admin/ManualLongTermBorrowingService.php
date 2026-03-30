<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\CourseSchedule;
use App\Models\Semester;
use App\Services\BookingSlotLockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualLongTermBorrowingService
{
    public function __construct(
        private readonly LongTermCourseScheduleService $longTermCourseScheduleService,
        private readonly ManualLongTermConflictService $manualLongTermConflictService,
        private readonly BookingSlotLockService $bookingSlotLockService,
    ) {
    }

    /**
     * @param array<string,mixed> $validated
     * @return array{created_count:int,rejected_count:int,has_slot_resolutions:bool}
     */
    public function create(array $validated, Semester $currentSemester, int $managerId): array
    {
        $periodToSlotId = $this->longTermCourseScheduleService->buildPeriodToSlotIdMap(true);
        $analysis = $this->manualLongTermConflictService->analyzeConflicts($validated, $currentSemester, $periodToSlotId);
        $conflictResolution = $validated['conflict_resolution'] ?? [];
        $slotResolutions = collect($validated['slot_resolutions'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])
            ->filter(fn ($value) => $value !== '')
            ->all();
        $hasSlotResolutions = ! empty($slotResolutions);

        $selectedByDay = $analysis['selected_by_day'];
        $pendingResolution = $conflictResolution['pending_short_term'] ?? null;
        $pendingRejectBookingIds = [];
        $approvedRejectBookingIds = [];

        if ($hasSlotResolutions) {
            $kindPriority = [
                'short_term_pending' => 1,
                'short_term_approved' => 2,
                'schedule' => 3,
            ];
            $conflictKindBySlot = [];
            $hasPendingReviewResolution = false;
            $hasUnresolvedPendingConflict = false;
            $hasUnresolvedApprovedConflict = false;

            foreach ($analysis['conflicts'] as $conflict) {
                $conflictKind = (string) ($conflict['conflict_kind'] ?? '');
                $periods = collect($conflict['overlap_periods'] ?? [])
                    ->map(fn ($period) => (int) $period)
                    ->filter(fn ($period) => $period > 0)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($periods)) {
                    continue;
                }

                $weekdays = [];
                if ($conflictKind === 'schedule') {
                    $weekday = (int) ($conflict['day_of_week'] ?? 0);
                    if ($weekday >= 1 && $weekday <= 7) {
                        $weekdays[] = $weekday;
                    }
                } else {
                    $weekdays = collect($conflict['conflict_dates'] ?? [])
                        ->map(function ($dateText) {
                            if (! is_string($dateText) || $dateText === '') {
                                return null;
                            }
                            $timestamp = strtotime($dateText);
                            if ($timestamp === false) {
                                return null;
                            }

                            return (int) date('N', $timestamp);
                        })
                        ->filter(fn ($weekday) => is_int($weekday) && $weekday >= 1 && $weekday <= 7)
                        ->unique()
                        ->values()
                        ->all();
                }

                foreach ($weekdays as $weekday) {
                    foreach ($periods as $period) {
                        $slotKey = $this->buildManualConflictSlotKey((int) $weekday, (int) $period);
                        $existingKind = $conflictKindBySlot[$slotKey] ?? null;
                        if (! $existingKind || ($kindPriority[$conflictKind] ?? 0) > ($kindPriority[$existingKind] ?? 0)) {
                            $conflictKindBySlot[$slotKey] = $conflictKind;
                        }
                    }
                }

                $conflictSlots = collect($conflict['conflict_slots'] ?? [])
                    ->filter(fn ($slot) => is_array($slot))
                    ->values();

                if (in_array($conflictKind, ['short_term_pending', 'short_term_approved'], true)) {
                    $bookingId = (int) ($conflict['booking_id'] ?? 0);

                    $bookingActions = $conflictSlots
                        ->map(function ($slot) use ($bookingId, $slotResolutions) {
                            $slotKey = (string) ($slot['slot_key'] ?? '');
                            if ($slotKey === '') {
                                $weekday = (int) ($slot['day_of_week'] ?? 0);
                                $period = (int) ($slot['period'] ?? 0);
                                if ($weekday >= 1 && $weekday <= 7 && $period > 0) {
                                    $slotKey = $this->buildManualConflictSlotKey($weekday, $period);
                                }
                            }

                            if ($slotKey === '') {
                                return null;
                            }

                            $bookingDateId = (int) ($slot['booking_date_id'] ?? 0);
                            $timeSlotId = (int) ($slot['time_slot_id'] ?? 0);

                            $resolutionKey = ($bookingDateId > 0 && $timeSlotId > 0)
                                ? $this->buildManualConflictResolutionKey($slotKey, $bookingDateId, $timeSlotId)
                                : null;
                            $legacyBookingKey = $bookingId > 0
                                ? $this->buildManualConflictLegacyResolutionKey($slotKey, $bookingId)
                                : null;

                            return $resolutionKey
                                ? ($slotResolutions[$resolutionKey] ?? $slotResolutions[$slotKey] ?? ($legacyBookingKey ? ($slotResolutions[$legacyBookingKey] ?? null) : null))
                                : ($slotResolutions[$slotKey] ?? ($legacyBookingKey ? ($slotResolutions[$legacyBookingKey] ?? null) : null));
                        })
                        ->filter(fn ($action) => is_string($action) && $action !== '')
                        ->values();

                    if ($conflictKind === 'short_term_pending') {
                        if ($bookingActions->contains('review_pending')) {
                            $hasPendingReviewResolution = true;
                            continue;
                        }

                        if ($bookingActions->contains('reject_and_override')) {
                            if ($bookingId > 0) {
                                $pendingRejectBookingIds[] = $bookingId;
                            }
                            continue;
                        }

                        $hasUnresolvedPendingConflict = true;
                        continue;
                    }

                    if ($bookingActions->contains('defer_to_short_term')) {
                        continue;
                    }

                    if ($bookingActions->contains('override_with_long_term')) {
                        if ($bookingId > 0) {
                            $approvedRejectBookingIds[] = $bookingId;
                        }
                        continue;
                    }

                    $hasUnresolvedApprovedConflict = true;
                    continue;
                }
            }

            if ($hasPendingReviewResolution) {
                throw ValidationException::withMessages(['periods' => '偵測到未審核短期借用，請先前往審核清單處理後再新增。']);
            }

            if ($hasUnresolvedPendingConflict) {
                throw ValidationException::withMessages(['periods' => '請先處理未審核短期借用衝突。']);
            }

            if ($hasUnresolvedApprovedConflict) {
                throw ValidationException::withMessages(['periods' => '請先處理已審核短期借用衝突。']);
            }

            foreach ($selectedByDay as $weekday => $periods) {
                $keptPeriods = [];
                foreach ($periods as $period) {
                    $slotKey = $this->buildManualConflictSlotKey((int) $weekday, (int) $period);
                    $kind = $conflictKindBySlot[$slotKey] ?? null;
                    $action = $slotResolutions[$slotKey] ?? null;

                    if (! $kind) {
                        $keptPeriods[] = (int) $period;
                        continue;
                    }

                    if ($kind === 'schedule') {
                        if ($action !== 'cancel_slot') {
                            throw ValidationException::withMessages(['periods' => '存在課表衝突，請點擊衝突格選擇「取消該節」。']);
                        }
                        continue;
                    }

                    if ($kind === 'short_term_pending') {
                        $keptPeriods[] = (int) $period;
                        continue;
                    }

                    if ($kind === 'short_term_approved') {
                        $keptPeriods[] = (int) $period;
                    }
                }

                $selectedByDay[$weekday] = collect($keptPeriods)
                    ->map(fn ($period) => (int) $period)
                    ->filter(fn ($period) => $period > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }
        } else {
            if (($analysis['schedule_conflict_count'] ?? 0) > 0) {
                throw ValidationException::withMessages(['periods' => '存在課表衝突，請先調整時段後再送出。']);
            }

            if (($analysis['approved_short_term_count'] ?? 0) > 0 && ($conflictResolution['approved_short_term'] ?? null) !== 'keep_short_term') {
                throw ValidationException::withMessages(['periods' => '請先確認「保留短期借用節數」後再送出。']);
            }

            $pendingConflictCount = (int) ($analysis['pending_short_term_count'] ?? 0);
            if ($pendingConflictCount > 0) {
                if ($pendingResolution === 'review_pending') {
                    throw ValidationException::withMessages(['periods' => '偵測到未審核短期借用，請先前往審核清單處理後再新增。']);
                }

                if ($pendingResolution !== 'reject_and_override') {
                    throw ValidationException::withMessages(['periods' => '請選擇未審核短期借用的處理方式。']);
                }
            }
        }

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

        $rejectedCount = 0;

        DB::transaction(function () use (
            $rows,
            $analysis,
            $pendingResolution,
            $pendingRejectBookingIds,
            $approvedRejectBookingIds,
            $hasSlotResolutions,
            $managerId,
            &$rejectedCount
        ): void {
            if ($hasSlotResolutions) {
                $rejectedCount += $this->rejectBookingsByIds(
                    array_merge($pendingRejectBookingIds, $approvedRejectBookingIds),
                    $managerId,
                    Booking::activeStatusEnums()
                );
            } elseif ($pendingResolution === 'reject_and_override') {
                $pendingIds = collect($analysis['pending_conflict_booking_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values();

                $rejectedCount += $this->rejectBookingsByIds(
                    $pendingIds->all(),
                    $managerId,
                    [Booking::STATUS_PENDING]
                );
            }

            foreach ($rows as $row) {
                $timeSlotIds = $row['time_slot_ids'] ?? [];
                unset($row['time_slot_ids']);

                $schedule = CourseSchedule::create($row);
                $schedule->timeSlots()->sync($timeSlotIds);
            }
        });

        return [
            'created_count' => count($rows),
            'rejected_count' => $rejectedCount,
            'has_slot_resolutions' => $hasSlotResolutions,
        ];
    }

    /**
     * @param array<int,int> $bookingIds
     * @param array<int,string> $allowedStatuses
     */
    private function rejectBookingsByIds(array $bookingIds, int $managerId, array $allowedStatuses): int
    {
        $normalizedIds = collect($bookingIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return 0;
        }

        $bookings = Booking::with('bookingDates.timeSlots')
            ->whereIn('id', $normalizedIds->all())
            ->whereIn('status_enum', $allowedStatuses)
            ->lockForUpdate()
            ->get();

        foreach ($bookings as $booking) {
            $booking->status_enum = Booking::STATUS_REJECTED;
            $booking->level = Booking::levelForStatus(Booking::STATUS_REJECTED);
            $booking->rejected_by = $managerId > 0 ? $managerId : null;
            $booking->rejected_at = now();
            $booking->approved_by = null;
            $booking->approved_at = null;
            $booking->save();

            $this->bookingSlotLockService->syncForBooking($booking);
        }

        return $bookings->count();
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

    private function buildManualConflictSlotKey(int $dayOfWeek, int $period): string
    {
        return $dayOfWeek . ':' . $period;
    }

    private function buildManualConflictResolutionKey(string $slotKey, int $bookingDateId, int $timeSlotId): string
    {
        return $slotKey . '|bd:' . $bookingDateId . '|ts:' . $timeSlotId;
    }

    private function buildManualConflictLegacyResolutionKey(string $slotKey, int $bookingId): string
    {
        return $slotKey . '|booking:' . $bookingId;
    }
}
