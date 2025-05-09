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

    public function test_show_multiple_filter_by_latitude_no_date_one_history_returns_correct_clusters_with_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster'),
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

    public function test_show_multiple_filter_by_latitude_no_date_multiple_histories_returns_correct_clusters_with_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $history = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.cluster'),
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

    public function test_show_multiple_filter_by_latitude_and_date_multiple_histories_returns_correct_clusters_with_history()
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

        $response = $this->postJson(route('api.stickers.cluster'),
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

    public function test_show_multiple_with_one_tag_and_no_date_simple_test()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();

        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $linker_sticker->tags()->sync([$links->id]);
        $history_linker_sticker = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster', [
            'min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'tags' => [$politik->id],
        ]));

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
                        'tag_counts' => [
                            $links->id => 1,
                        ],
                        'count' => 1,
                        'stickers' => [
                            [
                                'id' => $linker_sticker->id,
                                'state' => $history_linker_sticker->state->value,
                                'last_seen' => $history_linker_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ], );
    }

    public function test_show_multiple_with_one_tag_and_no_date_complex_test()
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
        $history_linker_sticker = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $toleranz_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16.0000001])->create();
        $toleranz_sticker->tags()->sync([$toleranz->id]);
        $history_toleranz_sticker = StateHistory::factory()->create([
            'sticker_id' => $toleranz_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $pride_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16])->create();
        $pride_sticker->tags()->sync([$pride->id]);
        $history_pride_sticker = StateHistory::factory()->create([
            'sticker_id' => $pride_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16.0000001])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $history_gruene_sticker = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $antifa_sticker = Sticker::factory(['lat' => 16.00000005, 'lon' => 16.00000005])->create();
        $antifa_sticker->tags()->sync([$antifa->id]);
        $history_antifa_sticker = StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        // Rechte Cluster
        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);
        $history_rechter_sticker = StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $queerphob_sticker = Sticker::factory(['lat' => 20.0000001, 'lon' => 20])->create();
        $queerphob_sticker->tags()->sync([$queerphob->id]);
        $history_queerphob_sticker = StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $transphob_sticker = Sticker::factory(['lat' => 20, 'lon' => 20.0000001])->create();
        $transphob_sticker->tags()->sync([$transphob->id]);
        $history_transphob_sticker = StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        // Gemischter Cluster
        $linker_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24])->create();
        $linker_sticker2->tags()->sync([$links->id]);
        $history_linker_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $pride_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.0000001])->create();
        $pride_sticker2->tags()->sync([$pride->id]);
        $history_pride_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $pride_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $gruene_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24])->create();
        $gruene_sticker2->tags()->sync([$gruene->id]);
        $history_gruene_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $antifa_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24.0000001])->create();
        $antifa_sticker2->tags()->sync([$antifa->id]);
        $history_antifa_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $rechter_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24.00000005])->create();
        $rechter_sticker2->tags()->sync([$rechts->id]);
        $history_rechter_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $queerphob_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.00000005])->create();
        $queerphob_sticker2->tags()->sync([$queerphob->id]);
        $history_queerphob_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $transphob_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24])->create();
        $transphob_sticker2->tags()->sync([$transphob->id]);
        $history_transphob_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster', [
            'min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'tags' => [$politik->id],
        ]));

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
                        'stickers' => [
                            [
                                'id' => $linker_sticker->id,
                                'state' => $history_linker_sticker->state->value,
                                'last_seen' => $history_linker_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $toleranz_sticker->id,
                                'state' => $history_toleranz_sticker->state->value,
                                'last_seen' => $history_toleranz_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $pride_sticker->id,
                                'state' => $history_pride_sticker->state->value,
                                'last_seen' => $history_pride_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $gruene_sticker->id,
                                'state' => $history_gruene_sticker->state->value,
                                'last_seen' => $history_gruene_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $antifa_sticker->id,
                                'state' => $history_antifa_sticker->state->value,
                                'last_seen' => $history_antifa_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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
                        'stickers' => [
                            [
                                'id' => $rechter_sticker->id,
                                'state' => $history_rechter_sticker->state->value,
                                'last_seen' => $history_rechter_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $queerphob_sticker->id,
                                'state' => $history_queerphob_sticker->state->value,
                                'last_seen' => $history_queerphob_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $transphob_sticker->id,
                                'state' => $history_transphob_sticker->state->value,
                                'last_seen' => $history_transphob_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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
                        'stickers' => [
                            [
                                'id' => $linker_sticker2->id,
                                'state' => $history_linker_sticker2->state->value,
                                'last_seen' => $history_linker_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $pride_sticker2->id,
                                'state' => $history_pride_sticker2->state->value,
                                'last_seen' => $history_pride_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $gruene_sticker2->id,
                                'state' => $history_gruene_sticker2->state->value,
                                'last_seen' => $history_gruene_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $antifa_sticker2->id,
                                'state' => $history_antifa_sticker2->state->value,
                                'last_seen' => $history_antifa_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $rechter_sticker2->id,
                                'state' => $history_rechter_sticker2->state->value,
                                'last_seen' => $history_rechter_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $queerphob_sticker2->id,
                                'state' => $history_queerphob_sticker2->state->value,
                                'last_seen' => $history_queerphob_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $transphob_sticker2->id,
                                'state' => $history_transphob_sticker2->state->value,
                                'last_seen' => $history_transphob_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_show_multiple_with_one_tag_and_date_simple_test()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();

        $linker_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $linker_sticker->tags()->sync([$links->id]);
        $history_linker_sticker = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $date = now();

        $this->travel(10)->minutes();

        StateHistory::factory()->create([
            'sticker_id' => $linker_sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.cluster'),
            [
                'min_lat' => 15,
                'max_lat' => 25,
                'min_lon' => 15,
                'max_lon' => 25,
                'tags' => [$politik->id],
                'date' => $date,
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
                        'tag_counts' => [
                            $links->id => 1,
                        ],
                        'count' => 1,
                        'stickers' => [
                            [
                                'id' => $linker_sticker->id,
                                'state' => $history_linker_sticker->state->value,
                                'last_seen' => $history_linker_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ], );
    }

    public function test_show_multiple_with_one_tag_and_date_complex_test()
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
        $history_linker_sticker = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $toleranz_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16.0000001])->create();
        $toleranz_sticker->tags()->sync([$toleranz->id]);
        $history_toleranz_sticker = StateHistory::factory()->create([
            'sticker_id' => $toleranz_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $pride_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16])->create();
        $pride_sticker->tags()->sync([$pride->id]);
        $history_pride_sticker = StateHistory::factory()->create([
            'sticker_id' => $pride_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16.0000001])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $history_gruene_sticker = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $antifa_sticker = Sticker::factory(['lat' => 16.00000005, 'lon' => 16.00000005])->create();
        $antifa_sticker->tags()->sync([$antifa->id]);
        $history_antifa_sticker = StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        // Rechte Cluster
        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);
        $history_rechter_sticker = StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $queerphob_sticker = Sticker::factory(['lat' => 20.0000001, 'lon' => 20])->create();
        $queerphob_sticker->tags()->sync([$queerphob->id]);
        $history_queerphob_sticker = StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $transphob_sticker = Sticker::factory(['lat' => 20, 'lon' => 20.0000001])->create();
        $transphob_sticker->tags()->sync([$transphob->id]);
        $history_transphob_sticker = StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        // Gemischter Cluster
        $linker_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24])->create();
        $linker_sticker2->tags()->sync([$links->id]);
        $history_linker_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $pride_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.0000001])->create();
        $pride_sticker2->tags()->sync([$pride->id]);
        $history_pride_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $pride_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $gruene_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24])->create();
        $gruene_sticker2->tags()->sync([$gruene->id]);
        $history_gruene_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $antifa_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24.0000001])->create();
        $antifa_sticker2->tags()->sync([$antifa->id]);
        $history_antifa_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $rechter_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24.00000005])->create();
        $rechter_sticker2->tags()->sync([$rechts->id]);
        $history_rechter_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $queerphob_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.00000005])->create();
        $queerphob_sticker2->tags()->sync([$queerphob->id]);
        $history_queerphob_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $transphob_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24])->create();
        $transphob_sticker2->tags()->sync([$transphob->id]);
        $history_transphob_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $date = now();

        $this->travel(10)->minutes();

        StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.cluster', [
            'min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'tags' => [$politik->id], 'date' => $date->toString(),
        ]));

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
                        'stickers' => [
                            [
                                'id' => $linker_sticker->id,
                                'state' => $history_linker_sticker->state->value,
                                'last_seen' => $history_linker_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $toleranz_sticker->id,
                                'state' => $history_toleranz_sticker->state->value,
                                'last_seen' => $history_toleranz_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $pride_sticker->id,
                                'state' => $history_pride_sticker->state->value,
                                'last_seen' => $history_pride_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $gruene_sticker->id,
                                'state' => $history_gruene_sticker->state->value,
                                'last_seen' => $history_gruene_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $antifa_sticker->id,
                                'state' => $history_antifa_sticker->state->value,
                                'last_seen' => $history_antifa_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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
                        'stickers' => [
                            [
                                'id' => $rechter_sticker->id,
                                'state' => $history_rechter_sticker->state->value,
                                'last_seen' => $history_rechter_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $queerphob_sticker->id,
                                'state' => $history_queerphob_sticker->state->value,
                                'last_seen' => $history_queerphob_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $transphob_sticker->id,
                                'state' => $history_transphob_sticker->state->value,
                                'last_seen' => $history_transphob_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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
                        'stickers' => [
                            [
                                'id' => $linker_sticker2->id,
                                'state' => $history_linker_sticker2->state->value,
                                'last_seen' => $history_linker_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $pride_sticker2->id,
                                'state' => $history_pride_sticker2->state->value,
                                'last_seen' => $history_pride_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $gruene_sticker2->id,
                                'state' => $history_gruene_sticker2->state->value,
                                'last_seen' => $history_gruene_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $antifa_sticker2->id,
                                'state' => $history_antifa_sticker2->state->value,
                                'last_seen' => $history_antifa_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $rechter_sticker2->id,
                                'state' => $history_rechter_sticker2->state->value,
                                'last_seen' => $history_rechter_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $queerphob_sticker2->id,
                                'state' => $history_queerphob_sticker2->state->value,
                                'last_seen' => $history_queerphob_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $transphob_sticker2->id,
                                'state' => $history_transphob_sticker2->state->value,
                                'last_seen' => $history_transphob_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_show_multiple_with_multiple_tags_and_no_date_simple_test()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();
        $gruene = Tag::factory(['super_tag' => $links->id])->create();
        $rechts = Tag::factory(['super_tag' => $politik->id])->create();

        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $history_gruene_sticker = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster'),
            [
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
                        'tag_counts' => [
                            $links->id => 1,
                        ],
                        'count' => 1,
                        'stickers' => [
                            [
                                'id' => $gruene_sticker->id,
                                'state' => $history_gruene_sticker->state->value,
                                'last_seen' => $history_gruene_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ], );
    }

    public function test_show_multiple_with_multiple_tags_and_date_simple_test()
    {
        $politik = Tag::factory()->create();
        $links = Tag::factory(['super_tag' => $politik->id])->create();
        $gruene = Tag::factory(['super_tag' => $links->id])->create();
        $rechts = Tag::factory(['super_tag' => $politik->id])->create();

        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $history_gruene_sticker = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $date = now();

        $this->travel(10)->minutes();

        StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.cluster'),
            [
                'min_lat' => 15,
                'max_lat' => 25,
                'min_lon' => 15,
                'max_lon' => 25,
                'tags' => [$links->id, $rechts->id],
                'date' => $date,
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
                        'tag_counts' => [
                            $links->id => 1,
                        ],
                        'count' => 1,
                        'stickers' => [
                            [
                                'id' => $gruene_sticker->id,
                                'state' => $history_gruene_sticker->state->value,
                                'last_seen' => $history_gruene_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ],
                ],
            ], );
    }

    public function test_show_multiple_with_multiple_tags_and_date_complex_test()
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
        $history_linker_sticker = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $politik_sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $politik_sticker->tags()->sync([$politik->id]);
        StateHistory::factory()->create([
            'sticker_id' => $politik_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $toleranz_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16.0000001])->create();
        $toleranz_sticker->tags()->sync([$toleranz->id]);
        $history_toleranz_sticker = StateHistory::factory()->create([
            'sticker_id' => $toleranz_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $pride_sticker = Sticker::factory(['lat' => 16.0000001, 'lon' => 16])->create();
        $pride_sticker->tags()->sync([$pride->id]);
        $history_pride_sticker = StateHistory::factory()->create([
            'sticker_id' => $pride_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $gruene_sticker = Sticker::factory(['lat' => 16, 'lon' => 16.0000001])->create();
        $gruene_sticker->tags()->sync([$gruene->id]);
        $history_gruene_sticker = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $antifa_sticker = Sticker::factory(['lat' => 16.00000005, 'lon' => 16.00000005])->create();
        $antifa_sticker->tags()->sync([$antifa->id]);
        $history_antifa_sticker = StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        // Rechte Cluster
        $rechter_sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $rechter_sticker->tags()->sync([$rechts->id]);
        $history_rechter_sticker = StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $queerphob_sticker = Sticker::factory(['lat' => 20.0000001, 'lon' => 20])->create();
        $queerphob_sticker->tags()->sync([$queerphob->id]);
        $history_queerphob_sticker = StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $transphob_sticker = Sticker::factory(['lat' => 20, 'lon' => 20.0000001])->create();
        $transphob_sticker->tags()->sync([$transphob->id]);
        $history_transphob_sticker = StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        // Gemischter Cluster
        $linker_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24])->create();
        $linker_sticker2->tags()->sync([$links->id]);
        $history_linker_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $linker_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $pride_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.0000001])->create();
        $pride_sticker2->tags()->sync([$pride->id]);
        $history_pride_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $pride_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $gruene_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24])->create();
        $gruene_sticker2->tags()->sync([$gruene->id]);
        $history_gruene_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $gruene_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $antifa_sticker2 = Sticker::factory(['lat' => 24.0000001, 'lon' => 24.0000001])->create();
        $antifa_sticker2->tags()->sync([$antifa->id]);
        $history_antifa_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $rechter_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24.00000005])->create();
        $rechter_sticker2->tags()->sync([$rechts->id]);
        $history_rechter_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $queerphob_sticker2 = Sticker::factory(['lat' => 24, 'lon' => 24.00000005])->create();
        $queerphob_sticker2->tags()->sync([$queerphob->id]);
        $history_queerphob_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);
        $transphob_sticker2 = Sticker::factory(['lat' => 24.00000005, 'lon' => 24])->create();
        $transphob_sticker2->tags()->sync([$transphob->id]);
        $history_transphob_sticker2 = StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $date = now();

        $this->travel(10)->minutes();

        StateHistory::factory()->create([
            'sticker_id' => $antifa_sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        StateHistory::factory()->create([
            'sticker_id' => $rechter_sticker->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        StateHistory::factory()->create([
            'sticker_id' => $queerphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        StateHistory::factory()->create([
            'sticker_id' => $transphob_sticker2->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->postJson(route('api.stickers.cluster', [
            'min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'tags' => [$links->id, $rechts->id], 'date' => $date->toString(),
        ]));

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
                        'stickers' => [
                            [
                                'id' => $linker_sticker->id,
                                'state' => $history_linker_sticker->state->value,
                                'last_seen' => $history_linker_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $toleranz_sticker->id,
                                'state' => $history_toleranz_sticker->state->value,
                                'last_seen' => $history_toleranz_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $pride_sticker->id,
                                'state' => $history_pride_sticker->state->value,
                                'last_seen' => $history_pride_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $gruene_sticker->id,
                                'state' => $history_gruene_sticker->state->value,
                                'last_seen' => $history_gruene_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $antifa_sticker->id,
                                'state' => $history_antifa_sticker->state->value,
                                'last_seen' => $history_antifa_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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
                        'stickers' => [
                            [
                                'id' => $rechter_sticker->id,
                                'state' => $history_rechter_sticker->state->value,
                                'last_seen' => $history_rechter_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $queerphob_sticker->id,
                                'state' => $history_queerphob_sticker->state->value,
                                'last_seen' => $history_queerphob_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $transphob_sticker->id,
                                'state' => $history_transphob_sticker->state->value,
                                'last_seen' => $history_transphob_sticker->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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
                        'stickers' => [
                            [
                                'id' => $linker_sticker2->id,
                                'state' => $history_linker_sticker2->state->value,
                                'last_seen' => $history_linker_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $pride_sticker2->id,
                                'state' => $history_pride_sticker2->state->value,
                                'last_seen' => $history_pride_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $gruene_sticker2->id,
                                'state' => $history_gruene_sticker2->state->value,
                                'last_seen' => $history_gruene_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $antifa_sticker2->id,
                                'state' => $history_antifa_sticker2->state->value,
                                'last_seen' => $history_antifa_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $rechter_sticker2->id,
                                'state' => $history_rechter_sticker2->state->value,
                                'last_seen' => $history_rechter_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $queerphob_sticker2->id,
                                'state' => $history_queerphob_sticker2->state->value,
                                'last_seen' => $history_queerphob_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                            [
                                'id' => $transphob_sticker2->id,
                                'state' => $history_transphob_sticker2->state->value,
                                'last_seen' => $history_transphob_sticker2->last_seen->format('Y-m-d H:i:s'),
                            ],
                        ],
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

        $response = $this->postJson(route('api.stickers.cluster'), [
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

        $response = $this->postJson(route('api.stickers.cluster', [
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

        $response = $this->postJson(route('api.stickers.cluster', [
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

        $response = $this->postJson(route('api.stickers.cluster', [
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

        $response = $this->postJson(route('api.stickers.cluster', [
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

    public function test_filter_by_states_should_show_sticker()
    {
        $tag = Tag::factory()->create();

        $sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $sticker->tags()->sync([$tag->id]);

        $history1 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::EXISTS,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster'), [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'states' => [State::EXISTS],
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
                            $tag->id => 1,
                        ],
                        'count' => 1,
                    ],
                ],
            ]);
    }

    public function test_filter_by_states_shouldnt_show_sticker()
    {
        $tag = Tag::factory()->create();

        $sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $sticker->tags()->sync([$tag->id]);

        $history1 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster'), [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'states' => [State::EXISTS],
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_filter_by_multiple_states_should_show_multiple_stickers()
    {
        $tag = Tag::factory()->create();

        $sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $sticker->tags()->sync([$tag->id]);

        $history1 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::EXISTS,
        ]);

        $sticker2 = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $sticker2->tags()->sync([$tag->id]);

        $history2 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster'), [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'states' => [State::EXISTS, State::PARTIALLY_REMOVED],
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
                            $tag->id => 2,
                        ],
                        'count' => 2,
                    ],
                ],
            ]);
    }

    public function test_filter_by_empty_states_should_show_all_stickers()
    {
        $tag = Tag::factory()->create();

        $sticker = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $sticker->tags()->sync([$tag->id]);

        $history1 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::EXISTS,
        ]);

        $sticker2 = Sticker::factory(['lat' => 16, 'lon' => 16])->create();
        $sticker2->tags()->sync([$tag->id]);

        $history2 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.cluster'), [
            'min_lat' => 15,
            'max_lat' => 25,
            'min_lon' => 15,
            'max_lon' => 25,
            'states' => [],
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
                            $tag->id => 2,
                        ],
                        'count' => 2,
                    ],
                ],
            ]);
    }
}
