<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Database\Seeders\ClassroomSeeder;
use Database\Seeders\BlacklistReasonSeeder;
use Database\Seeders\TimePeriodSeeder;

use App\Models\Borrower;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ClassroomSeeder::class,
            BlacklistReasonSeeder::class,
            TimePeriodSeeder::class,
        ]);

        // 先清除舊有資料，再產生新的借用者資料
        Borrower::delete();
        Borrower::factory()->count(10)->create();
    }
}
