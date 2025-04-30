<?php

namespace App\Services;

use App\ClusterPoint;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Interfaces\Clusterable;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Support\Collection;

class ClusteringService
{
    /**
     * Cluster a set of Clusterable models by their clusterable coordinates.
     *
     * @param  Collection|Clusterable[]  $models  Collection of models implementing Clusterable
     * @param  Config  $config  Clustering configuration object
     * @return Collection Collection of clusters with ->markers as full models
     */
    public function clusterModels(Collection $models, Config $config): Collection
    {
        $points = $models->map(function (Clusterable $model) {
            $coord = $model->getClusterableCoordinate();

            return new ClusterPoint($model->id, $coord->getLatitude(), $coord->getLongitude());
        });

        $lookupModels = $models->keyBy('id');

        $clusters = DefaultClusterer::cluster($points, $config);

        $clusters->each(function ($cluster) use ($lookupModels) {
            $cluster->markers = $cluster->markers
                ->map(fn ($point) => $lookupModels[$point->id])
                ->filter()
                ->values();
        });

        return $clusters;
    }
}
