<?php

use App\Http\Controllers\ClusterApiController;
use App\Http\Controllers\StickerApiController;
use App\Http\Controllers\TagApiController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->middleware(['throttle:api'])->group(function () {
    Route::name('stickers.')->prefix('stickers')->group(function () {
        Route::name('clusters.')->prefix('clusters')->group(function () {
            Route::get('', [ClusterApiController::class, 'index'])->name('index');
            Route::get('{tag}', [ClusterApiController::class, 'show'])->name('show');
            Route::post('', [ClusterApiController::class, 'showMultiple'])->name('showMultiple');
        });
        Route::post('', [StickerApiController::class, 'store'])->middleware(['throttle:sticker-upload'])->name('store');
        Route::get('', [StickerApiController::class, 'index'])->name('index');
        Route::get('{sticker}', [StickerApiController::class, 'show'])->name('show');
        Route::put('{sticker}', [StickerApiController::class, 'update'])->middleware(['throttle:sticker-update-tags'])->name('update');
    });
    Route::get('tags/tree', [TagApiController::class, 'tree'])->name('tags.tree');
    Route::resource('tags', TagApiController::class)->only(['index', 'show']);
});
