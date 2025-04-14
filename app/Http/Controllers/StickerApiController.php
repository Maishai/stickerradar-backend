<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClusterResource;
use App\Http\Resources\StickerResource;
use App\Models\Sticker;
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
            'epsilon' => 'nullable|numeric|min:0.1|max:100',
            'min_samples' => 'nullable|integer|min:1|max:100',
        ]);

        $epsilon = $request->float('epsilon', 10.5);
        $minSamples = $request->integer('min_samples', 2);

        return Cache::flexible("clusters.$epsilon.$minSamples", [1800, 18000], function () use ($epsilon, $minSamples) {
            $stickers = Sticker::with('tags')->get();
            $config = new Config([
                'epsilon' => $epsilon,
                'minSamples' => $minSamples,
            ]);

            return ClusterResource::collection(DefaultClusterer::cluster($stickers, $config)->values());
        });
    }
}
