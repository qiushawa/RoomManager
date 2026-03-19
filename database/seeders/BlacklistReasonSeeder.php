<?php

namespace Database\Seeders;

use App\Models\BlacklistReason;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlacklistReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BlacklistReason::factory()->createMany([
            ['reason' => '大門未鎖上'],
            ['reason' => '電源未關閉'],
            ['reason' => '冷氣未關閉'],
            ['reason' => '電燈未關閉'],
            ['reason' => '未維持環境整潔'],
            ['reason' => '設備損壞'],
        ]);
    }
}
