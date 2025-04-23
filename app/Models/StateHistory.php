<?php

namespace App\Models;

use App\State;
use Illuminate\Database\Eloquent\Model;

class StateHistory extends Model
{
    protected $fillable = ['sticker_id', 'state', 'last_seen'];

    protected $casts = [
        'state' => State::class,
    ];
}
