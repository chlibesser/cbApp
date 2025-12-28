<?php

namespace App\Infrastructure\Http\Controllers;

use App\Core\Localization\Services\TranslationService;
use App\Core\Localization\Exceptions\UnsupportedLocaleException;
use App\Core\Localization\Enums\SupportedLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Infrastructure layer controller for translation endpoints
 * Handles HTTP concerns and delegates to Core domain services
 */
class TranslationController extends Controller
{
    public function __construct(
        private TranslationService $translationService  // Injected Core service
    ) {}

    /**
     * GET /api/translations/locales
     * Get all available locales with their display information
     * Returns data from enum + config combination
     */
    public function getLocales(): JsonResponse
    {
        try {
            $locales = $this->translationService->getAvailableLocales();
            
            return response()->json([
                'success' => true,
                'locales' => $locales,
                'default_locale' => SupportedLocale::getDefault()->value,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load available locales',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/translations/{locale}
     * Get all translations for a specific locale
     * Example: GET /api/translations/de
     */
    public function getAllTranslations(string $locale): JsonResponse
    {
        try {
            $translations = $this->translationService->getAllTranslations($locale);
            
            return response()->json([
                'success' => true,
                'locale' => $locale,
                'translations' => $translations,
                'count' => count($translations),
            ]);
        } catch (UnsupportedLocaleException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unsupported locale',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load translations',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/translations/{locale}/{namespace}
     * Get translations for specific namespace
     * Example: GET /api/translations/de/admin.tenants
     */
    public function getNamespaceTranslations(string $locale, string $namespace): JsonResponse
    {
        try {
            $translations = $this->translationService->getNamespaceTranslations($locale, $namespace);
            
            return response()->json([
                'success' => true,
                'locale' => $locale,
                'namespace' => $namespace,
                'translations' => $translations,
                'empty' => empty($translations),
            ]);
        } catch (UnsupportedLocaleException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unsupported locale',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load namespace translations',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/user/locale
     * Update authenticated user's locale preference
     * Requires authentication
     */
    public function updateUserLocale(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'locale' => 'required|string|size:2',  // Validate 2-character locale code
            ]);

            $account = $request->user();
            $locale = $request->input('locale');

            // Validate locale using enum
            if (!SupportedLocale::isSupported($locale)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unsupported locale',
                    'message' => "Locale '{$locale}' is not supported",
                    'supported_locales' => SupportedLocale::getCodes(),
                ], 400);
            }

            // Check if locale is allowed in user's current tenant (if applicable)
            $tenant = $account->currentTenant ?? null;  // Adjust based on your tenant logic
            if ($tenant && !$tenant->isLocaleAllowed($locale)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Locale not allowed',
                    'message' => "Locale '{$locale}' is not allowed in your organization",
                ], 403);
            }

            // Update user's locale preference
            $success = $account->setPreferredLocale($locale);

            if ($success) {
                // Set locale for current request
                App::setLocale($locale);

                return response()->json([
                    'success' => true,
                    'message' => 'Locale preference updated successfully',
                    'locale' => $locale,
                    'display_name' => SupportedLocale::from($locale)->getDisplayName(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update locale preference',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update user locale',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/user/locale
     * Get authenticated user's current locale information
     * Requires authentication
     */
    public function getUserLocale(Request $request): JsonResponse
    {
        try {
            $account = $request->user();
            $preferredLocale = $account->getPreferredLocale();
            $currentLocale = App::getLocale();

            return response()->json([
                'success' => true,
                'preferred_locale' => $preferredLocale,
                'current_locale' => $currentLocale,
                'display_name' => SupportedLocale::from($preferredLocale)->getDisplayName(),
                'native_name' => SupportedLocale::from($preferredLocale)->getNativeName(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get user locale',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/translations/cache
     * Clear translation cache (development/admin only)
     * Should be protected in production
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $locale = $request->get('locale');  // Optional: clear specific locale
            $this->translationService->clearCache($locale);

            return response()->json([
                'success' => true,
                'message' => $locale ? "Cache cleared for locale '{$locale}'" : 'Translation cache cleared',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}