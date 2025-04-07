<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * By default, a tag will not have a super_tag.
     * Use the 'withSuperTag' state to create a tag with a super_tag.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->unique()->words(2, true),
            'super_tag' => null, // Default to no super_tag
            'color' => $this->faker->hexColor(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Define a state where the tag has a super tag.
     * This will create a new Tag to be the super tag.
     *
     * @return $this
     */
    public function withSuperTag()
    {
        return $this->state(function (array $attributes) {
            return [
                'super_tag' => Tag::factory()->create()->id,
            ];
        });
    }

    /**
     * Define a state where the tag has a specific super tag ID.
     *
     * @return $this
     */
    public function withSpecificSuperTag(string $superTagId)
    {
        return $this->state(function (array $attributes) use ($superTagId) {
            return [
                'super_tag' => $superTagId,
            ];
        });
    }
}
