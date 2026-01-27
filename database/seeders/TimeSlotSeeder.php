<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TimeSlot::factory()->createMany([
            ['name' => '第一節', 'start_time' => '08:10', 'end_time' => '09:00'],
            ['name' => '第二節', 'start_time' => '09:10', 'end_time' => '10:00'],
            ['name' => '第三節', 'start_time' => '10:10', 'end_time' => '11:00'],
            ['name' => '第四節', 'start_time' => '11:10', 'end_time' => '12:00'],
            ['name' => '午休', 'start_time' => '12:00', 'end_time' => '13:20'],
            ['name' => '第五節', 'start_time' => '13:20', 'end_time' => '14:10'],
            ['name' => '第六節', 'start_time' => '14:20', 'end_time' => '15:10'],
            ['name' => '第七節', 'start_time' => '15:20', 'end_time' => '16:10'],
            ['name' => '第八節', 'start_time' => '16:20', 'end_time' => '17:10'],
            ['name' => '第九節', 'start_time' => '17:20', 'end_time' => '18:10'],
            ['name' => '第十節', 'start_time' => '18:30', 'end_time' => '19:15'],
            ['name' => '第十一節', 'start_time' => '19:15', 'end_time' => '20:00'],
            ['name' => '第十二節', 'start_time' => '20:05', 'end_time' => '20:50'],
            ['name' => '第十三節', 'start_time' => '20:50', 'end_time' => '21:35'],
            ['name' => '第十四節', 'start_time' => '21:40', 'end_time' => '22:30'],
        ]);

    }
}
