<?php

namespace App\Models;

use App\State;
use EmilKlindt\MarkerClusterer\Interfaces\Clusterable;
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
        return $this->hasOne(StateHistory::class)->latestOfMany();
    }

    public function getClusterableCoordinate(): Coordinate
    {
        return new Coordinate([
            $this->lat,
            $this->lon,
        ]);
    }

    protected $casts = [
        'state' => State::class,
    ];
}
