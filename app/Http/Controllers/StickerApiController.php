<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use App\Services\StickerService;
use Illuminate\Http\Request;

class StickerApiController extends Controller
{
    protected StickerService $stickerService;

    public function __construct(StickerService $stickerService)
    {
        $this->stickerService = $stickerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sticker::query();

        if ($request->has('min_lat') && $request->has('max_lat')) {
            $query->whereBetween('lat', [$request->query('min_lat'), $request->query('max_lat')]);
        }

        if ($request->has('min_lon') && $request->has('max_lon')) {
            $query->whereBetween('lon', [$request->query('min_lon'), $request->query('max_lon')]);
        }

        $stickers = $query->get();

        return $stickers->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:4096',
            'tag' => 'required|array',
            'tag.*' => 'exists:tags,id',
        ]);

        $data = [
            'lat' => $validated['lat'],
            'lon' => $validated['lon'],
        ];

        return $this->stickerService->createSticker(
            $data,
            $validated['image'],
            $validated['tag']
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Sticker $sticker)
    {
        return $sticker->toJson();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
