<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Database\Seeders\ClassroomSeeder;
use Database\Seeders\BlacklistReasonSeeder;
use Database\Seeders\TimePeriodSeeder;

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

    }
}
