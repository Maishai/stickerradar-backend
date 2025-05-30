<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClusterRequest;
use App\Http\Resources\ClusterCollection;
use App\Models\Sticker;
use App\Models\Tag;
use App\Services\ClusteringService;
use App\StickerInclusion;

class ClusterApiController extends Controller
{
    public function __construct(protected ClusteringService $clusteringService) {}

    /**
     * Cluster stickers based on multiple tags.
     *
     * Will resolve counts of stickers to the specified tags.
     *
     * @response AnonymousResourceCollection<ClusterResource>
     */
    public function cluster(ClusterRequest $request)
    {
        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class)
            ?? StickerInclusion::DYNAMIC;

        $tags = $request->tags();
        $bounds = $request->getBounds();
        $dateFilter = $request->has('date') ? $request->date('date') : null;
        $states = $request->states();

        $query = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds($bounds)
            ->with('tags');

        if ($states->isNotEmpty() || $dateFilter !== null) {

            $query->without('latestStateHistory')
                ->whereHas('stateHistory', function ($q) use ($dateFilter, $states) {
                    if ($dateFilter) {
                        $q->where('last_seen', '<=', $dateFilter);
                    }
                    if ($states->isNotEmpty()) {
                        $q->whereIn('state', $states);
                    }
                })
                ->with(['stateHistory' => function ($q) use ($dateFilter, $states) {
                    if ($dateFilter) {
                        $q->where('last_seen', '<=', $dateFilter);
                    }
                    if ($states->isNotEmpty()) {
                        $q->whereIn('state', $states);
                    }
                    $q->latest('last_seen')->limit(1);
                }]);
        }

        if ($tags->isNotEmpty()) {
            if ($tags->count() === 1) {
                // Single-tag: only include its subTags, except it doesnt have any
                if ($tags->first()->subTags->isEmpty()) {
                    $singleTag = $tags->first();
                    $tagMap = collect([$singleTag->id => $singleTag->id]);
                } else {
                    $tagMap = $tags->first()->subTags->flatMap(fn ($subTag) => collect(Tag::getDescendantIds($subTag->id))
                        ->push($subTag->id)
                        ->mapWithKeys(fn ($id) => [$id => $subTag->id])
                    );
                }
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

        if ($dateFilter !== null || $states->isNotEmpty()) {
            $stickers->each(fn ($sticker) => $sticker
                ->setRelation('latestStateHistory', $sticker->stateHistory->first())
                ->unsetRelation('stateHistory')
            );
        }

        return new ClusterCollection($this->clusteringService->clusterModels($stickers, $request->getClusteringConfig()))
            ->stickerInclusion($stickerInclusion);
    }
}
