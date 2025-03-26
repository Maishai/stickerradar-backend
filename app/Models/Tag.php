<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tag extends Model
{
    protected $fillable = ['name','super_tag','color'];

    public function superTag(): HasOne
    {
        return $this->hasOne(Tag::class);
    }
}
