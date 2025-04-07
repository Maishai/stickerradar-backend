<?php

namespace Database\Factories;

use App\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sticker>
 */
class StickerFactory extends Factory
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
            'lat' => $this->faker->latitude(),
            'lon' => $this->faker->longitude(),
            'state' => $this->faker->randomElement(array_column(State::cases(), 'value')),
            'last_seen' => $this->faker->date('Y-m-d'),
            'filename' => $this->faker->uuid().'.jpeg',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
