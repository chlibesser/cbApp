<?php

/*
|--------------------------------------------------------------------------
| Available Locales
|--------------------------------------------------------------------------
| Here you may specify the locales that your application supports.
| These locales will be used for localization and translation purposes.
| Also includes cache settings for translations.
|--------------------------------------------------------------------------
*/

return [
    // Default locale
    'default_locale' => env('APP_LOCALE', 'de'),

    // Display information for enum cases (configurable part)
    'supported_locales' => [
        'de' => [
            'name' => 'Deutsch',        // Display name for admin UI
            'native' => 'Deutsch',      // Native language name  
            'flag' => 'de',             // Flag icon code
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => 'gb',
        ],
    ],

    // Files to exclude from API responses
    'excluded_files' => [
        'validation',        // Excludes Laravel validation  
        'pagination',        // Excludes Laravel pagination
    ],

    // Cache configuration
    'cache' => [
        'enabled' => env('TRANSLATION_CACHE_ENABLED', true),
        'driver' => env('TRANSLATION_CACHE_DRIVER', 'file'),
        'ttl' => env('TRANSLATION_CACHE_TTL', 86400),        // 24 hours
        'prefix' => env('TRANSLATION_CACHE_PREFIX', 'translations'),
    ],
];
