<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Models\Manager;
use App\Models\Semester;
use App\Models\TimeSlot;
use App\Services\Admin\LongTermCourseScheduleService;
use App\Services\Admin\ManualLongTermConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LongTermScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private Classroom $room;

    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 14)->setTime(15, 0));
        $this->semester = Semester::create(['academic_year' => 115, 'semester' => 1, 'start_date' => '2026-09-01', 'end_date' => '2027-01-31']);
        $this->room = Classroom::factory()->create(['code' => 'BGC101', 'is_active' => true]);
        $this->slot = TimeSlot::factory()->create(['name' => '1', 'start_time' => '08:00', 'end_time' => '09:00']);
        $admin = Manager::forceCreate(['username' => 'schedule-test', 'password' => bcrypt('test'), 'name' => 'Test', 'email' => 'test@example.test']);
        $this->actingAs($admin, 'admin');
    }

    private function row(array $overrides = []): array
    {
        return array_merge(['semester_id' => $this->semester->id, 'classroom_id' => $this->room->id, 'type' => 'course', 'course_name' => '測試課程', 'teacher_name' => '測試教師', 'day_of_week' => 1, 'start_date' => '2026-09-01', 'end_date' => '2027-01-31', 'time_slot_ids' => [$this->slot->id]], $overrides);
    }

    private function endpoint(string $suffix = ''): string
    {
        return route('admin.longTermBorrowing').$suffix;
    }

    private function schedule(array $overrides = []): CourseSchedule
    {
        $data = $this->row($overrides);
        $schedule = CourseSchedule::create(collect($data)->except('time_slot_ids')->all());
        $schedule->timeSlots()->sync($data['time_slot_ids']);

        return $schedule;
    }

    public function test_default_semester_includes_end_day_and_falls_forward(): void
    {
        $future = Semester::create(['academic_year' => 115, 'semester' => 2, 'start_date' => '2027-02-15', 'end_date' => '2027-06-30']);
        $service = app(LongTermCourseScheduleService::class);
        $this->travelTo(now()->setDate(2026, 9, 1)->setTime(0, 0));
        $this->assertSame($this->semester->id, $service->resolveCurrentOrNearestFutureSemester()->id);
        $this->travelTo(now()->setDate(2027, 1, 31)->setTime(23, 59));
        $this->assertSame($this->semester->id, $service->resolveCurrentOrNearestFutureSemester()->id);
        $this->travelTo(now()->setDate(2027, 2, 1));
        $this->assertSame($future->id, $service->resolveCurrentOrNearestFutureSemester()->id);
        $this->travelTo(now()->setDate(2027, 7, 1));
        $this->assertNull($service->resolveCurrentOrNearestFutureSemester());
    }

    public function test_import_preserves_other_semesters_rooms_and_manual_records(): void
    {
        $old = $this->schedule();
        $manual = $this->schedule(['type' => 'manual', 'day_of_week' => 2]);
        $borrowed = $this->schedule(['type' => 'borrowed', 'day_of_week' => 3]);
        $otherRoom = $this->schedule(['classroom_id' => Classroom::factory()->create()->id]);
        $past = Semester::create(['academic_year' => 114, 'semester' => 2, 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $otherSemester = $this->schedule(['semester_id' => $past->id, 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        app(LongTermCourseScheduleService::class)->replaceSemesterSchedulesForClassrooms($this->semester, collect([$this->room->id]), [$this->row(['course_name' => '新課表'])]);
        $this->assertModelMissing($old);
        foreach ([$manual, $borrowed, $otherRoom, $otherSemester] as $row) {
            $this->assertModelExists($row);
        }
        $this->assertDatabaseHas('course_schedules', ['semester_id' => $this->semester->id, 'course_name' => '新課表']);
    }

    public function test_empty_import_keeps_existing_courses(): void
    {
        $existing = $this->schedule();
        try {
            app(LongTermCourseScheduleService::class)->replaceSemesterSchedulesForClassrooms($this->semester, collect([$this->room->id]), []);
            $this->fail('Empty import must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('import', $e->errors());
        }
        $this->assertModelExists($existing);
    }

    public function test_room_without_returned_courses_is_not_cleared_in_mixed_import(): void
    {
        $otherRoom = Classroom::factory()->create();
        $existing = $this->schedule(['classroom_id' => $otherRoom->id]);
        app(LongTermCourseScheduleService::class)->replaceSemesterSchedulesForClassrooms($this->semester, collect([$this->room->id, $otherRoom->id]), [$this->row()]);
        $this->assertModelExists($existing);
    }

    public function test_cross_semester_payload_cannot_replace_any_courses(): void
    {
        $existing = $this->schedule();
        try {
            app(LongTermCourseScheduleService::class)->replaceSemesterSchedulesForClassrooms($this->semester, collect([$this->room->id]), [$this->row(['semester_id' => 9999])]);
            $this->fail('Out-of-scope import must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('import', $e->errors());
        }
        $this->assertModelExists($existing);
    }

    public function test_import_conflict_rolls_back_and_keeps_manual_record(): void
    {
        $old = $this->schedule();
        $manual = $this->schedule(['type' => 'manual']);
        try {
            app(LongTermCourseScheduleService::class)->replaceSemesterSchedulesForClassrooms($this->semester, collect([$this->room->id]), [$this->row()]);
            $this->fail('Conflict must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('conflict', $e->errors());
        }
        $this->assertModelExists($old);
        $this->assertModelExists($manual);
    }

    public function test_import_endpoints_require_explicit_semester(): void
    {
        foreach (['preview', 'import'] as $operation) {
            $this->postJson($this->endpoint('/'.$operation), ['classroom_ids' => [$this->room->id]])->assertUnprocessable()->assertJsonValidationErrors('semester_id');
        }
    }

    public function test_selected_semester_is_used_for_external_preview_and_import(): void
    {
        $past = Semester::create(['academic_year' => 114, 'semester' => 2, 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
        $rows = [$this->row(['semester_id' => $past->id, 'start_date' => '2026-02-01', 'end_date' => '2026-06-30'])];
        $this->partialMock(LongTermCourseScheduleService::class, function ($mock) use ($past, $rows) {
            $mock->shouldReceive('fetchImportedSchedulesForClassrooms')->twice()->withArgs(fn ($semester, $rooms, $map) => $semester->id === $past->id)->andReturn($rows);
        });
        $payload = ['semester_id' => $past->id, 'classroom_ids' => [$this->room->id]];
        $this->postJson($this->endpoint('/preview'), $payload)->assertOk()->assertJsonPath('semester_range.start_date', '2026-02-01');
        $this->post($this->endpoint('/import'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('course_schedules', ['semester_id' => $past->id]);
        $this->assertDatabaseMissing('course_schedules', ['semester_id' => $this->semester->id]);
    }

    public function test_revoke_only_removes_selected_semester_courses(): void
    {
        $course = $this->schedule();
        $manual = $this->schedule(['type' => 'manual']);
        $this->deleteJson($this->endpoint('/import/').$this->room->id, ['semester_id' => $this->semester->id])->assertOk();
        $this->assertModelMissing($course);
        $this->assertModelExists($manual);
    }

    public function test_all_types_support_non_contiguous_slots_and_delete(): void
    {
        $slot3 = TimeSlot::factory()->create(['name' => '3', 'start_time' => '10:00', 'end_time' => '11:00']);
        foreach (['course', 'manual', 'borrowed'] as $type) {
            $record = $this->schedule(['type' => $type]);
            $this->patchJson($this->endpoint('/records/').$record->id, $this->row(['course_name' => '更新資訊', 'time_slot_ids' => [$this->slot->id, $slot3->id]]))->assertOk();
            $this->assertSame([$this->slot->id, $slot3->id], $record->fresh()->timeSlots->modelKeys());
            $this->assertSame($type, $record->fresh()->type);
            $this->deleteJson($this->endpoint('/records/').$record->id)->assertOk();
            $this->assertDatabaseMissing('course_schedule_time_slots', ['course_schedule_id' => $record->id]);
        }
    }

    public function test_metadata_edit_ignores_existing_conflict_but_new_time_conflict_fails(): void
    {
        $record = $this->schedule();
        $this->schedule(['type' => 'manual']);
        $this->schedule(['type' => 'manual', 'day_of_week' => 2]);
        $this->patchJson($this->endpoint('/records/').$record->id, $this->row(['course_name' => '新名稱']))->assertOk();
        $this->patchJson($this->endpoint('/records/').$record->id, $this->row(['day_of_week' => 2]))->assertUnprocessable()->assertJsonValidationErrors('conflict');
        $this->assertSame(1, (int) $record->fresh()->day_of_week);
        $this->patchJson($this->endpoint('/records/').$record->id, $this->row(['end_date' => '2027-02-01']))->assertUnprocessable()->assertJsonValidationErrors('end_date');
    }

    public function test_short_term_booking_blocks_new_schedule_time(): void
    {
        $record = $this->schedule();
        $booking = Booking::create(['borrower_id' => Borrower::factory()->create()->id, 'classroom_id' => $this->room->id, 'reason' => 'Test', 'teacher' => 'Test', 'status_enum' => 'approved']);
        $date = $booking->bookingDates()->create(['date' => '2026-09-15']);
        $date->timeSlots()->sync([$this->slot->id]);
        $this->patchJson($this->endpoint('/records/').$record->id, $this->row(['day_of_week' => 2]))->assertUnprocessable()->assertJsonValidationErrors('conflict');
        $this->assertSame('approved', $booking->fresh()->status_enum);
    }

    public function test_historical_records_search_filter_and_pagination_without_current_semester(): void
    {
        $record = $this->schedule(['type' => 'borrowed', 'course_name' => '搜尋目標']);
        $this->schedule(['type' => 'manual']);
        $this->travelTo(now()->setDate(2028, 9, 1));
        $this->getJson($this->endpoint('/records?type=borrowed&building=BGC&search=搜尋目標&semester_id=').$this->semester->id)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $record->id);
        for ($i = 0; $i < 20; $i++) {
            $this->schedule();
        }
        $this->getJson($this->endpoint('/records?page=2'))->assertOk()->assertJsonPath('total', 22)->assertJsonCount(2, 'data');
        $this->get($this->endpoint())->assertOk();
    }

    public function test_edited_imported_dates_are_respected_by_manual_conflict_analysis(): void
    {
        $this->schedule(['end_date' => '2026-09-30']);
        $analysis = app(ManualLongTermConflictService::class)->analyzeConflicts([
            'classroom_id' => $this->room->id, 'day_of_week' => [1], 'periods' => [1],
            'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
        ], $this->semester, [1 => $this->slot->id]);
        $this->assertSame(0, $analysis['schedule_conflict_count']);
    }

    public function test_new_management_routes_remain_protected(): void
    {
        auth()->guard('admin')->logout();
        $this->getJson($this->endpoint('/records'))->assertUnauthorized();
    }

    public function test_editor_occupancy_excludes_self_but_keeps_other_occupants(): void
    {
        $self = $this->schedule(['course_name' => '正在編輯']);
        $this->getJson($this->endpoint('/records/'.$self->id.'/availability?classroom_id='.$this->room->id.'&start_date=2026-09-01&end_date=2026-09-30'))
            ->assertOk()->assertJsonMissing(['title' => '正在編輯']);
        $this->schedule(['course_name' => '其他課程', 'teacher_name' => '其他教師']);
        $this->getJson($this->endpoint('/records/'.$self->id.'/availability?classroom_id='.$this->room->id.'&start_date=2026-09-01&end_date=2026-09-30'))
            ->assertOk()->assertJsonPath('occupied_data.1.1.title', '其他課程')
            ->assertJsonPath('occupied_data.1.1.details.0.instructor', '其他教師');
        $this->getJson($this->endpoint('/records/'.$self->id.'/availability?classroom_id='.$this->room->id.'&start_date=2028-01-01&end_date=2028-01-31'))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
    }

    public function test_editor_occupancy_follows_selected_classroom(): void
    {
        $self = $this->schedule();
        $other = Classroom::factory()->create();
        $this->schedule(['classroom_id' => $other->id, 'course_name' => '另一間教室的課表']);
        $this->getJson($this->endpoint('/records/'.$self->id.'/availability?classroom_id='.$other->id.'&start_date=2026-09-01&end_date=2026-09-30'))
            ->assertOk()->assertJsonPath('occupied_data.1.1.title', '另一間教室的課表');
    }

    public function test_recurring_grid_includes_later_weeks_and_all_overlapping_occupants(): void
    {
        $self = $this->schedule();
        $this->schedule(['course_name' => '月底課程', 'start_date' => '2026-09-28', 'end_date' => '2026-09-28']);
        $this->schedule(['course_name' => '同格另一課程', 'start_date' => '2026-09-28', 'end_date' => '2026-09-28']);
        $this->schedule(['course_name' => '範圍之外', 'start_date' => '2026-10-05', 'end_date' => '2026-10-05']);
        $this->getJson($this->endpoint('/records/'.$self->id.'/availability?classroom_id='.$this->room->id.'&start_date=2026-09-01&end_date=2026-09-30'))
            ->assertOk()->assertJsonCount(2, 'occupied_data.1.1.details')
            ->assertJsonPath('occupied_data.1.1.details.0.dates', ['2026-09-28'])
            ->assertJsonMissing(['title' => '範圍之外']);
        $this->getJson($this->endpoint('/records/'.$self->id.'/availability?classroom_id='.$this->room->id.'&start_date=2026-09-01&end_date=2026-09-20'))
            ->assertOk()->assertJsonPath('occupied_data', []);
    }

    public function test_manual_grid_shows_range_occupancy_before_any_slots_are_selected(): void
    {
        $this->schedule(['course_name' => '本學期課程']);
        $this->schedule(['course_name' => '月底借用', 'type' => 'borrowed', 'day_of_week' => 2, 'start_date' => '2026-09-29', 'end_date' => '2026-09-29']);
        $url = $this->endpoint('/manual/availability?classroom_id='.$this->room->id.'&start_date=2026-09-01&end_date=2026-09-30');
        $this->getJson($url)->assertOk()
            ->assertJsonPath('occupied_data.1.1.title', '本學期課程')
            ->assertJsonPath('occupied_data.2.1.details.0.dates', ['2026-09-29']);
        $this->getJson($this->endpoint('/manual/availability?classroom_id='.$this->room->id.'&start_date=2026-09-01&end_date=2026-09-20'))
            ->assertOk()->assertJsonMissing(['title' => '月底借用']);
        $this->getJson($this->endpoint('/manual/availability'))->assertUnprocessable()->assertJsonValidationErrors(['classroom_id', 'start_date', 'end_date']);
    }
}
