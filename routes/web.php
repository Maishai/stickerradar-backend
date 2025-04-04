<?php

use App\Livewire\EditSticker;
use App\Livewire\EditTag;
use App\Livewire\ImageUpload;
use App\Livewire\StickerPreview;
use App\Livewire\TagsComponent;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::get('tags', TagsComponent::class)->name('tags.index');
    Route::get('tags/{tag}', EditTag::class)->name('tags.edit');
    Route::get('stickers', StickerPreview::class)->name('stickers.index');
    Route::get('stickers/upload', ImageUpload::class)->name('stickers.upload');
    Route::get('stickers/{sticker}', EditSticker::class)->name('stickers.show');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
