<?php

namespace App\Services;

use EmilKlindt\MarkerClusterer\Interfaces\Clusterable;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

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
        $input = $models->map(function ($model) {
            $coord = $model->getClusterableCoordinate();

            return json_encode([
                'id' => (string) $model->id,
                'lat' => (float) $coord->getLatitude(),
                'lon' => (float) $coord->getLongitude(),
            ]);
        })->implode("\n");

        $exe = php_uname('m') === 'arm64' ? 'arm64-dbscan-cli' : 'dbscan-cli';

        $epsilon = $config->epsilon / 5000;
        $result = Process::input($input)->run(base_path($exe).' --eps='.$epsilon.' --minPts='.$config->minSamples);

        if (! $result->successful()) {
            throw new \RuntimeException('DBSCAN process failed: '.$result->errorOutput()."\ninput:\n".$input);
        }

        $clustersData = json_decode($result->output(), true);
        $modelLookup = $models->keyBy('id');

        // Map back to original models
        return collect($clustersData)->map(function (array $cluster) use ($modelLookup) {
            $markers = collect($cluster['ids'])
                ->map(fn ($id) => $modelLookup[$id])
                ->filter()
                ->values();

            return (object) [
                'centroid' => [
                    /** @var float */
                    'lat' => $cluster['centroid_lat'],
                    /** @var float */
                    'lon' => $cluster['centroid_lon'],
                ],
                'markers' => $markers,
            ];
        });
    }
}
