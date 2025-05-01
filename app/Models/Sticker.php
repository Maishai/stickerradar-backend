<?php

namespace App\Models;

use App\Dtos\Bounds;
use App\State;
use EmilKlindt\MarkerClusterer\Interfaces\Clusterable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use League\Geotools\Coordinate\Coordinate;

class Sticker extends Model implements Clusterable
{
    use HasFactory;
    use HasUlids;

    protected $fillable = ['lat', 'lon', 'last_seen', 'filename', 'state'];

    protected $with = ['latestStateHistory'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function stateHistory(): HasMany
    {
        return $this->hasMany(StateHistory::class);
    }

    public function latestStateHistory(): HasOne
    {
        return $this->hasOne(StateHistory::class)->latestOfMany('last_seen');
    }

    public function latestStateHistoryBefore($date)
    {
        return $this->hasOne(StateHistory::class)
            ->where('last_seen', '<=', $date)
            ->latest('last_seen');
    }

    public function getClusterableCoordinate(): Coordinate
    {
        return new Coordinate([
            $this->lat,
            $this->lon,
        ]);
    }

    #[Scope]
    protected function olderThanTenMinutes(Builder $query): void
    {
        $query->where('created_at', '<=', now()->subMinutes(10));
    }

    #[Scope]
    protected function withinBounds(Builder $query, Bounds $bounds): void
    {
        $query->whereBetween('lat', [$bounds->minLat, $bounds->maxLat])
            ->whereBetween('lon', [$bounds->minLon, $bounds->maxLon]);
    }

    protected $casts = [
        'state' => State::class,
    ];
}
