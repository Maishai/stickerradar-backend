<?php

namespace App\Models;

use App\State;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StateHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['sticker_id', 'state', 'last_seen'];

    public function sticker(): BelongsTo
    {
        return $this->belongsTo(Sticker::class);
    }

    protected $casts = [
        'state' => State::class,
    ];
}
