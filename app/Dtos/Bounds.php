<?php

namespace App\Dtos;

use Illuminate\Http\Request;

class Bounds
{
    public function __construct(
        public float $minLat,
        public float $maxLat,
        public float $minLon,
        public float $maxLon
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->float('min_lat'),
            $request->float('max_lat'),
            $request->float('min_lon'),
            $request->float('max_lon'),
        );
    }
}
