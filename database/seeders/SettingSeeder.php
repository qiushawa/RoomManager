<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'semester_start',
                'value' => '02-23',
                'description' => '學期開始日期 (MM-DD)',
            ],
            [
                'key' => 'semester_end',
                'value' => '06-30',
                'description' => '學期結束日期 (MM-DD)',
            ],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
