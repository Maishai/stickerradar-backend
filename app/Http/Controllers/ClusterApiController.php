<?php

namespace App\Http\Controllers;

use App\Dtos\Bounds;
use App\Http\Resources\ClusterCollection;
use App\Http\Resources\ClusterResource;
use App\Models\Sticker;
use App\Models\Tag;
use App\Rules\MaxTileSize;
use App\Rules\NoSuperTag;
use App\StickerInclusion;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'include_stickers' => ['nullable', Rule::enum(StickerInclusion::class)],
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
        ]);

        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        $config = new Config([
            'epsilon' => $request->float('epsilon', 5),
            'minSamples' => $request->integer('min_samples', 1),
        ]);

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds(Bounds::fromRequest($request))
            ->with('tags')
            ->get();

        $includeStickers = match ($stickerInclusion) {
            StickerInclusion::INCLUDE => true,
            StickerInclusion::HIDE => false,
            StickerInclusion::DYNAMIC => $stickers->count() <= 15,
        };

        return new ClusterCollection(DefaultClusterer::cluster($stickers, $config)->values())
            ->includeStickers($includeStickers);
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
            'include_stickers' => ['nullable', Rule::enum(StickerInclusion::class)],
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
        ]);

        $config = new Config([
            'epsilon' => $request->float('epsilon', 5),
            'minSamples' => $request->integer('min_samples', 1),
        ]);

        $stickerInclusion = $request->enum('include_stickers', StickerInclusion::class) ?? StickerInclusion::DYNAMIC;

        $tagMap = $tag->subTags->flatMap(fn ($t) => collect(Tag::getDescendantIds($t->id))
            ->push($t->id)
            ->mapWithKeys(fn ($id) => [$id => $t->id])
        );

        $allSubTags = $tagMap->keys()->unique();

        $stickers = Sticker::query()
            ->olderThanTenMinutes()
            ->withinBounds(Bounds::fromRequest($request))
            ->with('tags')
            ->whereHas('tags', function ($query) use ($allSubTags) {
                $query->whereIn('tags.id', $allSubTags);
            })
            ->get();

        $includeStickers = match ($stickerInclusion) {
            StickerInclusion::INCLUDE => true,
            StickerInclusion::HIDE => false,
            StickerInclusion::DYNAMIC => $stickers->count() <= 15,
        };

        $stickers->each(function ($sticker) use ($tagMap) {
            $sticker->count_tags = $sticker->tags
                ->pluck('id')
                ->map(fn ($id) => $tagMap->get($id))
                ->filter()
                ->unique()
                ->values();
        });

        return new ClusterCollection(DefaultClusterer::cluster($stickers, $config)->values())
            ->includeStickers($includeStickers);
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
            'include_stickers' => ['nullable', Rule::enum(StickerInclusion::class)],
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'required|uuid|exists:tags,id',
        ]);

        $config = new Config([
            'epsilon' => $request->float('epsilon', 5),
            'minSamples' => $request->integer('min_samples', 1),
        ]);

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
            ->withinBounds(Bounds::fromRequest($request))
            ->whereHas('tags', function ($query) use ($allSubTags) {
                $query->whereIn('tags.id', $allSubTags);
            })
            ->get();

        $includeStickers = match ($stickerInclusion) {
            StickerInclusion::INCLUDE => true,
            StickerInclusion::HIDE => false,
            StickerInclusion::DYNAMIC => $stickers->count() <= 15,
        };

        $stickers->each(function ($sticker) use ($tagMap) {
            $sticker->count_tags = $sticker->tags
                ->pluck('id')
                ->map(fn ($id) => $tagMap->get($id))
                ->filter()
                ->unique()
                ->values();
        });

        return new ClusterCollection(DefaultClusterer::cluster($stickers, $config)->values())
            ->includeStickers($includeStickers);
    }
}
