<?php

namespace App\Http\Controllers;

use App\Http\Resources\StickerResource;
use App\Models\Sticker;
use App\Services\StickerService;
use App\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $query = Sticker::query()->with('tags');

        if ($request->has('min_lat') && $request->has('max_lat')) {
            $query->whereBetween('lat', [$request->query('min_lat'), $request->query('max_lat')]);
        }

        if ($request->has('min_lon') && $request->has('max_lon')) {
            $query->whereBetween('lon', [$request->query('min_lon'), $request->query('max_lon')]);
        }

        $stickers = $query->get();

        return StickerResource::collection($stickers);
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
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
            'state' => [Rule::enum(State::class)],
        ]);

        $data = [
            'lat' => $validated['lat'],
            'lon' => $validated['lon'],
        ];

        return new StickerResource($this->stickerService->createSticker(
            $data,
            $validated['image'],
            $validated['tags'],
            $validated['state'] ?? State::EXISTS,
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        return new StickerResource(Sticker::query()->with('tags')->findOrFail($uuid));
    }
}
