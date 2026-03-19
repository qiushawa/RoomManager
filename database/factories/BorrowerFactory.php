<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Borrower>
 */
class BorrowerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identity_code' => $this->faker->unique()->numerify('########'),
            'name' => mb_substr($this->faker->name(), 0, 20),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->numerify('09########'),
            'department' => $this->faker->word() . '系',
            'is_active' => true,
        ];
    }
}
