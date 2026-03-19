<?php

namespace Database\Seeders;

use App\Models\Borrower;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BorrowerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // 申請人名稱列表 (實際上不會用到這些名稱，因為前端不顯示申請人名稱，但這樣可以讓資料看起來更真實一些)
        $applicantNames = [
            '陳小明',
            '李小華',
            '王大明',
            '林志玲'
        ];
        Borrower::factory()->createMany([
            ['name' => $applicantNames[0], 'email' => 'chenxiaoming@example.com'],
            ['name' => $applicantNames[1], 'email' => 'lixiaohua@example.com'],
            ['name' => $applicantNames[2], 'email' => 'wangdaming@example.com'],
            ['name' => $applicantNames[3], 'email' => 'linzhiling@example.com']
        ]);
    }
}
