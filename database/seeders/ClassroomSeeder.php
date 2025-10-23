<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('classroom')->insert([
            ['active' => 1, 'room_id' => 'BGC0501', 'room_name' => '基本電學與證照實驗室'],
            ['active' => 1, 'room_id' => 'BGC0513', 'room_name' => '生物資訊實驗室'],
            ['active' => 1, 'room_id' => 'BGC0601', 'room_name' => '系統設計實驗室'],
            ['active' => 1, 'room_id' => 'BGC0614', 'room_name' => '多功能教學實驗室'],
            ['active' => 1, 'room_id' => 'BCB0303', 'room_name' => '資工科普通教室'],
            ['active' => 1, 'room_id' => 'BCB0305', 'room_name' => '數位邏輯實驗室'],
            ['active' => 1, 'room_id' => 'BRA0102', 'room_name' => '人工智慧創新實驗室'],
            ['active' => 1, 'room_id' => 'BRA0201', 'room_name' => '智慧運算與資訊安全實驗室'],
        ]);
    }
}
