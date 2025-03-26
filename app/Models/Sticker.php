<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sticker extends Model
{
    protected $fillable = ['lat','lon','last_seen'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
