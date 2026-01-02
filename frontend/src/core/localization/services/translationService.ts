/**
 * ============================================================================
 * TRANSLATION SERVICE - API Integration for i18n
 * ============================================================================
 *
 * This service is the SINGLE SOURCE OF TRUTH for all translation data.
 * It handles:
 * - API calls to backend Laravel endpoints
 * - In-memory caching (fast, cleared on reload)
 * - localStorage caching (persistent, fallback when API fails)
 * - Error handling and retry logic
 *
 * ARCHITECTURE:
 * Component → Store → Service → API Client → Backend
 *
 * CACHING STRATEGY:
 * 1. Check in-memory cache (instant)
 * 2. Check localStorage cache (fast)
 * 3. Fetch from API (network)
 * 4. Save to both caches
 *
 * WHY SINGLETON:
 * - Shared cache across entire app
 * - Consistent state
 * - No duplicate API calls
 */

import { apiClient } from '@/core/api/apiClient'
import type {
  SupportedLocale,
  LocaleInfo,
  TranslationMessages,
  LocaleCache,
  LocalesResponse,
  TranslationsResponse,
  UserLocaleResponse,
  UpdateLocaleRequest,
} from '../types/locale.types'

// Translation Service Class
class TranslationService {
  // In-memory cache - PRIMARY cache
  private cache: LocaleCache = {}

  // localStorage key for caching
  private readonly STORAGE_KEY = 'cbapp_translations_cache'

  /**
   * Cache version for invalidation
   *
   * PURPOSE: When we change translation structure, increment this
   * to invalidate old caches automatically.
   *
   * EXAMPLE: Change from v1 to v2 when adding new translation fields
   */
  private readonly CACHE_VERSION = 'v1'

  // Fetch available locales from backend
  async fetchAvailableLocales(): Promise<LocaleInfo[]> {
    try {
      const response = await apiClient.get<LocalesResponse>('/translations/locales')
      return response.data.locales
    } catch (error) {
      return this.getFallbackLocales()
    }
  }

  // Fetch all translations for a locale
  async fetchAllTranslations(locale: SupportedLocale): Promise<TranslationMessages> {
    try {
      const response = await apiClient.get<TranslationsResponse>(`/translations/${locale}`)

      // Initialize cache for this locale if needed
      if (!this.cache[locale]) {
        this.cache[locale] = {}
      }

      // Store translations in cache
      this.cache[locale] = response.data.translations
      this.saveToLocalStorage(locale)

      return response.data.translations
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error'
      console.error(
        `[TranslationService] Failed to fetch translations for ${locale}:`,
        errorMessage
      )

      // Try localStorage fallback
      const cached = this.loadFromLocalStorage(locale)
      if (cached) {
        return cached
      }

      // Log critical error - no translations available
      console.error(
        `[TranslationService] CRITICAL: No translations available for ${locale}. ` +
          `API failed and no cache found.`
      )

      return {}
    }
  }

  // Fetch specific namespace translations
  async fetchNamespace(locale: SupportedLocale, namespace: string): Promise<TranslationMessages> {
    try {
      const response = await apiClient.get<TranslationsResponse>(
        `/translations/${locale}/${namespace}`
      )

      // Initialize locale cache if needed
      if (!this.cache[locale]) {
        this.cache[locale] = {}
      }

      // Store namespace in cache
      this.cache[locale][namespace] = response.data.translations
      this.saveToLocalStorage(locale)
      return response.data.translations

    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error'
      console.error(
        `[TranslationService] Failed to fetch namespace ${namespace} for ${locale}:`,
        errorMessage
      )

      // Try in-memory cache first
      if (this.cache[locale]?.[namespace]) {       
        return this.cache[locale][namespace]
      }

      // Try localStorage
      const cached = this.loadFromLocalStorage(locale)
      if (cached?.[namespace]) {        
        const namespaceData = cached[namespace]
        // Ensure we return TranslationMessages object, not string
        return typeof namespaceData === 'object' && namespaceData !== null ? namespaceData : {}
      }

      console.warn(
        `[TranslationService] No cache available for namespace ${namespace} in ${locale}`
      )

      return {}
    }
  }

