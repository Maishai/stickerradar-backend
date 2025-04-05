<?php

namespace App\Http\Resources;

use App\State;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StickerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lat' => $this->lat,
            'lon' => $this->lon,
            /**
             * @var State
             */
            'state' => $this->state,
            /**
             * YYYY-MM-DD, e.g. 2025-01-25
             *
             * @var \Illuminate\Support\Carbon
             *
             * @format /^\d{4}-\d{2}-\d{2}$/
             */
            'last_seen' => $this->last_seen,
            'filename' => $this->filename,
            /**
             * @var array<string>
             */
            'tags' => $this->tags->pluck('id')->toArray(),
        ];
    }
}
