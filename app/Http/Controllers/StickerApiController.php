<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStickerRequest;
use App\Http\Resources\StickerResource;
use App\Models\Sticker;
use App\Rules\ContainsUncertainTag;
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
     * Update tags of a sticker
     *
     * Just overwrite all tags of a sticker. Not sure if this is a good idea. Highly ratelimited.
     **/
    public function update(Request $request, Sticker $sticker)
    {
        $validated = $request->validate([
            'tags' => ['required', 'array', new NoSuperTag, new ContainsUncertainTag($sticker)],
            'tags.*' => 'uuid|exists:tags,id',
        ]);

        $sticker->tags()->sync($request->array('tags'));
    }
}
