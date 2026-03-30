<?php

namespace Tests\Unit\Admin;

use App\Models\TimeSlot;
use App\Services\Admin\LongTermCourseScheduleService;
use App\Services\Admin\ManualLongTermConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_period_to_slot_id_map_excludes_lunch_break_by_default(): void
    {
        $slot1 = TimeSlot::factory()->create([
            'name' => '1',
            'start_time' => '08:10:00',
            'end_time' => '09:00:00',
        ]);
        TimeSlot::factory()->create([
            'name' => '午休',
            'start_time' => '12:10:00',
            'end_time' => '13:00:00',
        ]);
        $slot2 = TimeSlot::factory()->create([
            'name' => '2',
            'start_time' => '13:10:00',
            'end_time' => '14:00:00',
        ]);

        $service = app(LongTermCourseScheduleService::class);
        $map = $service->buildPeriodToSlotIdMap();

        $this->assertSame([
            1 => $slot1->id,
            2 => $slot2->id,
        ], $map);
    }

    public function test_build_period_to_slot_id_map_can_include_lunch_break(): void
    {
        $slot1 = TimeSlot::factory()->create([
            'name' => '1',
            'start_time' => '08:10:00',
            'end_time' => '09:00:00',
        ]);
        $lunch = TimeSlot::factory()->create([
            'name' => '午休',
            'start_time' => '12:10:00',
            'end_time' => '13:00:00',
        ]);
        $slot2 = TimeSlot::factory()->create([
            'name' => '2',
            'start_time' => '13:10:00',
            'end_time' => '14:00:00',
        ]);

        $service = app(LongTermCourseScheduleService::class);
        $map = $service->buildPeriodToSlotIdMap(includeLunchBreak: true);

        $this->assertSame([
            1 => $slot1->id,
            2 => $lunch->id,
            3 => $slot2->id,
        ], $map);
    }

    public function test_extract_building_code_and_schedule_type_normalization(): void
    {
        $courseService = app(LongTermCourseScheduleService::class);
        $conflictService = app(ManualLongTermConflictService::class);

        $this->assertSame('GC', $courseService->extractBuildingCode('bgc102'));
        $this->assertSame('CB', $courseService->extractBuildingCode('CB-501'));
        $this->assertSame('RA', $courseService->extractBuildingCode('ra301'));
        $this->assertNull($courseService->extractBuildingCode('XH101'));

        $this->assertSame('course', $conflictService->resolveScheduleType('course'));
        $this->assertSame('borrowed', $conflictService->resolveScheduleType('borrowed'));
        $this->assertSame('manual', $conflictService->resolveScheduleType('unknown'));
        $this->assertSame('manual', $conflictService->resolveScheduleType(null));
    }
}
