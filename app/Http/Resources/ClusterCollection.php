<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClusterCollection extends ResourceCollection
{
    /** @var bool */
    protected $includeStickers = false;

    /**
     * Controller can call this before returning.
     */
    public function includeStickers(bool $include): self
    {
        $this->includeStickers = $include;

        return $this;
    }

    /**
     * Transform the resource collection into an array.
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(fn ($cluster) => new ClusterResource($cluster)
                ->includeStickers($this->includeStickers)
                ->toArray($request)
            ),
        ];
    }
}
