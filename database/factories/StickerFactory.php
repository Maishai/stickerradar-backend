<?php

namespace Database\Factories;

use App\Models\StateHistory;
use App\Models\Sticker;
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
            'filename' => $this->faker->uuid().'.jpeg',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Sticker $sticker) {
            StateHistory::factory()
                ->create(["sticker_id" => $sticker->id]);
        });
    }
}
