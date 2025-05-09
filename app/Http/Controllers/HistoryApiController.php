<?php

namespace App\Http\Controllers;

use App\Http\Resources\StateHistoryResource;
use App\Models\Sticker;
use App\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HistoryApiController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request, Sticker $sticker)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = $request->date('date') ?? now();

        return StateHistoryResource::collection(
            $sticker->stateHistory()
                ->where('last_seen', '<=', $date)
                ->get()
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sticker $sticker)
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::enum(State::class)],
        ]);

        $state = $request->enum('state', State::class) ?? $sticker->latestStateHistory->state;

        return $sticker->stateHistory()->create(['state' => $state, 'last_seen' => now()])->toResource();
    }
}
