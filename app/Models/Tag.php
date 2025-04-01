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

    public static function buildTrees()
    {
        $tags = Tag::all();
        return $tags->whereNull('super_tag')->map(function ($tag) use ($tags) {
            return Tag::buildTree($tags, $tag);
        })->values();
    }

    private static function buildTree($tags, $tag)
    {
        return [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color ?? 'steelblue',
            'children' => $tags->where('super_tag', $tag->id)->map(fn($child) => Tag::buildTree($tags, $child))->values(),
        ];
    }
}
