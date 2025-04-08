<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagApiControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_display_a_listing_of_tags()
    {
        // Arrange
        $tags = Tag::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/tags');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'color',
                    ],
                ],
            ]);

        foreach ($tags as $tag) {
            $response->assertJsonFragment([
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ]);
        }
    }

    public function test_returns_an_empty_array_when_there_are_no_tags()
    {
        // Act
        $response = $this->getJson('/api/tags');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_can_display_a_tree_of_tags()
    {
        // Arrange
        $parentTag = Tag::factory()->create(['name' => 'Parent Tag']);
        $childTag1 = Tag::factory()->withSpecificSuperTag($parentTag->id)->create(['name' => 'Child Tag 1']);
        $childTag2 = Tag::factory()->withSpecificSuperTag($parentTag->id)->create(['name' => 'Child Tag 2']);
        $grandchildTag = Tag::factory()->withSpecificSuperTag($childTag1->id)->create(['name' => 'Grandchild Tag']);

        // Act
        $response = $this->getJson('/api/tags/tree');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'color',
                    'children' => [
                        '*' => [
                            'id',
                            'name',
                            'color',
                            'children',
                        ],
                    ],
                ],
            ]);

        $response->assertJsonFragment([
            'id' => $parentTag->id,
            'name' => $parentTag->name,
            'color' => $parentTag->color,
            'children' => [
                [
                    'id' => $childTag1->id,
                    'name' => $childTag1->name,
                    'color' => $childTag1->color,
                    'children' => [
                        [
                            'id' => $grandchildTag->id,
                            'name' => $grandchildTag->name,
                            'color' => $grandchildTag->color,
                            'children' => [],
                        ],
                    ],
                ],
                [
                    'id' => $childTag2->id,
                    'name' => $childTag2->name,
                    'color' => $childTag2->color,
                    'children' => [],
                ],
            ],
        ]);
    }

    public function test_returns_an_empty_array_for_tag_tree_when_no_tags_exist()
    {
        // Act
        $response = $this->getJson('/api/tags/tree');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_can_display_a_specific_tag()
    {
        // Arrange
        $tag = Tag::factory()->create();

        // Act
        $response = $this->getJson("/api/tags/{$tag->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'color',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ],
            ]);
    }

    public function test_returns_404_when_trying_to_display_a_non_existent_tag()
    {
        // Act
        $response = $this->getJson('/api/tags/'.$this->faker->uuid());

        // Assert
        $response->assertStatus(404);
    }
}
