<?php

namespace Tests\Feature;

use App\Models\StateHistory;
use App\Models\Sticker;
use App\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_no_date_returns_correct_history()
    {
        $sticker = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);
        $oldestHistory = $sticker->latestStateHistory;

        $this->travel(10)->minutes();

        $history1 = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.history.show', ['sticker' => $sticker->id]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'id' => $oldestHistory->id,
                        'sticker_id' => $sticker->id,
                        'state' => $oldestHistory->state->value,
                        'last_seen' => $oldestHistory->last_seen,
                    ],
                    [
                        'id' => $history1->id,
                        'sticker_id' => $sticker->id,
                        'state' => $history1->state->value,
                        'last_seen' => $history1->last_seen,
                    ],
                ],
            ]);
    }

    public function test_show_filter_by_date_returns_correct_history()
    {
        $sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();
        $oldestHistory = $sticker->latestStateHistory;

        $this->travel(10)->minutes();

        $history = $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $date = now();

        $this->travel(10)->minutes();

        $sticker->stateHistory()->create([
            'last_seen' => now(),
            'state' => State::REMOVED,
        ]);

        $response = $this->getJson(route('api.stickers.history.show', ['sticker' => $sticker->id, 'date' => $date->toString()]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'id' => $oldestHistory->id,
                        'sticker_id' => $sticker->id,
                        'state' => $oldestHistory->state->value,
                        'last_seen' => $oldestHistory->last_seen,
                    ],
                    [
                        'id' => $history->id,
                        'sticker_id' => $sticker->id,
                        'state' => $history->state->value,
                        'last_seen' => $history->last_seen,
                    ],
                ],
            ]);
    }

    public function test_show_history_for_non_existing_sticker_returns_not_found()
    {
        $response = $this->getJson(route('api.stickers.history.show', ['sticker' => 'non-existent-uuid']));

        $response->assertNotFound();
    }

    public function test_update_sticker_history_with_state_creates_and_returns_history()
    {
        $sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();

        $this->travel(10)->minutes();

        $history1 = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->freezeTime();

        $response = $this->postJson(route('api.stickers.history.update', $sticker->id), ['state' => State::REMOVED]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => $sticker->latestStateHistory->id,
                    'sticker_id' => $sticker->id,
                    'state' => State::REMOVED->value,
                    'last_seen' => now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z'),
                ],
            ]);
        $this->assertDatabaseHas('state_histories', [
            'sticker_id' => $sticker->id,
            'state' => State::REMOVED->value,
            'last_seen' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_update_sticker_history_without_state_creates_and_returns_history()
    {
        $sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();

        $this->travel(10)->minutes();

        $history1 = StateHistory::factory()->create([
            'sticker_id' => $sticker->id,
            'last_seen' => now(),
            'state' => State::PARTIALLY_REMOVED,
        ]);

        $this->freezeTime();

        $response = $this->postJson(route('api.stickers.history.update', $sticker->id));

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => $sticker->latestStateHistory->id,
                    'sticker_id' => $sticker->id,
                    'state' => State::PARTIALLY_REMOVED->value,
                    'last_seen' => now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z'),
                ],
            ]);
        $this->assertDatabaseHas('state_histories', [
            'sticker_id' => $sticker->id,
            'state' => State::PARTIALLY_REMOVED->value,
            'last_seen' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_update_history_for_non_existing_sticker_returns_not_found()
    {
        $response = $this->postJson(route('api.stickers.history.update', ['sticker' => 'non-existent-uuid']));

        $response->assertNotFound();
    }

    public function test_update_history_with_invalid_state_enum_value_returns_validation_error()
    {
        $sticker = Sticker::factory(['lat' => 20, 'lon' => 20])->create();

        $this->travel(10)->minutes();

        $response = $this->postJson(route('api.stickers.history.update', $sticker->id), ['state' => 'invalid_state']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }
}
