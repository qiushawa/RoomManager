<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->time();
        return [
            'name' => $this->faker->unique()->word(),
            'start_time' => $start,
            'end_time' => date('H:i:s', strtotime($start) + 3600),
        ];
    }
}
