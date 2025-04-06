<?php

use App\Http\Controllers\StickerApiController;
use App\Http\Controllers\TagApiController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::resource('stickers', StickerApiController::class)->only(['index', 'store', 'show']);
    Route::get('tags/tree', [TagApiController::class, 'tree'])->name('tags.tree');
    Route::resource('tags', TagApiController::class)->only(['index', 'show']);
});
