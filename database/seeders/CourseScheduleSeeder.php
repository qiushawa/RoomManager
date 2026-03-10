<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Models\Semester;
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
        // 課程名稱列表 資工系常見課程，實際上不會用到這些名稱，因為前端不顯示課程名稱，但這樣可以讓資料看起來更真實一些
        $courseNames = [
            '程式設計入門',
            '資料結構與演算法',
            '作業系統概論',
            '計算機網路',
            '軟體工程',
            '資料庫系統',
            '人工智慧導論',
            '機器學習基礎',
            '深度學習實務',
            '自然語言處理',
            '電腦視覺應用',
            '行動應用開發',
            '雲端運算概論',
            '大數據分析',
            '資訊安全基礎',
            '人機互動設計',
            '軟體專案管理',
            '計算理論與複雜度',
            '分散式系統',
            '物聯網應用',
        ];
        // 指導老師名稱列表
        $instructorNames = [
            '吳祥維',
            '陳怡君',
            '李明哲',
            '張雅婷',
            '林志明',
            '黃美玲',
            '劉建宏',
            '蔡佳穎',
            '王大明',
            '許文彬',
            '鄭雅文',
            '徐志強',
            '何佩珊',
            '羅志遠',
            '楊雅婷'
        ];
        $classrooms = Classroom::where('is_active', true)->get();
        $timeSlots = TimeSlot::where('name', '!=', '午休')->get(); // 排除中午
        $semesters = Semester::all();

        if ($classrooms->isEmpty() || $timeSlots->isEmpty() || $semesters->isEmpty()) {
            return;
        }
        foreach ($classrooms as $classroom) {
            // 每間教室每學期排 20-30 堂課
            foreach ($semesters as $semester) {
                $coursesCount = rand(20, 30);

                for ($i = 0; $i < $coursesCount; $i++) {
                    // 隨機選一個時段
                    $startSlot = $timeSlots->random();
                    CourseSchedule::factory()->create([
                        'semester_id' => $semester->id,
                        'classroom_id' => $classroom->id,
                        'start_slot_id' => $startSlot->id,
                        'end_slot_id' => $startSlot->id, // 簡化：單節課
                        'day_of_week' => rand(1, 6), // 週一到週六
                        'course_name' => $courseNames[array_rand($courseNames)],
                        'teacher_name' => $instructorNames[array_rand($instructorNames)],
                    ]);
                }
            }
        }
    }
}
