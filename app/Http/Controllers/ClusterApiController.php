<?php

namespace App\Http\Controllers;

use App\ClusterPoint;
use App\Http\Requests\ClusterIndexRequest;
use App\Http\Requests\ClusterShowMultipleRequest;
use App\Http\Resources\ClusterCollection;
use App\Http\Resources\ClusterResource;
use App\Models\Sticker;
use App\Models\Tag;
use App\StickerInclusion;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Cluster;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClusterApiController extends Controller
{
    /**
     * Cluster all stickers in a viewport.
     *
     * Epsilon determins how close stickers need to be together to be considered as neighbours.
     *
     * @response AnonymousResourceCollection<ClusterResource>
     */
    public function index(ClusterIndexRequest $request)
    {
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        // fetch only minimal fields needed for clustering
        $points = Sticker::query()
            ->without('latestStateHistory')
            ->olderThanTenMinutes()
            ->withinBounds($request->getBounds())
            ->get(['id', 'lat', 'lon'])
            ->map(fn (Sticker $s) => new ClusterPoint($s->id, $s->lat, $s->lon));

        // clustering on lightweight points
        $clusters = DefaultClusterer::cluster($points, $request->getClusteringConfig());

        // load full sticker models
        $fullStickersById = Sticker::with('tags')
            ->whereIn('id', $points->pluck('id')->toArray())
            ->get()
            ->keyBy('id');

        // replace cluster markers with full stickers
        $clusters->each(function ($cluster) use ($fullStickersById) {
            $cluster->markers = $cluster->markers
                ->map(fn ($point) => $fullStickersById[$point->id] ?? null)
                ->filter()
                ->values();
        });

        return new ClusterCollection($clusters->values())
            ->stickerInclusion($stickerInclusion);
    }

    /**
     * Cluster stickers based on one parent tag.
     *
     * E.g. clustering on "Politk" tag will resolve counts of stickers to its direct ancestors ("Links", "Rechts")
     *
     * @response AnonymousResourceCollection<ClusterResource>
     */
    public function show(ClusterIndexRequest $request, Tag $tag)
    {
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        $tagMap = $tag->subTags->flatMap(fn ($t) => collect(Tag::getDescendantIds($t->id))
            ->push($t->id)
            ->mapWithKeys(fn ($id) => [$id => $t->id])
        );

        $allSubTags = $tagMap->keys()->unique();

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds($request->getBounds())
            ->with('tags')
            ->whereHas('tags', function ($query) use ($allSubTags) {
                $query->whereIn('tags.id', $allSubTags);
            })
            ->get();

        $stickers->each(function ($sticker) use ($tagMap) {
            $sticker->count_tags = $sticker->tags
                ->pluck('id')
                ->map(fn ($id) => $tagMap->get($id))
                ->filter()
                ->unique()
                ->values();
        });

        return new ClusterCollection(DefaultClusterer::cluster($stickers, $request->getClusteringConfig())->values())
            ->stickerInclusion($stickerInclusion);
    }

    /**
     * Cluster stickers based on multiple tags.
     *
     * Will resolve counts of stickers to the specified tags.
     *
     * @response AnonymousResourceCollection<ClusterResource>
     */
    public function showMultiple(ClusterShowMultipleRequest $request)
    {
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        $tagMap = $request->collect('tags')
            ->mapWithKeys(fn ($tagId) => collect(Tag::getDescendantIds($tagId))
                ->push($tagId)
                ->mapWithKeys(fn ($id) => [$id => $tagId])
            );

        $allSubTags = $tagMap->keys()->unique();

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->with('tags')
            ->withinBounds($request->getBounds())
            ->whereHas('tags', function ($query) use ($allSubTags) {
                $query->whereIn('tags.id', $allSubTags);
            })
            ->get();

        $stickers->each(function ($sticker) use ($tagMap) {
            $sticker->count_tags = $sticker->tags
                ->pluck('id')
                ->map(fn ($id) => $tagMap->get($id))
                ->filter()
                ->unique()
                ->values();
        });

        return new ClusterCollection(DefaultClusterer::cluster($stickers, $request->getClusteringConfig())->values())
            ->stickerInclusion($stickerInclusion);
    }
}
