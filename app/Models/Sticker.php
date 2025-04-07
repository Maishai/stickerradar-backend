<?php

namespace App\Models;

use App\State;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sticker extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = ['lat', 'lon', 'last_seen', 'filename', 'state'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected $casts = [
        'state' => State::class,
    ];
}
