<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Core\Localization\Enums\SupportedLocale;

/**
 * Simple middleware to set locale based on user preference
 * Optional - only if you want automatic locale setting
 */
class SetLocaleMiddleware
{
    /**
     * Handle an incoming request and set appropriate locale
     * Priority: URL parameter -> User preference -> Default
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->determineLocale($request);
        
        if ($locale && SupportedLocale::isSupported($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Determine which locale to use
     * Simple logic: URL param -> User preference -> Default
     */
    private function determineLocale(Request $request): string
    {
        // 1. Check URL parameter (?locale=de)
        $urlLocale = $request->get('locale');
        if ($urlLocale && SupportedLocale::isSupported($urlLocale)) {
            return $urlLocale;
        }

        // 2. Check authenticated user preference
        $user = $request->user();
        if ($user && method_exists($user, 'getPreferredLocale')) {
            $userLocale = $user->getPreferredLocale();
            if ($userLocale && SupportedLocale::isSupported($userLocale)) {
                return $userLocale;
            }
        }

        // 3. Accept-Language header (browser setting)
        $headerLocale = $this->parseAcceptLanguageHeader($request);
        if ($headerLocale && SupportedLocale::isSupported($headerLocale)) {
            return $headerLocale;
        }

        // 4. Fall back to default
        return SupportedLocale::getDefault()->value;
    }

    /**
     * Parse Accept-Language header from browser
     */
    private function parseAcceptLanguageHeader(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        if (!$acceptLanguage) return null;

        // Parse: "en-US,en;q=0.9,de;q=0.8" 
        foreach (explode(',', $acceptLanguage) as $lang) {
            $locale = substr(trim(explode(';', $lang)[0]), 0, 2); // Get 'en' from 'en-US'
            if (SupportedLocale::isSupported($locale)) {
                return $locale;
            }
        }

        return null;
    }
}