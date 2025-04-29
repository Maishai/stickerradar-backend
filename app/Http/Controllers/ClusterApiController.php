<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClusterIndexRequest;
use App\Http\Requests\ClusterShowMultipleRequest;
use App\Http\Resources\ClusterCollection;
use App\Http\Resources\ClusterResource;
use App\Models\Sticker;
use App\Models\Tag;
use App\StickerInclusion;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Config;
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

        $config = new Config([
            'epsilon' => $request->float('epsilon', 5),
            'minSamples' => $request->integer('min_samples', 1),
        ]);

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds($request->getBounds())
            ->with('tags')
            ->get();

        return new ClusterCollection(DefaultClusterer::cluster($stickers, $request->getClusteringConfig())->values())
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
