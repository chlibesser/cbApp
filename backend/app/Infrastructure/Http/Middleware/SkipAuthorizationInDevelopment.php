<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class SkipAuthorizationInDevelopment
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // In Development-Modus alle Authorization-Checks umgehen
        if (config('app.skip_authorization', false)) {
            Gate::before(function () {
                return true; // Alle Policies werden übersprungen
            });
        }

        return $next($request);
    }
}