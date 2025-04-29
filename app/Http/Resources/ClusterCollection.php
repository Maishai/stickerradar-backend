<?php

namespace App\Http\Resources;

use App\StickerInclusion;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ClusterCollection extends ResourceCollection
{
    protected StickerInclusion $stickerInclusion;

    /**
     * Controller can call this before returning.
     */
    public function stickerInclusion(StickerInclusion $stickerInclusion): self
    {
        $this->stickerInclusion = $stickerInclusion;

        return $this;
    }

    /**
     * Transform the resource collection into an array.
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(fn ($cluster) => new ClusterResource($cluster)
                ->stickerInclusion($this->stickerInclusion)
                ->toArray($request)
            ),
        ];
    }
}
