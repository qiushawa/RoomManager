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
            'semester_id' => \App\Models\Semester::factory(),
            'classroom_id' => Classroom::factory(),
            'course_name' => $this->faker->jobTitle() . '課程',
            'teacher_name' => $this->faker->name(),
            'day_of_week' => $this->faker->numberBetween(1, 7),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\CourseSchedule $schedule) {
            $slotIds = TimeSlot::query()
                ->where('name', '!=', '午休')
                ->inRandomOrder()
                ->limit(1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!empty($slotIds)) {
                $schedule->timeSlots()->sync($slotIds);
            }
        });
    }
}
