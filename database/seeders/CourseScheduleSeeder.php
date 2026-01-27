<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = Classroom::where('is_active', true)->get();
        $timeSlots = TimeSlot::where('name', '!=', '午休')->get(); // 排除中午

        if ($classrooms->isEmpty() || $timeSlots->isEmpty()) {
            return;
        }
        foreach ($classrooms as $classroom) {
            // 每間教室排 5-10 堂課
            $coursesCount = rand(5, 10);

            for ($i = 0; $i < $coursesCount; $i++) {
                // 隨機選一個時段
                $startSlot = $timeSlots->random();
                // 假設課程長度 1-3 小時，但要確保連續時段存在
                // 這裡簡化，先假設都只佔用 1 個時段，或者確保有下一個時段的邏輯比較複雜
                // 為了種子簡單，我們先只排 1 個時段的課，或者亂數如果選到最後一個時段就只排1節
                
                CourseSchedule::factory()->create([
                    'classroom_id' => $classroom->id,
                    'start_slot_id' => $startSlot->id,
                    'end_slot_id' => $startSlot->id, // 簡化：單節課
                    'day_of_week' => rand(1, 6), // 週一到週六
                ]);
            }
        }
    }
}
