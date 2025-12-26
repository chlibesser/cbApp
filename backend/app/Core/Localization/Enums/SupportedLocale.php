<?php

namespace App\Core\Localization\Enums;

/**
 * Enum for supported locales with configurable display data
 * Combines type safety (enum cases) with flexibility (config data)
 */
enum SupportedLocale: string
{
    case GERMAN = 'de';     // Type-safe German locale
    case ENGLISH = 'en';    // Type-safe English locale

    /**
     * Get display name from configuration
     * Example: SupportedLocale::GERMAN->getDisplayName() returns 'Deutsch'
     * Falls back to locale code if config missing
     */
    public function getDisplayName(): string
    {
        $config = config("localization.supported_locales.{$this->value}");
        return $config['name'] ?? $this->value;
    }

    /**
     * Get native language name from configuration
     * Example: SupportedLocale::GERMAN->getNativeName() returns 'Deutsch'
     * Falls back to locale code if config missing
     */
    public function getNativeName(): string
    {
        $config = config("localization.supported_locales.{$this->value}");
        return $config['native'] ?? $this->value;
    }

    /**
     * Get flag code from configuration
     * Example: SupportedLocale::ENGLISH->getFlagCode() returns 'gb'
     * Falls back to locale code if config missing
     */
    public function getFlagCode(): string
    {
        $config = config("localization.supported_locales.{$this->value}");
        return $config['flag'] ?? $this->value;
    }

    /**
     * Get all available locales formatted for API responses
     * Returns array with code, name, native, flag for each enum case
     * Combines enum type safety with config flexibility
     */
    public static function getAvailable(): array
    {
        return array_map(fn($locale) => [
            'code' => $locale->value,               // From enum case
            'name' => $locale->getDisplayName(),    // From config
            'native' => $locale->getNativeName(),   // From config
            'flag' => $locale->getFlagCode(),       // From config
        ], self::cases());
    }

    /**
     * Check if a locale code is supported (type-safe)
     * Example: SupportedLocale::isSupported('de') returns true
     * Example: SupportedLocale::isSupported('fr') returns false
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, array_column(self::cases(), 'value'));
    }

    /**
     * Get enum case from locale code
     * Example: SupportedLocale::from('de') returns SupportedLocale::GERMAN
     * Throws ValueError if locale not supported (built-in enum behavior)
     */
    public static function fromCode(string $locale): self
    {
        return self::from($locale); // Built-in enum method     
    }

    /**
     * Try to get enum case from locale code, return null if not supported
     * Example: SupportedLocale::tryFromCode('fr') returns null
     * Safe version that doesn't throw exceptions
     */
    public static function tryFromCode(string $locale): ?self
    {
        return self::tryFrom($locale); // Built-in enum method
    }

    /**
     * Get default locale enum case
     * Returns enum case for configured default locale
     */
    public static function getDefault(): self
    {
        $defaultCode = config('localization.default_locale', 'de');
        return self::from($defaultCode);
    }

    /**
     * Get all supported locale codes as array
     * Example: ['de', 'en']
     * Useful for validation rules, etc.
     */
    public static function getCodes(): array
    {
        return array_column(self::cases(), 'value');
    }
}