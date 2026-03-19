<?php

namespace Database\Factories;

use App\Models\Blacklist;
use App\Models\BlacklistReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlacklistDetail>
 */
class BlacklistDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blacklist_id' => Blacklist::factory(),
            'reason_id' => BlacklistReason::factory(),
        ];
    }
}
