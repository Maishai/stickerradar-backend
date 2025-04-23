<?php

namespace Database\Factories;

use App\Models\StateHistory;
use App\Models\Sticker;
use App\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StateHistory>
 */
class StateHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'sticker_id' => Sticker::factory(),
            'state' => $this->faker->randomElement(array_column(State::cases(), 'value')),
            'last_seen' => $this->faker->dateTimeBetween('-30 days'),
            'created_at' => $this->faker->dateTimeBetween('-30 days'),
            'updated_at' => now(),
        ];
    }
}
