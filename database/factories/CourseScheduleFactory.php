<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseSchedule>
 */
class CourseScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'course_name' => $this->faker->jobTitle() . '課程',
            'teacher_name' => $this->faker->name(),
            'day_of_week' => $this->faker->numberBetween(1, 7),
            'start_slot_id' => TimeSlot::factory(),
            'end_slot_id' => TimeSlot::factory(),
        ];
    }
}
