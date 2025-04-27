<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // OBACHT: this will let requests without X-API-KEY through! Only validates if its set!
        if ($key = $request->header('X-API-KEY')) {
            if (! env('API_KEY') === $key) {
                abort(Response::HTTP_UNAUTHORIZED, 'Invalid API key.');
            }
        }

        return $next($request);
    }
}
