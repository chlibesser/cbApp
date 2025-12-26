<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider - Application service provider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register service bindings

        // Register Translation Service as singleton
        // Singleton ensures one instance per request for better performance
        $this->app->singleton(TranslationService::class, function ($app) {
            return new TranslationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bootstrap application services
        // Set application locale from config on boot
        // This ensures consistent locale across the application
        $defaultLocale = config('localization.default_locale', 'de');
        app()->setLocale($defaultLocale);
    }
}