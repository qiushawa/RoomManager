<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class BlacklistReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('blacklist_reason')->upsert([
            ['reason_id' => 1, 'reason' => '大門未鎖上'],
            ['reason_id' => 2, 'reason' => '電源未關閉'],
            ['reason_id' => 3, 'reason' => '冷氣未關閉'],
            ['reason_id' => 4, 'reason' => '風扇未關閉'],
            ['reason_id' => 5, 'reason' => '電燈未關閉'],
            ['reason_id' => 6, 'reason' => '未維持環境整潔'],
            ['reason_id' => 7, 'reason' => '設備損壞'],
        ], ['reason_id'], ['reason']);
    }
}
