<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'auth.login')
        ->name('login');
});

Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
