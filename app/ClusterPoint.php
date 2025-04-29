<?php

namespace App;

use EmilKlindt\MarkerClusterer\Interfaces\Clusterable;
use League\Geotools\Coordinate\Coordinate;

class ClusterPoint implements Clusterable
{
    public function __construct(public string $id, public float $lat, public float $lon) {}

    public function getClusterableCoordinate(): Coordinate
    {
        return new Coordinate([$this->lat, $this->lon]);
    }
}
