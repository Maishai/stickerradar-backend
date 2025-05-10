<?php

use App\Http\Controllers\ClusterApiController;
use App\Http\Controllers\HistoryApiController;
use App\Http\Controllers\StickerApiController;
use App\Http\Controllers\TagApiController;
use App\Http\Middleware\EnsureApiKeyIsValid;
use Illuminate\Support\Facades\Route;

Route::name('api.')->middleware(['throttle:api'])->group(function () {
    Route::name('stickers.')->prefix('stickers')->group(function () {
        Route::post('cluster', [ClusterApiController::class, 'cluster'])->name('cluster');
        Route::name('history.')->group(function () {
            Route::post('{sticker}/history', [HistoryApiController::class, 'update'])->name('update');
            Route::get('{sticker}/history', [HistoryApiController::class, 'show'])->name('show');
        });
        Route::post('', [StickerApiController::class, 'store'])
            ->middleware(['throttle:sticker-upload', EnsureApiKeyIsValid::class])
            ->name('store');
        Route::get('', [StickerApiController::class, 'index'])->name('index');
        Route::put('{sticker}', [StickerApiController::class, 'update'])->middleware(['throttle:sticker-update-tags'])->name('update');
    });
    Route::get('tags/tree', [TagApiController::class, 'tree'])->name('tags.tree');
    Route::resource('tags', TagApiController::class)->only(['index', 'show']);
});
