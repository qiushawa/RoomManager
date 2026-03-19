<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semesters = [
            [
                'academic_year' => 114,
                'semester' => 1,
                'start_date' => '2025-09-01',
                'end_date' => '2026-01-31',
            ],
            [
                'academic_year' => 114,
                'semester' => 2,
                'start_date' => '2026-02-23',
                'end_date' => '2026-06-30',
            ],
        ];

        foreach ($semesters as $semester) {
            Semester::updateOrCreate(
                ['academic_year' => $semester['academic_year'], 'semester' => $semester['semester']],
                $semester
            );
        }
    }
}
