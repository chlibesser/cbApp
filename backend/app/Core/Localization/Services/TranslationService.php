<?php

namespace App\Core\Localization\Services;

use App\Core\Localization\Enums\SupportedLocale;
use App\Core\Localization\Exceptions\UnsupportedLocaleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Translation service using hybrid enum approach
 * Type-safe operations with configurable display data
 */
class TranslationService
{
    /**
     * Get all available locales (uses enum with config data)
     * Returns formatted data for API responses
     */
    public function getAvailableLocales(): array
    {
        return SupportedLocale::getAvailable();
    }

    /**
     * Load all translations for a locale
     * Uses enum for type-safe validation
     */
    public function getAllTranslations(string $locale): array
    {
        $this->validateLocale($locale);
        
        $cacheKey = $this->getCacheKey($locale, 'all');
        
        if ($this->isCacheEnabled()) {
            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($locale) {
                return $this->loadAllTranslationsFromFiles($locale);
            });
        }
        
        return $this->loadAllTranslationsFromFiles($locale);
    }

    /**
     * Load specific namespace translations
     * Example: getNamespaceTranslations('de', 'admin.tenants')
     */
    public function getNamespaceTranslations(string $locale, string $namespace): array
    {
        $this->validateLocale($locale);
        
        $cacheKey = $this->getCacheKey($locale, $namespace);
        
        if ($this->isCacheEnabled()) {
            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($locale, $namespace) {
                return $this->loadNamespaceFromFiles($locale, $namespace);
            });
        }
        
        return $this->loadNamespaceFromFiles($locale, $namespace);
    }

    /**
     * Validate locale using enum (type-safe)
     * Throws exception if locale not supported by enum
     */
    public function validateLocale(string $locale): bool
    {
        if (!SupportedLocale::isSupported($locale)) {
            throw UnsupportedLocaleException::locale($locale);
        }
        return true;
    }

    /**
     * Get default locale code from enum
     * Type-safe access to default locale
     */
    public function getDefaultLocale(): string
    {
        return SupportedLocale::getDefault()->value;
    }

    /**
     * Clear translation cache
     * Simple cache management for development
     */
    public function clearCache(?string $locale = null): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }
        
        // Simple approach - clear all cache
        // Could be enhanced to clear only translation-related cache
        Cache::flush();
    }

    /**
     * Load all translation files for a locale from filesystem
     * Scans lang/{locale}/ directory recursively
     */
    private function loadAllTranslationsFromFiles(string $locale): array
    {
        $translations = [];
        $langPath = base_path("lang/{$locale}");
        
        if (!File::exists($langPath)) {
            return [];
        }

        $files = File::allFiles($langPath);
        
        foreach ($files as $file) {
            // Convert file path to dot notation
            // admin/tenants.php becomes admin.tenants (handle both / and \ path separators)
            $relativePath = $file->getRelativePathname();
            $key = str_replace(['/', '\\', '.php'], ['.', '.', ''], $relativePath);
            
            // Skip excluded files - check multiple formats
            if ($this->isExcluded($key, $relativePath, $file->getFilename())) {
                continue;
            }
            
            $fileContent = include $file->getPathname();
            if (is_array($fileContent)) {
                $translations[$key] = $fileContent;
            }
        }

        return $translations;
    }

    /**
     * Load single namespace file
     * Converts admin.tenants to lang/{locale}/admin/tenants.php
     */
    private function loadNamespaceFromFiles(string $locale, string $namespace): array
    {
        $filePath = base_path("lang/{$locale}/" . str_replace('.', '/', $namespace) . '.php');
        
        if (!File::exists($filePath)) {
            return [];
        }

        $content = include $filePath;
        return is_array($content) ? $content : [];
    }

    /**
     * Generate cache key for translations
     * Format: {prefix}.{locale}.{namespace}
     */
    private function getCacheKey(string $locale, string $namespace): string
    {
        return $this->getCachePrefix() . '.' . $locale . '.' . $namespace;
    }

    /**
     * Get list of files to exclude from API responses
     */
    private function getExcludedFiles(): array
    {
        return config('localization.excluded_files', []);
    }

    /**
     * Check if a translation key should be excluded
     * Supports multiple exclusion formats:
     * - Dot notation: "auth.login"
     * - File path: "auth/login.php" or "auth\\login.php" 
     * - Filename only: "login.php"
     * - Directory: "auth/*" (excludes all files in auth folder)
     */
    private function isExcluded(string $key, string $relativePath, string $filename): bool
    {
        $excludedFiles = $this->getExcludedFiles();
        
        foreach ($excludedFiles as $pattern) {
            // 1. Direct dot notation match: "auth.login"
            if ($pattern === $key) {
                return true;
            }
            
            // 2. File path match: "auth/login.php"
            $normalizedPath = str_replace('\\', '/', $relativePath);
            if ($pattern === $normalizedPath) {
                return true;
            }
            
            // 3. Filename only match: "login.php"
            if ($pattern === $filename) {
                return true;
            }
            
            // 4. Directory wildcard: "auth/*" excludes all files in auth folder
            if (str_ends_with($pattern, '/*')) {
                $dirPattern = str_replace('/*', '', $pattern);
                if (str_starts_with($key, $dirPattern . '.') || str_starts_with($normalizedPath, $dirPattern . '/')) {
                    return true;
                }
            }
            
            // 5. Wildcard pattern: "*.debug" excludes all files ending with .debug
            if (str_contains($pattern, '*')) {
                // Properly escape the pattern for regex, then replace * with .*
                $escapedPattern = preg_quote($pattern, '/');
                $regexPattern = str_replace('\\*', '.*', $escapedPattern);
                
                if (preg_match('/^' . $regexPattern . '$/', $key) || preg_match('/^' . $regexPattern . '$/', $filename)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get cache configuration values
     */
    private function getCachePrefix(): string
    {
        return config('localization.cache.prefix', 'translations');
    }
    
    private function getCacheTtl(): int
    {
        return config('localization.cache.ttl', 86400);
    }
    
    private function isCacheEnabled(): bool
    {
        return config('localization.cache.enabled', true);
    }
}