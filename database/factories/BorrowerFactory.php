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
            'student_id' => $this->faker->unique()->numerify('########'),
            'email' => $this->faker->unique()->safeEmail(),
            'name' => mb_substr($this->faker->name(), 0, 4),
            'cellphone' => $this->faker->numerify('##########'),
            'department' => $this->faker->randomElement(['資訊工程系', '電子工程系', '機械工程系', '化學工程系', '土木工程系']),
        ];
    }
}
