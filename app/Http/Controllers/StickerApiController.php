<?php

namespace App\Http\Controllers;

use App\Dtos\Bounds;
use App\Http\Requests\StoreStickerRequest;
use App\Http\Resources\StickerResource;
use App\Models\Sticker;
use App\Rules\MaxTileSize;
use App\Rules\NoSuperTag;
use App\Services\StickerService;
use App\State;
use Illuminate\Http\Request;

class StickerApiController extends Controller
{
    protected StickerService $stickerService;

    public function __construct(StickerService $stickerService)
    {
        $this->stickerService = $stickerService;
    }

    /**
     * Get all stickers in a viewport.
     **/
    public function index(Request $request)
    {
        $request->validate([
            /** @var float */
            'min_lat' => ['required', 'numeric', 'between:-90,90', new MaxTileSize(100)],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lon' => ['required', 'numeric', 'between:-180,180'],
            'max_lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds(Bounds::fromRequest($request))
            ->with('tags')
            ->get();

        return StickerResource::collection($stickers);
    }

    /**
     * Create a new Sticker
     *
     * This endpoint is heavily ratelimited.
     * It also checks the incoming image using AI - to disable this, set the X-API-KEY header.
     **/
    public function store(StoreStickerRequest $request)
    {
        $validated = $request->validated();

        $state = $request->enum('state', State::class) ?? State::EXISTS;

        return new StickerResource(
            $this->stickerService->createSticker(
                ['lat' => $validated['lat'], 'lon' => $validated['lon']],
                $validated['image'],
                $validated['tags'],
                $state
            )
        );
    }

    /**
     * Get a specific sticker by id
     **/
    public function show($uuid)
    {
        return new StickerResource(Sticker::query()->with('tags')->findOrFail($uuid));
    }

    /**
     * Update tags of a sticker
     *
     * Just overwrite all tags of a sticker. Not sure if this is a good idea. Highly ratelimited.
     **/
    public function update(Request $request, Sticker $sticker)
    {
        $validated = $request->validate([
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'uuid|exists:tags,id',
        ]);

        $sticker->tags()->sync($request->array('tags'));
    }
}
