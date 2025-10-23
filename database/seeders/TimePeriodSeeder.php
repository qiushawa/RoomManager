<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimePeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('time_period')->upsert([
            ['period_id' => 1, 'start' => '08:10:00', 'end' => '09:00:00'],  // 第1節
            ['period_id' => 2, 'start' => '09:10:00', 'end' => '10:00:00'],  // 第2節
            ['period_id' => 3, 'start' => '10:10:00', 'end' => '11:00:00'],  // 第3節
            ['period_id' => 4, 'start' => '11:10:00', 'end' => '12:00:00'],  // 第4節
            ['period_id' => 5, 'start' => '12:00:00', 'end' => '13:20:00'],  // 午休
            ['period_id' => 6, 'start' => '13:20:00', 'end' => '14:10:00'],  // 第5節
            ['period_id' => 7, 'start' => '14:20:00', 'end' => '15:10:00'],  // 第6節
            ['period_id' => 8, 'start' => '15:20:00', 'end' => '16:10:00'],  // 第7節
            ['period_id' => 9, 'start' => '16:20:00', 'end' => '17:10:00'],  // 第8節
            ['period_id' => 10, 'start' => '17:20:00', 'end' => '18:10:00'], // 第9節
            ['period_id' => 11, 'start' => '18:30:00', 'end' => '19:15:00'], // 第10節
            ['period_id' => 12, 'start' => '19:15:00', 'end' => '20:00:00'], // 第11節
            ['period_id' => 13, 'start' => '20:05:00', 'end' => '20:50:00'], // 第12節
            ['period_id' => 14, 'start' => '20:50:00', 'end' => '21:35:00'], // 第13節
            ['period_id' => 15, 'start' => '21:40:00', 'end' => '22:30:00'], // 第14節
        ], ['period_id'], ['start', 'end']);
    }
}