  // Get user's current locale preference
  async getUserLocale(): Promise<SupportedLocale> {
    try {
      const response = await apiClient.get<UserLocaleResponse>('/user/locale')
      return response.data.preferred_locale
    } catch (error) {
      // Use fallback locale from environment (same as localeStore)
      return (import.meta.env.FALLBACK_LOCALE || 'de') as SupportedLocale
    }
  }

  // Update user's locale preference on backend
  async updateUserLocale(locale: SupportedLocale): Promise<boolean> {
    try {
      const payload: UpdateLocaleRequest = { locale }
      await apiClient.put('/user/locale', payload)
      return true
    } catch (error) {
      console.error('[TranslationService] Failed to update user locale:', error)
      return false
    }
  }

  // Get cached namespace from in-memory cache
  getCachedNamespace(locale: SupportedLocale, namespace: string): TranslationMessages | null {
    return this.cache[locale]?.[namespace] || null
  }

  // Get all cached translations for a locale
  getCachedLocale(locale: SupportedLocale): TranslationMessages {
    return this.cache[locale] || {}
  }

  // Check if namespace is already loaded in cache
  // USE CASE: Avoid duplicate API calls
  isNamespaceLoaded(locale: SupportedLocale, namespace: string): boolean {
    return !!this.cache[locale]?.[namespace]
  }

  /**
   * Clear cache (in-memory and localStorage)
   *
   * WHEN TO USE:
   * - User logout
   * - Force refresh translations
   * - Switch tenant (if translations are tenant-specific)
   *
   * PARAMETERS:
   * - locale: Clear specific locale only
   * - undefined: Clear ALL locales
   */
  clearCache(locale?: SupportedLocale): void {
    if (locale) {
      // Clear specific locale
      delete this.cache[locale]
      localStorage.removeItem(`${this.STORAGE_KEY}_${locale}`)
    } else {
      // Clear all locales
      this.cache = {}

      // Clear all locale caches from localStorage
      Object.keys(localStorage).forEach((key) => {
        if (key.startsWith(this.STORAGE_KEY)) {
          localStorage.removeItem(key)
        }
      })
    }
  }

  // Save translations to localStorage
  private saveToLocalStorage(locale: SupportedLocale): void {
    try {
      const data = {
        version: this.CACHE_VERSION,
        timestamp: Date.now(),
        translations: this.cache[locale],
      }

      localStorage.setItem(`${this.STORAGE_KEY}_${locale}`, JSON.stringify(data))
    } catch (error) {
      console.warn('[TranslationService] Failed to save to localStorage:', error)
    }
  }

  // Load translations from localStorage   
  private loadFromLocalStorage(locale: SupportedLocale): TranslationMessages | null {
    try {
      const stored = localStorage.getItem(`${this.STORAGE_KEY}_${locale}`)
      if (!stored) return null

      const data = JSON.parse(stored)

      // Validate version - invalidate if mismatch
      if (data.version !== this.CACHE_VERSION) {
        localStorage.removeItem(`${this.STORAGE_KEY}_${locale}`)
        return null
      }

      // Check if cache is too old (7 days = 604800000ms)
      const MAX_AGE = 7 * 24 * 60 * 60 * 1000
      if (Date.now() - data.timestamp > MAX_AGE) {
        localStorage.removeItem(`${this.STORAGE_KEY}_${locale}`)
        return null
      }

      // Valid cache - restore to in-memory cache
      this.cache[locale] = data.translations
      return data.translations
    } catch (error) {
      console.warn('[TranslationService] Failed to load from localStorage:', error)
      return null
    }
  }

  // Fallback locales if API completely fails
  private getFallbackLocales(): LocaleInfo[] {
    return [
      { code: 'de', name: 'German', native: 'Deutsch', flag: 'de' },
      { code: 'en', name: 'English', native: 'English', flag: 'gb' },
    ]
  }
}

/**
 * Singleton instance - exported for use across the app
 *
 * USAGE:
 * import { translationService } from '@/core/localization/services/translationService'
 * const translations = await translationService.fetchNamespace('de', 'admin.tenants')
 */
export const translationService = new TranslationService()
