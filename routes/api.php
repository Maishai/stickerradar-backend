<?php

use App\Http\Controllers\StickerApiController;
use App\Http\Controllers\TagApiController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->middleware(['throttle:api'])->group(function () {
    Route::resource('stickers', StickerApiController::class)->only(['index', 'show']);
    Route::post('stickers', [StickerApiController::class, 'store'])->middleware(['throttle:sticker-upload'])->name('api.stickers.store');
    Route::get('tags/tree', [TagApiController::class, 'tree'])->name('tags.tree');
    Route::resource('tags', TagApiController::class)->only(['index', 'show']);
});
