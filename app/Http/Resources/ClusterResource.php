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
        $colorCounts = $this->markers
            ->pluck('tags')
            ->flatten(1)
            ->pluck('color')
            ->countBy();

        $mostCommonColor = $colorCounts->sortDesc()->keys()->first();

        return [
            'centroid' => [
                /** @var float */
                'lat' => (float) $this->centroid->getLatitude(),
                /** @var float */
                'lon' => (float) $this->centroid->getLongitude(),
            ],
            'color' => $mostCommonColor,
            /** @var int */
            'count' => $this->markers->count(),
        ];
    }
}
