<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClusterResource;
use App\Models\Sticker;
use App\Models\Tag;
use App\Rules\MaxTileSize;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

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
        $epsilon = $request->float('epsilon', 5);
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
        $minSamples = $request->integer('min_samples', 1);

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

        $tagMap = $this->resolveSubTagsToParent($tag->subTags->all());
        $stickers = $this->replaceTagsWithParents($stickers, $tagMap);

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

    private function resolveSubTagsToParent(array $parentTags): array 
    {
        $tagMap = [];
    
        foreach ($parentTags as $parentTag) {
            $subtags = $parentTag->subTags->all();

            while (! empty($subtags))
            {
                $tag = array_pop($subtags);
                $tagMap[$tag->id] = $parentTag->id;
    
                if (! $tag->relationLoaded('subTags')) {
                    $tag->load('subTags');
                }
    
                $subtags = array_merge($subtags, $tag->subTags->all());
            }
        }
        return $tagMap;
    }

    private function replaceTagsWithParents(Collection $stickers, array $tagMap): Collection {
        foreach ($stickers as $sticker) {
            $newTags = [];
    
            foreach ($sticker['tags'] as $tag) {
                $tagId = $tag['id'];

                if (array_key_exists($tagId, $tagMap)) {
                    $newTags[] = $tagMap[$tagId];
                }
                if (!in_array($tagId, $newTags) && array_search($tagId, $tagMap) !== false) {
                    //Add tag to newTags if it is not already present and is a parent tag
                    $newTags[] = $tagId;
                }
            }

            $sticker['count_tags'] = array_values(array_unique($newTags));
        }
        return $stickers;
    }
    
}
