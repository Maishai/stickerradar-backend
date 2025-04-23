<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(env('THROTTLE_API_REQUESTS_PER_MINUTE', 30))->by($request->ip());
        });
        RateLimiter::for('sticker-upload', function (Request $request) {
            return Limit::perMinute(env('THROTTLE_STICKER_UPLOADS_PER_MINUTE', 5))->by($request->ip());
        });
        RateLimiter::for('sticker-update-tags', function (Request $request) {
            return Limit::perMinute(env('THROTTLE_STICKER_UPDATE_TAGS', 1))->by($request->ip());
        });
    }
}
