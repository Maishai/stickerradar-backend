<?php

namespace App\Http\Controllers;

use App\Dtos\Bounds;
use App\Http\Resources\StateHistoryResource;
use App\Models\Sticker;
use App\Rules\MaxTileSize;
use App\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HistoryApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            /** @var float */
            'min_lat' => ['required', 'numeric', 'between:-90,90', new MaxTileSize(100)],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lon' => ['required', 'numeric', 'between:-180,180'],
            'max_lon' => ['required', 'numeric', 'between:-180,180'],
            'date' => ['nullable', 'date'],
        ]);

        $date = $request->date('date') ?? now();

        /* Alte Query
        return Sticker::query()
            ->without('latestStateHistory')
            ->olderThanTenMinutes()
            ->withinBounds(Bounds::fromRequest($request))
            ->with([
                'latestStateHistory' => function ($q) use ($date) {
                    $q->where('last_seen', '<=', $date);
                },
            ])
            ->get()
            ->pluck('latestStateHistory')
            ->toResourceCollection();*/

        $stickers = Sticker::query()
            ->without('latestStateHistory')
            ->olderThanTenMinutes()
            ->withinBounds(Bounds::fromRequest($request))
            ->get();

        $stickers->each(function ($sticker) use ($date) {
            $sticker->setRelation('latestStateHistory', $sticker->latestStateHistoryBefore($date)->first());
        });

        return $stickers
            ->pluck('latestStateHistory')
            ->toResourceCollection();

        /* Alternative, aber ist kein JSON mehr
        $stateHistories = $stickers
            ->map(function ($sticker) use ($date) {
                return $sticker->stateHistory
                    ->filter(fn ($history) => $history->last_seen <= $date)
                    ->sortByDesc('last_seen')
                    ->first();
            })
            ->filter();

        return StateHistoryResource::collection($stateHistories);*/
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Sticker $sticker)
    {
        $request->validate([
            /** @var float */
            'date' => ['nullable', 'date'],
        ]);

        $date = $request->date('date') ?? now();

        /* Alte Query
        return $sticker
            ->with([
                'latestStateHistory' => function ($q) use ($date) {
                    $q->where('last_seen', '<=', $date);
                },
            ])
            ->get()
            ->pluck('latestStateHistory')
            ->first()
            ->toResource();*/
        return new StateHistoryResource(
            $sticker->stateHistory()
                ->where('last_seen', '<=', $date)
                ->orderBy('last_seen', 'desc')
                ->firstOrFail()
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
