<?php

namespace App\Core\Localization\Exceptions;

use Exception;
use App\Core\Localization\Enums\SupportedLocale;

/**
 * Exception for unsupported locale operations
 * Uses enum to provide accurate error messages
 */
class UnsupportedLocaleException extends Exception
{
    /**
     * Create exception for unsupported locale
     * Uses enum to get accurate list of supported locales
     */
    public static function locale(string $locale): self
    {
        $supported = implode(', ', SupportedLocale::getCodes());
        return new self("Locale '{$locale}' is not supported. Supported locales: {$supported}");
    }

    /**
     * Create exception for locale not allowed in tenant context
     * When user tries to switch to language their organization doesn't support
     */
    public static function notAllowedInTenant(string $locale, string $tenantName): self
    {
        return new self("Locale '{$locale}' is not allowed in tenant '{$tenantName}'.");
    }
}