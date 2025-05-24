<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->prefix('admin')->group(function () {
    Volt::route('login', 'auth.login')
        ->name('login');
});

Route::prefix('admin')->post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
