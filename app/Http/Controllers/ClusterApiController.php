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
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        $tags = $request->tags();
        $date = $request->date('date') ?? now();
        $stickers = null;

        switch ($tags->count()) {
            case 0:
                $stickers = Sticker::query()
                    ->olderThanTenMinutes()
                    ->withinBounds($request->getBounds())
                    ->without('latestStateHistory')
                    // only keep stickers with history <= $date
                    ->whereHas('stateHistory', fn ($q) => $q->where('last_seen', '<=', $date))
                    ->with([
                        'tags',
                        'stateHistory' => fn ($q) => $q
                            ->where('last_seen', '<=', $date)
                            // ->orderByDesc('last_seen')
                            // ->limit(1),
                            // not sure if latest does what i think
                            ->latest('last_seen'),
                    ])
                    ->get()
                    // this is ugly, maybe do distinction instead in StickerResource
                    ->map(function ($sticker) {
                        $one = $sticker->stateHistory->first();
                        $sticker->setRelation('latestStateHistory', $one);
                        $sticker->unsetRelation('stateHistory');

                        return $sticker;
                    });
                break;
            case 1:
                $tagMap = $tags->first()->subTags->flatMap(fn ($t) => collect(Tag::getDescendantIds($t->id))
                    ->push($t->id)
                    ->mapWithKeys(fn ($id) => [$id => $t->id])
                );

                $allSubTags = $tagMap->keys()->unique();

                $stickers = Sticker::query()
                    ->olderThanTenMinutes()
                    ->withinBounds($request->getBounds())
                    ->without('latestStateHistory')
                    ->with('tags')
                    ->whereHas('tags', function ($query) use ($allSubTags) {
                        $query->whereIn('tags.id', $allSubTags);
                    })
                    ->whereHas('stateHistory', fn ($q) => $q->where('last_seen', '<=', $date))
                    ->with([
                        'tags',
                        'stateHistory' => fn ($q) => $q
                            ->where('last_seen', '<=', $date)
                            // ->orderByDesc('last_seen')
                            // ->limit(1),
                            // not sure if latest does what i think
                            ->latest('last_seen'),
                    ])
                    ->get()
                    // this is ugly, maybe do distinction instead in StickerResource
                    ->map(function ($sticker) {
                        $one = $sticker->stateHistory->first();
                        $sticker->setRelation('latestStateHistory', $one);
                        $sticker->unsetRelation('stateHistory');

                        return $sticker;
                    });

                $stickers->each(function ($sticker) use ($tagMap) {
                    $sticker->count_tags = $sticker->tags
                        ->pluck('id')
                        ->map(fn ($id) => $tagMap->get($id))
                        ->filter()
                        ->unique()
                        ->values();
                });
                break;
            default:
                $tagMap = $tags
                    ->pluck('id')
                    ->mapWithKeys(fn ($tagId) => collect(Tag::getDescendantIds($tagId))
                        ->push($tagId)
                        ->mapWithKeys(fn ($id) => [$id => $tagId])
                    );
                $allSubTags = $tagMap->keys()->unique();

                $stickers = Sticker::query()
                    ->olderThanTenMinutes()
                    ->withinBounds($request->getBounds())
                    ->without('latestStateHistory')
                    ->whereHas('tags', function ($query) use ($allSubTags) {
                        $query->whereIn('tags.id', $allSubTags);
                    })
                    ->whereHas('stateHistory', fn ($q) => $q->where('last_seen', '<=', $date))
                    ->with([
                        'tags',
                        'stateHistory' => fn ($q) => $q
                            ->where('last_seen', '<=', $date)
                            // ->orderByDesc('last_seen')
                            // ->limit(1),
                            // not sure if latest does what i think
                            ->latest('last_seen'),
                    ])
                    ->get()
                    // this is ugly, maybe do distinction instead in StickerResource
                    ->map(function ($sticker) {
                        $one = $sticker->stateHistory->first();
                        $sticker->setRelation('latestStateHistory', $one);
                        $sticker->unsetRelation('stateHistory');

                        return $sticker;
                    });

                $stickers->each(function ($sticker) use ($tagMap) {
                    $sticker->count_tags = $sticker->tags
                        ->pluck('id')
                        ->map(fn ($id) => $tagMap->get($id))
                        ->filter()
                        ->unique()
                        ->values();
                });
        }

        return new ClusterCollection($this->clusteringService->clusterModels($stickers, $request->getClusteringConfig()))
            ->stickerInclusion($stickerInclusion);
    }
}
