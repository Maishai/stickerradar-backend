<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClusterIndexRequest;
use App\Http\Requests\ClusterShowMultipleRequest;
use App\Http\Resources\ClusterCollection;
use App\Models\Sticker;
use App\Models\Tag;
use App\Services\ClusteringService;
use App\StickerInclusion;

class ClusterApiController extends Controller
{
    public function __construct(protected ClusteringService $clusteringService) {}

    /**
     * Cluster all stickers in a viewport.
     *
     * @response AnonymousResourceCollection<ClusterResource>
     */
    public function index(ClusterIndexRequest $request)
    {
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds($request->getBounds())
            ->with('tags')
            ->get();

        return new ClusterCollection($this->clusteringService->clusterModels($stickers, $request->getClusteringConfig()))
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

        return new ClusterCollection($this->clusteringService->clusterModels($stickers, $request->getClusteringConfig()))
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
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class)
            ?? StickerInclusion::DYNAMIC;

        $tags = $request->tags();
        $bounds = $request->getBounds();
        $dateFilter = $request->has('date') ? $request->date('date') : null;

        $query = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds($bounds)
            ->with('tags')
            ->when($dateFilter, function ($q, $date) {
                $q->without('latestStateHistory')
                    ->whereHas('stateHistory', fn ($q) => $q->where('last_seen', '<=', $date));
            })
            ->with(['stateHistory' => function ($q) use ($dateFilter, $request) {
                if ($dateFilter) {
                    $q->where('last_seen', '<=', $dateFilter);
                }
                if ($request->states()->isNotEmpty()) {
                    $q->whereIn('state', $request->states());
                }
                $q->latest('last_seen')->limit(1);
            }]);

        if ($tags->isNotEmpty()) {
            if ($tags->count() === 1) {
                // Single-tag: include its subTags
                $tagMap = $tags->first()->subTags->flatMap(fn ($subTag) => collect(Tag::getDescendantIds($subTag->id))
                    ->push($subTag->id)
                    ->mapWithKeys(fn ($id) => [$id => $subTag->id])
                );
            } else {
                // Multiple tags: include each tag and its descendants
                $tagMap = $tags->flatMap(fn ($tag) => collect(Tag::getDescendantIds($tag->id))
                    ->push($tag->id)
                    ->mapWithKeys(fn ($id) => [$id => $tag->id])
                );
            }

            $allSubTagIds = $tagMap->keys()->unique();

            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $allSubTagIds));
        }

        $stickers = $query->get();

        if ($tags->isNotEmpty()) {
            $stickers->each(fn ($sticker) => $sticker->count_tags = $sticker->tags
                ->pluck('id')
                ->map(fn ($id) => $tagMap->get($id))
                ->filter()
                ->unique()
                ->values()
            );
        }

        if ($dateFilter) {
            $stickers->each(fn ($sticker) => $sticker
                ->setRelation('latestStateHistory', $sticker->stateHistory->first())
                ->unsetRelation('stateHistory')
            );
        }

        return new ClusterCollection($this->clusteringService->clusterModels($stickers, $request->getClusteringConfig()))
            ->stickerInclusion($stickerInclusion);
    }
}
