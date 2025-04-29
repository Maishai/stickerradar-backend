<?php

namespace App\Traits;

use EmilKlindt\MarkerClusterer\Models\Config;

trait WithClustering
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function getClusteringRules(): array
    {
        return [
            // determins how close stickers need to be together to be considered as neighbours
            'epsilon' => 'nullable|numeric|min:0.1|max:100',
            /** @var int */
            'min_samples' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function getClusteringConfig(): Config
    {
        return new Config([
            'epsilon' => $this->float('epsilon', 5),
            'minSamples' => $this->integer('min_samples', 1),
        ]);
    }
}
