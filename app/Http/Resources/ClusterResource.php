<?php

namespace App\Http\Resources;

use App\StickerInclusion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClusterResource extends JsonResource
{
    protected bool $includeStickers;

    /**
     * Setter so the controller can turn this on or off.
     */
    public function stickerInclusion(StickerInclusion $stickerInclusion): self
    {
        $this->includeStickers = match ($stickerInclusion) {
            StickerInclusion::INCLUDE => true,
            StickerInclusion::HIDE => false,
            StickerInclusion::DYNAMIC => $this->markers->count() <= 15,
        };

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->markers->first()['count_tags'] != null) {
            $tagCounts = $this->markers
                ->pluck('count_tags')
                ->flatten(1)
                ->countBy()
                ->sortDesc();
        } else {
            $tagCounts = $this->markers
                ->pluck('tags')
                ->flatten(1)
                ->pluck('id')
                ->countBy()
                ->sortDesc();
        }

        return [
            'centroid' => [
                /** @var float */
                'lat' => (float) $this->centroid->getLatitude(),
                /** @var float */
                'lon' => (float) $this->centroid->getLongitude(),
            ],
            /** @var array<string, int> */
            'tag_counts' => $tagCounts,
            /** @var int */
            'count' => $this->markers->count(),
            'stickers' => $this->when($this->includeStickers, StickerResource::collection($this->markers)),
        ];
    }
}
