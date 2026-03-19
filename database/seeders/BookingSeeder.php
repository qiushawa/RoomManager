<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $borrowers = Borrower::all();
        $classrooms = Classroom::where('is_active', true)->get();
        $timeSlots = TimeSlot::where('name', '!=', '午休')->get();
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

        if ($borrowers->isEmpty() || $classrooms->isEmpty() || $timeSlots->isEmpty()) {
            return;
        }

        // 產生 20 筆預約
        for ($i = 0; $i < 20; $i++) {
            // 隨機選 1~3 節連續課
            $slotsCount = rand(1, 3);
            // 避免選到最後幾節導致越界，所以起始節次的上限是 count - slotsCount
            if ($timeSlots->count() <= $slotsCount) {
                $startIdx = 0;
            } else {
                $startIdx = rand(0, $timeSlots->count() - $slotsCount - 1);
            }

            $selectedSlots = $timeSlots->slice($startIdx, $slotsCount);

            $booking = Booking::factory()->create([
                'borrower_id' => $borrowers->random()->id,
                'classroom_id' => $classrooms->random()->id,
                'teacher' => $instructorNames[array_rand($instructorNames)],
                'date' => now()->addDays(rand(-5, 10)), // 過去或未來幾天
            ]);

            $booking->timeSlots()->attach($selectedSlots->pluck('id'));
        }
    }
}
