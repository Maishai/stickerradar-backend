<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClusterResource extends JsonResource
{
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
            'stickers' => $this->when($request->boolean('include_stickers'), StickerResource::collection($this->markers)),
        ];
    }
}
