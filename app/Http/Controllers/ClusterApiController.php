<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClusterResource;
use App\Models\Sticker;
use App\Models\Tag;
use App\Rules\MaxTileSize;
use App\Rules\NoSuperTag;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Database\Eloquent\Collection;
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
        $epsilon = $request->float('epsilon', 5);
        $minSamples = $request->integer('min_samples', 2);

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
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

        $allSubTags = Tag::getDescendantIds($tag->id);

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
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

    public function showMultiple(Request $request)
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
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'required|uuid|exists:tags,id',
        ]);

        $minLat = $request->float('min_lat');
        $maxLat = $request->float('max_lat');
        $minLon = $request->float('min_lon');
        $maxLon = $request->float('max_lon');
        $epsilon = $request->float('epsilon', 10.5);
        $minSamples = $request->integer('min_samples', 1);
        $tags = $request->array('tags');

        $allSubTags = [];
        foreach ($tags as $tagId) {
            $descendants = Tag::getDescendantIds($tagId);
            $allSubTags[] = $tagId;
            $allSubTags = array_merge($allSubTags, $descendants);
        }
        $allSubTags = array_unique($allSubTags);

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
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

        $tagMap = $this->resolveSubTagsToParent($tags);
        $stickers = $this->replaceTagsWithParents($stickers, $tagMap);

        return ClusterResource::collection(DefaultClusterer::cluster($stickers, $config)->values());
    }

    private function resolveSubTagsToParent(array $parentTags): array
    {
        $tagMap = [];

        foreach ($parentTags as $parentTag) {
            $parentId = is_object($parentTag) ? $parentTag->id : $parentTag;

            $subtags = Tag::getDescendantIds($parentId);

            foreach ($subtags as $tagId) {
                $tagMap[$tagId] = $parentId;
            }
            $tagMap[$parentId] = $parentId;
        }

        return $tagMap;
    }

    private function replaceTagsWithParents(Collection $stickers, array $tagMap): Collection
    {
        foreach ($stickers as $sticker) {
            $newTags = [];

            foreach ($sticker['tags'] as $tag) {
                $tagId = $tag['id'];

                if (array_key_exists($tagId, $tagMap)) {
                    $newTags[] = $tagMap[$tagId];
                }
            }

            $sticker['count_tags'] = array_values(array_unique($newTags));
        }

        return $stickers;
    }
}
