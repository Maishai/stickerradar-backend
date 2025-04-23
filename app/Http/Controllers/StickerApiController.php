<?php

namespace App\Http\Controllers;

use App\Http\Resources\StickerResource;
use App\Models\Sticker;
use App\Rules\MaxTileSize;
use App\Rules\NoSuperTag;
use App\Rules\StickerImage;
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
            ->with('tags')
            ->whereBetween('lat', [$request->float('min_lat'), $request->float('max_lat')])
            ->whereBetween('lon', [$request->float('min_lon'), $request->float('max_lon')])
            ->get();

        return StickerResource::collection($stickers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'image' => ['required', new StickerImage],
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'uuid|exists:tags,id',
            'state' => [Rule::enum(State::class)],
        ]);

        $data = [
            'lat' => $validated['lat'],
            'lon' => $validated['lon'],
        ];

        $state = isset($validated['state'])
            ? State::from($validated['state'])
            : State::EXISTS;

        return new StickerResource($this->stickerService->createSticker(
            $data,
            $validated['image'],
            $validated['tags'],
            $state
        ));
    }

    public function show($uuid)
    {
        return new StickerResource(Sticker::query()->with('tags')->findOrFail($uuid));
    }

    public function update(Request $request, Sticker $sticker)
    {
        $validated = $request->validate([
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'uuid|exists:tags,id',
        ]);

        $sticker->tags()->sync($request->array('tags'));
    }
}
