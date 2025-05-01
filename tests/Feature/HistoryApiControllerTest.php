<?php

namespace Tests\Feature;

use App\Models\StateHistory;
use App\Models\Sticker;
use App\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HistoryApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filter_by_latitude_no_date_returns_correct_history()
    {
        $sticker1 = Sticker::factory()->create(['lat' => 14, 'lon' => 14]);
        $sticker2 = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);
        $sticker3 = Sticker::factory()->create(['lat' => 22, 'lon' => 21]);
        $sticker4 = Sticker::factory()->create(['lat' => 26, 'lon' => 26]);

        $this->travel(10)->minutes();

        $history3 = StateHistory::factory()->create([
            'sticker_id' => $sticker3->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->getJson(route('api.stickers.history.index', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $sticker2->latestStateHistory->id,
                        'sticker_id' => $sticker2->id,
                        'state' => $sticker2->latestStateHistory->state->value,
                        'last_seen' => $sticker2->latestStateHistory->last_seen,
                    ],
                    1 => [
                        'id' => $history3->id,
                        'sticker_id' => $sticker3->id,
                        'state' => $history3->state->value,
                        'last_seen' => $history3->last_seen->format('Y-m-d H:i:s'),
                    ],
                ],
            ]);
    }

    public function test_index_filter_by_latitude_and_date_returns_correct_history()
    {
        Sticker::factory()->create(['lat' => 14, 'lon' => 14]);
        $sticker2 = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);
        $sticker3 = Sticker::factory()->create(['lat' => 22, 'lon' => 21]);
        $sticker4 = Sticker::factory()->create(['lat' => 22, 'lon' => 23]);
        Sticker::factory()->create(['lat' => 26, 'lon' => 26]);

        $this->travel(10)->minutes();

        $history3 = StateHistory::factory()->create([
            'sticker_id' => $sticker3->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        $history4_before = StateHistory::factory()->create([
            'sticker_id' => $sticker4->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $date = now();
        $this->travel(10)->minutes();

        $history4_after = StateHistory::factory()->create([
            'sticker_id' => $sticker4->id,
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);
        Sticker::factory()->create(['lat' => 19, 'lon' => 21]);

        $response = $this->getJson(route('api.stickers.history.index', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25, 'date' => $date]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $sticker2->latestStateHistory->id,
                        'sticker_id' => $sticker2->id,
                        'state' => $sticker2->latestStateHistory->state->value,
                        'last_seen' => $sticker2->latestStateHistory->last_seen,
                    ],
                    1 => [
                        'id' => $history3->id,
                        'sticker_id' => $sticker3->id,
                        'state' => $history3->state->value,
                        'last_seen' => $history3->last_seen->format('Y-m-d H:i:s'),
                    ],
                    2 => [
                        'id' => $history4_before->id,
                        'sticker_id' => $sticker4->id,
                        'state' => $history4_before->state->value,
                        'last_seen' => $history4_before->last_seen->format('Y-m-d H:i:s'),
                    ],
                ],
            ]);
    }

    public function test_show_no_date_returns_correct_history()
    {
        $sticker1 = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $history1 = StateHistory::factory()->create([
            'sticker_id' => $sticker1->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.history.show', ['sticker' => $sticker1->id]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $history1->id,
                        'sticker_id' => $sticker1->id,
                        'state' => $history1->state->value,
                        'last_seen' => $history1->last_seen,
                    ],
                ],
            ]);
    }

    public function test_show_filter_by_date_returns_correct_history()
    {
        $this->freezeTime();
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);
        $creationDate = now();
        $historyId = $sticker->latestStateHistory->id;
        $state = $sticker->latestStateHistory->state->value;
        Carbon::setTestNow();

        $this->travel(10)->minutes();

        $history = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $date = now();
        $this->travel(10)->minutes();

        StateHistory::factory()->create(['sticker_id' => $sticker->id, 'last_seen' => now(), 'state' => State::REMOVED]);

        $response = $this->getJson(route('api.stickers.history.show', ['sticker' => $sticker->id, 'date' => $date]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $historyId,
                        'sticker_id' => $sticker->id,
                        'state' => $state,
                        'last_seen' => $creationDate->format('Y-m-d H:i:s'),
                    ],
                    1 => [
                        'id' => $history->id,
                        'sticker_id' => $sticker->id,
                        'state' => $history->state->value,
                        'last_seen' => $history->last_seen,
                    ],
                ],
            ]);
    }

    public function test_update_sticker_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(10)->minutes();

        $history1 = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->freezeTime();

        $response = $this->postJson(route('api.stickers.history.update', ['sticker' => $sticker->id]), [
            'state' => State::REMOVED,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $history1->id,
                    'sticker_id' => $sticker->id,
                    'state' => State::REMOVED->value,
                    'last_seen' => now()->format('Y-m-d H:i:s'),
                ],
            ]);
        $this->assertDatabaseHas('state_histories', [
            'id' => $history1->id,
            'sticker_id' => $sticker->id,
            'state' => State::REMOVED->value,
            'last_seen' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
