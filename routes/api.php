<?php

use App\Http\Controllers\StickerApiController;
use Illuminate\Support\Facades\Route;

Route::resource('stickers', StickerApiController::class)->only(['index', 'store', 'show']);
