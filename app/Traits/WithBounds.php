<?php

namespace App\Traits;

use App\Dtos\Bounds;
use App\Rules\MaxTileSize;

trait WithBounds
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function getBoundsRules(): array
    {
        return [
            /** @var float */
            'min_lat' => ['required', 'numeric', 'between:-90,90', new MaxTileSize(1000)],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lon' => ['required', 'numeric', 'between:-180,180'],
            'max_lon' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function getBounds(): Bounds
    {
        return Bounds::fromRequest($this);
    }
}
