<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sticker extends Model
{
    use HasUlids;

    protected $fillable = ['lat', 'lon', 'last_seen', 'filename', 'state'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected $casts = [
        // 'last_seen' => 'date:YY-MM-DD',
    ];
}
