<?php

namespace Database\Factories;

use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'borrower_id' => Borrower::factory(),
            'classroom_id' => Classroom::factory(),
            'start_slot_id' => TimeSlot::factory(),
            'end_slot_id' => TimeSlot::factory(),
            'reason' => $this->faker->sentence(),
            'teacher' => $this->faker->name(),
            'status' => $this->faker->numberBetween(0, 3),
            'date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
