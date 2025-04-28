<?php

namespace Tests\Feature;

use App\Models\Sticker;
use App\Models\Tag;
use App\StickerInclusion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ClusterApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cluster_stickers_by_parent_single_tag()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();
        $rechts = Tag::factory(['super_tag' => $politik->id])->create();
        $gruene = Tag::factory(['super_tag' => $links->id])->create();
        $antifa = Tag::factory(['super_tag' => $links->id])->create();
        $toleranz = Tag::factory(['super_tag' => $links->id])->create();
        $pride = Tag::factory(['super_tag' => $toleranz->id])->create();
        $queerphob = Tag::factory(['super_tag' => $rechts->id])->create();
        $transphob = Tag::factory(['super_tag' => $queerphob->id])->create();

        // Linke Cluster
        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $linker_sticker->tags()->sync([$links->id]);
        $toleranz_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16.0000001])->create();
        $toleranz_sticker->tags()->sync([$toleranz->id]);
        $pride_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16])->create();
        $pride_sticker->tags()->sync([$pride->id]);
        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16.0000001])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $antifa_sticker = Sticker::factory(['lat' => 16.00000005, 'lon' => 16.00000005])->create();
        $antifa_sticker->tags()->sync([$antifa->id]);
        // Rechte Cluster
        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);
        $queerphob_sticker = Sticker::factory(['lat' => 20.0000001, 'lon' => 20])->create();
        $queerphob_sticker->tags()->sync([$queerphob->id]);
        $transphob_sticker = Sticker::factory(['lat' => 20, 'lon' => 20.0000001])->create();
        $transphob_sticker->tags()->sync([$transphob->id]);
        // Gemischter Cluster
        $linker_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24])->create();
        $linker_sticker2->tags()->sync([$links->id]);
        $pride_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.0000001])->create();
        $pride_sticker2->tags()->sync([$pride->id]);
        $gruene_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24])->create();
        $gruene_sticker2->tags()->sync([$gruene->id]);
        $antifa_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24.0000001])->create();
        $antifa_sticker2->tags()->sync([$antifa->id]);
        $rechter_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24.00000005])->create();
        $rechter_sticker2->tags()->sync([$rechts->id]);
        $queerphob_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.00000005])->create();
        $queerphob_sticker2->tags()->sync([$queerphob->id]);
        $transphob_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24])->create();
        $transphob_sticker2->tags()->sync([$transphob->id]);

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.clusters.show', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'tag' => $politik->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'centroid' => [
                            'lat',
                            'lon',
                        ],
                        'tag_counts',
                        'count',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    [
                        'centroid' => [
                            'lat' => 16.00000005,
                            'lon' => 16.00000005,
                        ],
                        'tag_counts' => [
                            $links->id => 5,
                        ],
                        'count' => 5,
                    ],
                    [
                        'centroid' => [
                            'lat' => 20.0000000333333,
                            'lon' => 20.0000000333333,
                        ],
                        'tag_counts' => [
                            $rechts->id => 3,
                        ],
                        'count' => 3,
                    ],
                    [
                        'centroid' => [
                            'lat' => 24.0000000428571,
                            'lon' => 24.0000000428571,
                        ],
                        'tag_counts' => [
                            $links->id => 4,
                            $rechts->id => 3,
                        ],
                        'count' => 7,
                    ],
                ],
            ]);
    }

    public function test_cluster_stickers_by_parent_multiple_tags()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();
        $rechts = Tag::factory(['super_tag' => $politik->id])->create();
        $gruene = Tag::factory(['super_tag' => $links->id])->create();
        $antifa = Tag::factory(['super_tag' => $links->id])->create();
        $toleranz = Tag::factory(['super_tag' => $links->id])->create();
        $pride = Tag::factory(['super_tag' => $toleranz->id])->create();
        $queerphob = Tag::factory(['super_tag' => $rechts->id])->create();
        $transphob = Tag::factory(['super_tag' => $queerphob->id])->create();

        // Linke Cluster
        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $linker_sticker->tags()->sync([$links->id]);
        $toleranz_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16.0000001])->create();
        $toleranz_sticker->tags()->sync([$toleranz->id]);
        $pride_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16])->create();
        $pride_sticker->tags()->sync([$pride->id]);
        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16.0000001])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $antifa_sticker = Sticker::factory(['lat' => 16.00000005, 'lon' => 16.00000005])->create();
        $antifa_sticker->tags()->sync([$antifa->id]);
        // Rechte Cluster
        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);
        $queerphob_sticker = Sticker::factory(['lat' => 20.0000001, 'lon' => 20])->create();
        $queerphob_sticker->tags()->sync([$queerphob->id]);
        $transphob_sticker = Sticker::factory(['lat' => 20, 'lon' => 20.0000001])->create();
        $transphob_sticker->tags()->sync([$transphob->id]);
        // Gemischter Cluster
        $linker_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24])->create();
        $linker_sticker2->tags()->sync([$links->id]);
        $pride_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.0000001])->create();
        $pride_sticker2->tags()->sync([$pride->id]);
        $gruene_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24])->create();
        $gruene_sticker2->tags()->sync([$gruene->id]);
        $antifa_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24.0000001])->create();
        $antifa_sticker2->tags()->sync([$antifa->id]);
        $rechter_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24.00000005])->create();
        $rechter_sticker2->tags()->sync([$rechts->id]);
        $queerphob_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.00000005])->create();
        $queerphob_sticker2->tags()->sync([$queerphob->id]);
        $transphob_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24])->create();
        $transphob_sticker2->tags()->sync([$transphob->id]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.clusters.showMultiple'), [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'tags' => [$links->id, $rechts->id],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'centroid' => [
                            'lat',
                            'lon',
                        ],
                        'tag_counts',
                        'count',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    [
                        'centroid' => [
                            'lat' => 16.00000005,
                            'lon' => 16.00000005,
                        ],
                        'tag_counts' => [
                            $links->id => 5,
                        ],
                        'count' => 5,
                    ],
                    [
                        'centroid' => [
                            'lat' => 20.0000000333333,
                            'lon' => 20.0000000333333,
                        ],
                        'tag_counts' => [
                            $rechts->id => 3,
                        ],
                        'count' => 3,
                    ],
                    [
                        'centroid' => [
                            'lat' => 24.0000000428571,
                            'lon' => 24.0000000428571,
                        ],
                        'tag_counts' => [
                            $links->id => 4,
                            $rechts->id => 3,
                        ],
                        'count' => 7,
                    ],
                ],
            ]);
    }

    public function test_cluster_stickers_by_parent_single_tag_doesnt_return_stickers_younger_than_ten_minutes()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();
        $rechts = Tag::factory(['super_tag' => $politik->id])->create();

        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $linker_sticker->tags()->sync([$links->id]);

        $this->travel(10)->minutes();

        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);

        $response = $this->getJson(route('api.stickers.clusters.show', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'tag' => $politik->id]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'centroid' => [
                            'lat' => 16,
                            'lon' => 16,
                        ],
                        'tag_counts' => [
                            $links->id => 1,
                        ],
                        'count' => 1,
                    ],
                ],
            ]);
    }

    public function test_cluster_stickers_by_parent_multiple_tags_doesnt_return_stickers_younger_than_ten_minutes()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();
        $rechts = Tag::factory(['super_tag' => $politik->id])->create();

        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $linker_sticker->tags()->sync([$links->id]);

        $this->travel(10)->minutes();

        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);

        $response = $this->postJson(route('api.stickers.clusters.showMultiple'), [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'tags' => [$links->id, $rechts->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'centroid' => [
                            'lat' => 16,
                            'lon' => 16,
                        ],
                        'tag_counts' => [
                            $links->id => 1,
                        ],
                        'count' => 1,
                    ],
                ],
            ]);
    }

    public function test_too_much_stickers_shouldnt_include_stickers_on_dynamic_mode()
    {
        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->count(16)->create();

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.clusters.index', [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            // include stickers dynamic is default
        ]));

        ds($response->json());

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.count', 16)
            ->assertJson(fn (AssertableJson $json) => $json
                ->whereType('data.0.stickers', 'array')
                ->has('data.0.stickers', 0)
            );
    }

    public function test_not_too_much_stickers_should_include_stickers_on_dynamic_mode()
    {
        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->count(15)->create();

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.clusters.index', [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            // include stickers dynamic is default
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.count', 15)
            ->assertJson(fn (AssertableJson $json) => $json
                ->whereType('data.0.stickers', 'array')
                ->has('data.0.stickers', 15)
            );
    }

    public function test_stickers_should_always_be_included__on_include_mode()
    {
        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->count(20)->create();

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.clusters.index', [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'include_stickers' => StickerInclusion::INCLUDE,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.count', 20)
            ->assertJson(fn (AssertableJson $json) => $json
                ->whereType('data.0.stickers', 'array')
                ->has('data.0.stickers', 20)
            );
    }

    public function test_stickers_should_never_be_included__on_hide_mode()
    {
        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->count(1)->create();

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.clusters.index', [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'include_stickers' => StickerInclusion::HIDE,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.count', 1)
            ->assertJson(fn (AssertableJson $json) => $json
                ->whereType('data.0.stickers', 'array')
                ->has('data.0.stickers', 0)
            );
    }
}
