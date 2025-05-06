<?php

namespace Tests\Feature;

use App\Models\StateHistory;
use App\Models\Sticker;
use App\Models\Tag;
use App\State;
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

    // public function test_cluster_stickers_by_parent_multiple_tags()
    // {
    //     $politik = Tag::factory()->create();
    //     $links = Tag::factory(['super_tag' => $politik->id])->create();
    //     $rechts = Tag::factory(['super_tag' => $politik->id])->create();
    //     $gruene = Tag::factory(['super_tag' => $links->id])->create();
    //     $antifa = Tag::factory(['super_tag' => $links->id])->create();
    //     $toleranz = Tag::factory(['super_tag' => $links->id])->create();
    //     $pride = Tag::factory(['super_tag' => $toleranz->id])->create();
    //     $queerphob = Tag::factory(['super_tag' => $rechts->id])->create();
    //     $transphob = Tag::factory(['super_tag' => $queerphob->id])->create();
    //
    //     // Linke Cluster
    //     $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
    //     $linker_sticker->tags()->sync([$links->id]);
    //     $toleranz_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16.0000001])->create();
    //     $toleranz_sticker->tags()->sync([$toleranz->id]);
    //     $pride_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16])->create();
    //     $pride_sticker->tags()->sync([$pride->id]);
    //     $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16.0000001])->create();
    //     $gruene_sticker->tags()->sync([$gruene->id]);
    //     $antifa_sticker = Sticker::factory(['lat' => 16.00000005, 'lon' => 16.00000005])->create();
    //     $antifa_sticker->tags()->sync([$antifa->id]);
    //     // Rechte Cluster
    //     $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
    //     $rechter_sticker->tags()->sync([$rechts->id]);
    //     $queerphob_sticker = Sticker::factory(['lat' => 20.0000001, 'lon' => 20])->create();
    //     $queerphob_sticker->tags()->sync([$queerphob->id]);
    //     $transphob_sticker = Sticker::factory(['lat' => 20, 'lon' => 20.0000001])->create();
    //     $transphob_sticker->tags()->sync([$transphob->id]);
    //     // Gemischter Cluster
    //     $linker_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24])->create();
    //     $linker_sticker2->tags()->sync([$links->id]);
    //     $pride_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.0000001])->create();
    //     $pride_sticker2->tags()->sync([$pride->id]);
    //     $gruene_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24])->create();
    //     $gruene_sticker2->tags()->sync([$gruene->id]);
    //     $antifa_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24.0000001])->create();
    //     $antifa_sticker2->tags()->sync([$antifa->id]);
    //     $rechter_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24.00000005])->create();
    //     $rechter_sticker2->tags()->sync([$rechts->id]);
    //     $queerphob_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.00000005])->create();
    //     $queerphob_sticker2->tags()->sync([$queerphob->id]);
    //     $transphob_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24])->create();
    //     $transphob_sticker2->tags()->sync([$transphob->id]);
    //
    //     $this->travel(10)->minutes();
    //
    //     $response = $this->postJson(route('api.stickers.clusters.showMultiple'), [
    //         'min_lat' => 15,
    //         'max_lat' => 25,
    //         'min_lon' => 15,
    //         'max_lon' => 25,
    //         'tags' => [$links->id, $rechts->id],
    //     ]);
    //
    //     $response->assertOk()
    //         ->assertJsonStructure([
    //             'data' => [
    //                 '*' => [
    //                     'centroid' => [
    //                         'lat',
    //                         'lon',
    //                     ],
    //                     'tag_counts',
    //                     'count',
    //                 ],
    //             ],
    //         ])
    //         ->assertJson([
    //             'data' => [
    //                 [
    //                     'centroid' => [
    //                         'lat' => 16.00000005,
    //                         'lon' => 16.00000005,
    //                     ],
    //                     'tag_counts' => [
    //                         $links->id => 5,
    //                     ],
    //                     'count' => 5,
    //                 ],
    //                 [
    //                     'centroid' => [
    //                         'lat' => 20.0000000333333,
    //                         'lon' => 20.0000000333333,
    //                     ],
    //                     'tag_counts' => [
    //                         $rechts->id => 3,
    //                     ],
    //                     'count' => 3,
    //                 ],
    //                 [
    //                     'centroid' => [
    //                         'lat' => 24.0000000428571,
    //                         'lon' => 24.0000000428571,
    //                     ],
    //                     'tag_counts' => [
    //                         $links->id => 4,
    //                         $rechts->id => 3,
    //                     ],
    //                     'count' => 7,
    //                 ],
    //             ],
    //         ]);
    // }

    public function test_multiple_filter_by_latitude_no_date_one_history_returns_correct_clusters_with_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.clusters.showMultiple'),
            [
                'min_lat' => 15,
                'max_lat' => 25,
                'min_lon' => 15,
                'max_lon' => 25,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'stickers' => [
                            [
                                'id' => $sticker->id,
                                'state' => $sticker->latestStateHistory->state->value,
                                'last_seen' => $sticker->latestStateHistory->last_seen,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_multiple_filter_by_latitude_no_date_multiple_histories_returns_correct_clusters_with_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $history = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.clusters.showMultiple'),
            [
                'min_lat' => 15,
                'max_lat' => 25,
                'min_lon' => 15,
                'max_lon' => 25,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'stickers' => [
                            [
                                'id' => $sticker->id,
                                'state' => $history->state->value,
                                'last_seen' => $history->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_multiple_filter_by_latitude_and_date_multiple_histories_returns_correct_clusters_with_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $history = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $date = now();

        $this->travel(10)->minutes();

        StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.clusters.showMultiple'),
            [
                'min_lat' => 15,
                'max_lat' => 25,
                'min_lon' => 15,
                'max_lon' => 25,
                'date' => $date,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'stickers' => [
                            [
                                'id' => $sticker->id,
                                'state' => $history->state->value,
                                'last_seen' => $history->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ]);
    }

    // public function test_index_filter_by_latitude_and_date_returns_correct_history()
    // {
    //     Sticker::factory()->create(['lat' => 14, 'lon' => 14]);
    //     $sticker2 = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);
    //     $autogeneratedHistory2 = $sticker2->latestStateHistory;
    //     $sticker3 = Sticker::factory()->create(['lat' => 22, 'lon' => 21]);
    //     $autogeneratedHistory3 = $sticker3->latestStateHistory;
    //     $sticker4 = Sticker::factory()->create(['lat' => 22, 'lon' => 23]);
    //     $autogeneratedHistory4 = $sticker4->latestStateHistory;
    //     Sticker::factory()->create(['lat' => 26, 'lon' => 26]);
    //
    //     $this->travel(10)->minutes();
    //
    //     $history3 = $sticker3->stateHistory()->create([
    //         'last_seen' => now(),
    //         'state' => State::REMOVED,
    //     ]);
    //     $history4_before = $sticker4->stateHistory()->create([
    //         'last_seen' => now(),
    //         'state' => State::PARTIALLY_REMOVED,
    //     ]);
    //
    //     $date = now();
    //     $this->travel(10)->minutes();
    //
    //     $history4_after = $sticker4->stateHistory()->create([
    //         'last_seen' => now(),
    //         'state' => State::REMOVED,
    //     ]);
    //     Sticker::factory()->create(['lat' => 19, 'lon' => 21]);
    //
    //     $response = $this->getJson(route('api.stickers.history.index', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'date' => $date->toString()]));
    //
    //     $response->assertOk()
    //         ->assertJson([
    //             'data' => [
    //                 [
    //                     'id' => $autogeneratedHistory2->id,
    //                     'sticker_id' => $sticker2->id,
    //                     'state' => $autogeneratedHistory2->state->value,
    //                     'last_seen' => $autogeneratedHistory2->last_seen,
    //                 ],
    //                 [
    //                     'id' => $history3->id,
    //                     'sticker_id' => $sticker3->id,
    //                     'state' => $history3->state->value,
    //                     'last_seen' => $history3->last_seen,
    //                 ],
    //                 [
    //                     'id' => $autogeneratedHistory3->id,
    //                     'sticker_id' => $sticker3->id,
    //                     'state' => $autogeneratedHistory3->state->value,
    //                     'last_seen' => $autogeneratedHistory3->last_seen,
    //                 ],
    //                 [
    //                     'id' => $history4_before->id,
    //                     'sticker_id' => $sticker4->id,
    //                     'state' => $history4_before->state->value,
    //                     'last_seen' => $history4_before->last_seen,
    //                 ],
    //                 [
    //                     'id' => $autogeneratedHistory4->id,
    //                     'sticker_id' => $sticker4->id,
    //                     'state' => $autogeneratedHistory4->state->value,
    //                     'last_seen' => $autogeneratedHistory4->last_seen,
    //                 ],
    //             ],
    //         ]);
    // }

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

    public function test_stickers_should_always_be_included_on_include_mode()
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

    public function test_stickers_should_never_be_included_on_hide_mode()
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
