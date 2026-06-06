<?php

namespace Tests\Unit\Admin;

use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Models\Semester;
use App\Models\TimeSlot;
use App\Services\Admin\ManualLongTermConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualLongTermConflictServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_conflicts_detects_schedule_and_short_term_conflicts_together(): void
    {
        $service = app(ManualLongTermConflictService::class);

        $semester = Semester::factory()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-30',
        ]);

        $classroom = Classroom::factory()->create();

        $slot1 = TimeSlot::factory()->create(['name' => '1', 'start_time' => '08:10:00', 'end_time' => '09:00:00']);
        $slot2 = TimeSlot::factory()->create(['name' => '2', 'start_time' => '09:10:00', 'end_time' => '10:00:00']);
        $slot3 = TimeSlot::factory()->create(['name' => '3', 'start_time' => '10:10:00', 'end_time' => '11:00:00']);

        $periodToSlotId = [1 => (int) $slot1->id, 2 => (int) $slot2->id, 3 => (int) $slot3->id];

        $manualSchedule = CourseSchedule::factory()->create([
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 1,
            'type' => 'manual',
            'start_date' => '2026-03-01',
            'end_date' => '2026-05-31',
            'course_name' => 'Manual Course',
            'teacher_name' => 'Teacher A',
        ]);
        $manualSchedule->timeSlots()->sync([$slot1->id, $slot2->id]);

        $borrower = Borrower::factory()->create();

        $pendingBooking = Booking::query()->create([
            'borrower_id' => $borrower->id,
            'classroom_id' => $classroom->id,
            'reason' => 'pending overlap',
            'teacher' => 'Pending Teacher',
            'status_enum' => Booking::STATUS_PENDING,
            'level' => Booking::levelForStatus(Booking::STATUS_PENDING),
        ]);
        $pendingDate = $pendingBooking->bookingDates()->create(['date' => '2026-04-06']);
        $pendingDate->timeSlots()->sync([$slot2->id]);

        $approvedBooking = Booking::query()->create([
            'borrower_id' => $borrower->id,
            'classroom_id' => $classroom->id,
            'reason' => 'approved overlap',
            'teacher' => 'Approved Teacher',
            'status_enum' => Booking::STATUS_APPROVED,
            'level' => Booking::levelForStatus(Booking::STATUS_APPROVED),
        ]);
        $approvedDate = $approvedBooking->bookingDates()->create(['date' => '2026-04-13']);
        $approvedDate->timeSlots()->sync([$slot3->id]);

        $validated = [
            'classroom_id' => $classroom->id,
            'day_of_week' => [1],
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'periods' => [1, 2, 3],
            'periods_by_day' => [
                1 => [1, 2, 3],
            ],
        ];

        $result = $service->analyzeConflicts($validated, $semester, $periodToSlotId);

        $this->assertSame(1, $result['schedule_conflict_count']);
        $this->assertSame(1, $result['pending_short_term_count']);
        $this->assertSame(1, $result['approved_short_term_count']);
        $this->assertSame([$pendingBooking->id], $result['pending_conflict_booking_ids']);
        $this->assertSame([$approvedBooking->id], $result['approved_conflict_booking_ids']);

        $kinds = collect($result['conflicts'])->pluck('conflict_kind')->all();
        $this->assertContains('schedule', $kinds);
        $this->assertContains('short_term_pending', $kinds);
        $this->assertContains('short_term_approved', $kinds);

        $scheduleConflict = collect($result['conflicts'])->firstWhere('conflict_kind', 'schedule');
        $this->assertNotNull($scheduleConflict);
        $this->assertSame([1, 2], $scheduleConflict['overlap_periods']);

        $pendingConflict = collect($result['conflicts'])->firstWhere('conflict_kind', 'short_term_pending');
        $this->assertNotNull($pendingConflict);
        $this->assertSame([2], $pendingConflict['overlap_periods']);

        $approvedConflict = collect($result['conflicts'])->firstWhere('conflict_kind', 'short_term_approved');
        $this->assertNotNull($approvedConflict);
        $this->assertSame([3], $approvedConflict['overlap_periods']);
    }

    public function test_analyze_conflicts_ignores_imported_course_when_request_outside_semester_range(): void
    {
        $service = app(ManualLongTermConflictService::class);

        $semester = Semester::factory()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-30',
        ]);

        $classroom = Classroom::factory()->create();
        $slot1 = TimeSlot::factory()->create(['name' => '1', 'start_time' => '08:10:00', 'end_time' => '09:00:00']);
        $periodToSlotId = [1 => (int) $slot1->id];

        $importedCourse = CourseSchedule::factory()->create([
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 1,
            'type' => 'course',
            'start_date' => null,
            'end_date' => null,
        ]);
        $importedCourse->timeSlots()->sync([$slot1->id]);

        $validated = [
            'classroom_id' => $classroom->id,
            'day_of_week' => [1],
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'periods' => [1],
            'periods_by_day' => [1 => [1]],
        ];

        $result = $service->analyzeConflicts($validated, $semester, $periodToSlotId);

        $this->assertSame(0, $result['schedule_conflict_count']);
        $this->assertSame(0, $result['pending_short_term_count']);
        $this->assertSame(0, $result['approved_short_term_count']);
        $this->assertSame([], $result['conflicts']);
    }

    public function test_analyze_conflicts_returns_empty_when_selected_periods_not_mapped_to_slots(): void
    {
        $service = app(ManualLongTermConflictService::class);

        $semester = Semester::factory()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-30',
        ]);

        $classroom = Classroom::factory()->create();

        $validated = [
            'classroom_id' => $classroom->id,
            'day_of_week' => [1, 2],
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'periods' => [99],
            'periods_by_day' => [
                1 => [99],
                2 => [99],
            ],
        ];

        $result = $service->analyzeConflicts($validated, $semester, [1 => 12345]);

        $this->assertSame([], $result['conflicts']);
        $this->assertSame(0, $result['schedule_conflict_count']);
        $this->assertSame(0, $result['pending_short_term_count']);
        $this->assertSame(0, $result['approved_short_term_count']);
        $this->assertSame([1 => [99], 2 => [99]], $result['selected_by_day']);
    }

    public function test_short_term_conflict_contains_consistent_conflict_dates_and_conflict_slots(): void
    {
        $service = app(ManualLongTermConflictService::class);

        $semester = Semester::factory()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-30',
        ]);

        $classroom = Classroom::factory()->create();
        $borrower = Borrower::factory()->create();

        $slot1 = TimeSlot::factory()->create(['name' => '1', 'start_time' => '08:10:00', 'end_time' => '09:00:00']);
        $slot2 = TimeSlot::factory()->create(['name' => '2', 'start_time' => '09:10:00', 'end_time' => '10:00:00']);
        $slot3 = TimeSlot::factory()->create(['name' => '3', 'start_time' => '10:10:00', 'end_time' => '11:00:00']);

        $periodToSlotId = [
            1 => (int) $slot1->id,
            2 => (int) $slot2->id,
            3 => (int) $slot3->id,
        ];

        $pendingBooking = Booking::query()->create([
            'borrower_id' => $borrower->id,
            'classroom_id' => $classroom->id,
            'reason' => 'pending overlap',
            'teacher' => 'Pending Teacher',
            'status_enum' => Booking::STATUS_PENDING,
            'level' => Booking::levelForStatus(Booking::STATUS_PENDING),
        ]);

        $dateA = $pendingBooking->bookingDates()->create(['date' => '2026-04-06']);
        $dateA->timeSlots()->sync([$slot2->id]);

        $dateB = $pendingBooking->bookingDates()->create(['date' => '2026-04-13']);
        $dateB->timeSlots()->sync([$slot2->id]);

        $validated = [
            'classroom_id' => $classroom->id,
            'day_of_week' => [1],
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'periods' => [2],
            'periods_by_day' => [
                1 => [2],
            ],
        ];

        $result = $service->analyzeConflicts($validated, $semester, $periodToSlotId);

        $pendingConflict = collect($result['conflicts'])->firstWhere('conflict_kind', 'short_term_pending');

        $this->assertNotNull($pendingConflict);
        $this->assertSame(['2026-04-06', '2026-04-13'], $pendingConflict['conflict_dates']);
        $this->assertSame('2026-04-06', $pendingConflict['start_date']);
        $this->assertSame('2026-04-13', $pendingConflict['end_date']);
        $this->assertSame([2], $pendingConflict['overlap_periods']);

        $this->assertCount(2, $pendingConflict['conflict_slots']);
        $this->assertSame([
            [
                'slot_key' => '1:2',
                'day_of_week' => 1,
                'period' => 2,
                'date' => '2026-04-06',
                'booking_date_id' => (int) $dateA->id,
                'time_slot_id' => (int) $slot2->id,
            ],
            [
                'slot_key' => '1:2',
                'day_of_week' => 1,
                'period' => 2,
                'date' => '2026-04-13',
                'booking_date_id' => (int) $dateB->id,
                'time_slot_id' => (int) $slot2->id,
            ],
        ], $pendingConflict['conflict_slots']);
    }

    public function test_short_term_conflict_dates_are_unique_and_sorted_even_when_source_dates_repeat(): void
    {
        $service = app(ManualLongTermConflictService::class);

        $semester = Semester::factory()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-30',
        ]);

        $classroom = Classroom::factory()->create();
        $borrower = Borrower::factory()->create();

        $slot1 = TimeSlot::factory()->create(['name' => '1', 'start_time' => '08:10:00', 'end_time' => '09:00:00']);
        $slot2 = TimeSlot::factory()->create(['name' => '2', 'start_time' => '09:10:00', 'end_time' => '10:00:00']);

        $periodToSlotId = [1 => (int) $slot1->id, 2 => (int) $slot2->id];

        $approvedBooking = Booking::query()->create([
            'borrower_id' => $borrower->id,
            'classroom_id' => $classroom->id,
            'reason' => 'approved overlap',
            'teacher' => 'Approved Teacher',
            'status_enum' => Booking::STATUS_APPROVED,
            'level' => Booking::levelForStatus(Booking::STATUS_APPROVED),
        ]);

        $dateA = $approvedBooking->bookingDates()->create(['date' => '2026-04-20']);
        $dateA->timeSlots()->sync([$slot1->id, $slot2->id]);

        $dateB = $approvedBooking->bookingDates()->create(['date' => '2026-04-06']);
        $dateB->timeSlots()->sync([$slot1->id]);

        $validated = [
            'classroom_id' => $classroom->id,
            'day_of_week' => [1],
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'periods' => [1, 2],
            'periods_by_day' => [
                1 => [2, 1, 2],
            ],
        ];

        $result = $service->analyzeConflicts($validated, $semester, $periodToSlotId);

        $approvedConflict = collect($result['conflicts'])->firstWhere('conflict_kind', 'short_term_approved');

        $this->assertNotNull($approvedConflict);
        $this->assertSame(['2026-04-06', '2026-04-20'], $approvedConflict['conflict_dates']);
        $this->assertSame('2026-04-06', $approvedConflict['start_date']);
        $this->assertSame('2026-04-20', $approvedConflict['end_date']);
        $this->assertSame([1, 2], $approvedConflict['overlap_periods']);

        $slotKeys = collect($approvedConflict['conflict_slots'])
            ->map(fn ($slot) => ($slot['booking_date_id'] ?? 0) . ':' . ($slot['time_slot_id'] ?? 0))
            ->values()
            ->all();

        $this->assertCount(count(array_unique($slotKeys)), $slotKeys);
    }
}
