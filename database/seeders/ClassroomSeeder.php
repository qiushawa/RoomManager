<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Classroom::factory()->createMany([
            ['code' => 'BGC0305', 'name' => '嵌入式系統軟體設計實驗室', 'is_active' => true],
            ['code' => 'BGC0402', 'name' => '會議室', 'is_active' => true],
            ['code' => 'BGC0501', 'name' => '基本電學與證照實驗室', 'is_active' => true],
            ['code' => 'BGC0508', 'name' => '研討室', 'is_active' => true],
            ['code' => 'BGC0513', 'name' => '生物資訊實驗室', 'is_active' => true],
            ['code' => 'BGC0601', 'name' => 'IC設計實驗室', 'is_active' => true],
            ['code' => 'BGC0614', 'name' => '數位學習實驗室', 'is_active' => true],
        ]);
    }
}
