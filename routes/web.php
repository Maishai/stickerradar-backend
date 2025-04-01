<?php

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
    Route::get('tags', TagsComponent::class)->name('tags');
    Route::get('stickers/upload', ImageUpload::class)->name('stickers.upload');
    Route::get('stickers/preview', StickerPreview::class)->name('stickers.preview');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
