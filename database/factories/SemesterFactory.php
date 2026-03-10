<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Semester>
 */
class SemesterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $semester = $this->faker->randomElement([1, 2]);
        $academicYear = $this->faker->numberBetween(112, 115);

        if ($semester === 1) {
            $startDate = ($academicYear + 1911) . '-09-01';
            $endDate = ($academicYear + 1912) . '-01-31';
        } else {
            $startDate = ($academicYear + 1912) . '-02-15';
            $endDate = ($academicYear + 1912) . '-06-30';
        }

        return [
            'academic_year' => $academicYear,
            'semester' => $semester,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}
