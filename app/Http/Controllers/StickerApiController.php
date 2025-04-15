<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClusterResource;
use App\Http\Resources\StickerResource;
use App\Models\Sticker;
use App\Rules\MaxTileSize;
use App\Rules\StickerImage;
use App\Services\StickerService;
use App\State;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'image' => ['required', new StickerImage],
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
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

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        return new StickerResource(Sticker::query()->with('tags')->findOrFail($uuid));
    }

    /**
     * Cluster the stickers.
     *
     * @response array{data: ClusterResource[]}
     */
    public function clusters(Request $request)
    {
        $request->validate([
            /** @var float */
            'min_lat' => ['required', 'numeric', 'between:-90,90', new MaxTileSize(1000)],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lon' => ['required', 'numeric', 'between:-180,180'],
            'max_lon' => ['required', 'numeric', 'between:-180,180'],
            'epsilon' => 'nullable|numeric|min:0.1|max:100',
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
        ]);

        $minLat = $request->float('min_lat');
        $maxLat = $request->float('max_lat');
        $minLon = $request->float('min_lon');
        $maxLon = $request->float('max_lon');
        $epsilon = $request->float('epsilon', 10.5);
        $minSamples = $request->integer('min_samples', 2);

        return Cache::flexible("clusters.$minLat.$maxLat.$minLon.$maxLon.$epsilon.$minSamples", [1800, 18000], function () use ($epsilon, $minSamples, $minLat, $maxLat, $minLon, $maxLon) {

            $stickers = Sticker::query()
                ->with('tags')
                ->whereBetween('lat', [$minLat, $maxLat])
                ->whereBetween('lon', [$minLon, $maxLon])
                ->get();

            $config = new Config([
                'epsilon' => $epsilon,
                'minSamples' => $minSamples,
            ]);

            return ClusterResource::collection(DefaultClusterer::cluster($stickers, $config)->values());
        });
    }
}
