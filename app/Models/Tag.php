<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'super_tag', 'color'];

    public function superTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'super_tag');
    }

    public function subTags(): HasMany
    {
        return $this->hasMany(Tag::class, 'super_tag');
    }
}
