<?php

namespace Tests\Unit;

use App\ClusterPoint;
use App\Models\Sticker;
use App\Services\ClusteringService;
use EmilKlindt\MarkerClusterer\Facades\DefaultClusterer;
use EmilKlindt\MarkerClusterer\Models\Cluster;
use EmilKlindt\MarkerClusterer\Models\Config;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClusteringServiceTest extends TestCase
{
    // #[Test]
    // public function clusters_real_sticker_models_and_maps_back()
    // {
    //     $sticker1 = new Sticker;
    //     $sticker1->id = '1';
    //     $sticker1->lat = 10.0;
    //     $sticker1->lon = 20.0;
    //
    //     $sticker2 = new Sticker;
    //     $sticker2->id = '2';
    //     $sticker2->lat = 10.5;
    //     $sticker2->lon = 20.5;
    //     $models = collect([$sticker1, $sticker2]);
    //
    //     // Prepare ClusterPoint representations
    //     $point1 = new ClusterPoint('1', 10.0, 20.0);
    //     $point2 = new ClusterPoint('2', 10.5, 20.5);
    //     $fakeCluster = new Cluster([
    //         'markers' => collect([$sticker1, $sticker2]),
    //         'centroid' => null,
    //     ]);
    //     $fakeClusters = collect([$fakeCluster]);
    //
    //     DefaultClusterer::shouldReceive('cluster')
    //         ->once()
    //         ->withArgs(fn (Collection $points, ?Config $config = null) =>
    //             // IDs come back as strings, so compare accordingly
    //             $points->pluck('id')->sort()->values()->all() === ['1', '2']
    //         )
    //         ->andReturn($fakeClusters);
    //
    //     $service = new ClusteringService;
    //
    //     // Act: clusterModels without loader uses original models
    //     $clusters = $service->clusterModels($models, new Config(['epsilon' => 12.0, 'minSamples' => 1]));
    //
    //     // Assert
    //     $this->assertCount(1, $clusters);
    //     $cluster = $clusters->first();
    //     $this->assertCount(2, $cluster->markers);
    //     $this->assertSame('1', $cluster->markers[0]->id);
    //     $this->assertSame('2', $cluster->markers[1]->id);
    // }
    //
    #[Test]
    public function empty_stickers_collection_returns_empty_clusters()
    {
        $models = collect();

        // DefaultClusterer::shouldReceive('cluster')
        //     ->once()
        //     ->withArgs(fn (Collection $points, $config) => $points->isEmpty())
        //     ->andReturn(collect());

        $service = new ClusteringService;
        $clusters = $service->clusterModels($models, new Config(['epsilon' => 12.0, 'minSamples' => 1]));

        $this->assertTrue($clusters->isEmpty());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
