<?php

namespace App\Http\Controllers;

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

        return Sticker::query()
            ->without('latestStateHistory')
            ->olderThanTenMinutes()
            ->whereBetween('lat', [$request->float('min_lat'), $request->float('max_lat')])
            ->whereBetween('lon', [$request->float('min_lon'), $request->float('max_lon')])
            ->with([
                'latestStateHistory' => function ($q) use ($date) {
                    $q->where('last_seen', '<=', $date);
                },
            ])
            ->get()
            ->pluck('latestStateHistory')
            ->toResourceCollection();
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

        return $sticker
            ->with([
                'latestStateHistory' => function ($q) use ($date) {
                    $q->where('last_seen', '<=', $date);
                },
            ])
            ->get()
            ->pluck('latestStateHistory')
            ->first()
            ->toResource();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sticker $sticker)
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::enum(State::class)],
        ]);

        $state = $request->enum('state') ?? $sticker->latestStateHistory->state;

        return $sticker->stateHistory()->create(['state' => $state, 'last_seen' => now()])->toResource();
    }
}
