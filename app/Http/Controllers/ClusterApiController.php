<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClusterResource;
use App\Models\Sticker;
use App\Models\Tag;
use App\Rules\MaxTileSize;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Http\Request;

class ClusterApiController extends Controller
{
    /**
     * Cluster all stickers.
     *
     * @response array{data: ClusterResource[]}
     */
    public function index(Request $request)
    {
        $request->validate([
            /** @var float */
            'min_lat' => ['required', 'numeric', 'between:-90,90', new MaxTileSize(1000)],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lon' => ['required', 'numeric', 'between:-180,180'],
            'max_lon' => ['required', 'numeric', 'between:-180,180'],
            'epsilon' => 'nullable|numeric|min:0.1|max:100',
            /** @var bool */
            'include_stickers' => ['nullable', 'in:0,1,true,false'],
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
        ]);

        $minLat = $request->float('min_lat');
        $maxLat = $request->float('max_lat');
        $minLon = $request->float('min_lon');
        $maxLon = $request->float('max_lon');
        $epsilon = $request->float('epsilon', 10.5);
        $minSamples = $request->integer('min_samples', 2);

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
    }

    /**
     * Cluster stickers based on one parent tag.
     */
    public function show(Request $request, Tag $tag)
    {
        $request->validate([
            /** @var float */
            'min_lat' => ['required', 'numeric', 'between:-90,90', new MaxTileSize(1000)],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lon' => ['required', 'numeric', 'between:-180,180'],
            'max_lon' => ['required', 'numeric', 'between:-180,180'],
            'epsilon' => 'nullable|numeric|min:0.1|max:100',
            /** @var bool */
            'include_stickers' => ['nullable', 'in:0,1,true,false'],
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
        ]);

        $minLat = $request->float('min_lat');
        $maxLat = $request->float('max_lat');
        $minLon = $request->float('min_lon');
        $maxLon = $request->float('max_lon');
        $epsilon = $request->float('epsilon', 10.5);
        $minSamples = $request->integer('min_samples', 2);

        $allSubTags = $this->getAllSubTags($tag);

        $stickers = Sticker::query()
            ->with('tags')
            ->whereBetween('lat', [$minLat, $maxLat])
            ->whereBetween('lon', [$minLon, $maxLon])
            ->whereHas('tags', function ($query) use ($allSubTags) {
                $query->whereIn('tags.id', $allSubTags);
            })
            ->get();

        $config = new Config([
            'epsilon' => $epsilon,
            'minSamples' => $minSamples,
        ]);

        return ClusterResource::collection(DefaultClusterer::cluster($stickers, $config)->values());
    }

    private function getAllSubTags(Tag $parentTag)
    {
        $allSubTags = collect();
        $stack = $parentTag->subTags->all();

        while (! empty($stack)) {
            $tag = array_pop($stack);
            $allSubTags->push($tag->id);

            if (! $tag->relationLoaded('subTags')) {
                $tag->load('subTags');
            }
            $stack = array_merge($stack, $tag->subTags->all());
        }

        return $allSubTags->push($parentTag->id);
    }
}
