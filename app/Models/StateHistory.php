<?php

namespace App\Models;

use App\State;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['sticker_id', 'state', 'last_seen'];

    protected $casts = [
        'state' => State::class,
    ];
}
