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
 */

import { apiClient } from '@/core/api/apiClient'
import type {
  SupportedLocale,
  LocaleInfo,
  TranslationMessages,
  LocaleCache,
  NamespaceCache,
  LocalesResponse,
  TranslationsResponse,
  UserLocaleResponse,
  UpdateLocaleRequest,
} from '../types/locale.types'

// Translation Service Class
class TranslationService {

  private availableLocalesCache: LocaleInfo[] | null = null
  private readonly LOCALES_STORAGE_KEY = 'cb_available_locales'
  private cache: LocaleCache = {}
  private readonly STORAGE_KEY = 'cb_translations_cache'

  // Cache version for invalidation
  // PURPOSE: When we change translation structure, increment this to invalidate old caches automatically.
  private readonly CACHE_VERSION = 'v2'

  // Get available locales with smart caching
  async getAvailableLocales(): Promise<LocaleInfo[]> {

    // 1. Check in-memory cache first (fastest)
    if (this.availableLocalesCache) {
      return this.availableLocalesCache
    }

    // 2. Try localStorage cache (persistent across sessions)
    const cachedLocales = this.loadLocalesFromStorage()

    if (cachedLocales) {
      const hasTranslationData = cachedLocales.some(locale => {
        const translationCache = this.loadFromLocalStorage(locale.code as SupportedLocale, true)
        return translationCache && Object.keys(translationCache).length > 0
      })

      if (hasTranslationData) {
        this.availableLocalesCache = cachedLocales
        return cachedLocales
      } else {
        // Clear invalid locale cache
        localStorage.removeItem(this.LOCALES_STORAGE_KEY)
      }
    }

    // 3. Fetch from API (fallback)
    return await this.fetchAvailableLocales()
  }

  // Fetch available locales from backend (internal method)
  private async fetchAvailableLocales(): Promise<LocaleInfo[]> {
    try {
      const response = await apiClient.get<LocalesResponse>('/translations/locales')
      const locales = response.data.locales

      // Cache in both memory and localStorage
      this.availableLocalesCache = locales
      this.saveLocalesToStorage(locales)

      return locales
    } catch (error) {
      console.warn('[TranslationService] API failed, using fallback locales')
      const fallback = this.getFallbackLocales()

      // Cache fallback to avoid repeated API failures
      this.availableLocalesCache = fallback
      this.saveLocalesToStorage(fallback)

      return fallback
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

      // Load existing cache from localStorage first
      // This prevents overwriting previously cached namespaces
      if (!this.cache[locale]) {
        const existingCache = this.loadFromLocalStorage(locale)
        this.cache[locale] = (existingCache as NamespaceCache) || {}
      }

      // Store namespace in cache (merges with existing)
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
      return (import.meta.env.VITE_FALLBACK_LOCALE || 'de') as SupportedLocale
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
    
    // First check in-memory cache (fastest)
    if (this.cache[locale]) {
      return this.cache[locale]
    }

    // If not in memory, try localStorage (persistent)
    const cached = this.loadFromLocalStorage(locale)
    if (cached) {
      return cached
    }

    // No cache available
    return {}
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
      this.availableLocalesCache = null
      localStorage.removeItem(this.LOCALES_STORAGE_KEY)

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
  // @param validateOnly - If true, only validates cache exists without restoring to in-memory cache
  private loadFromLocalStorage(locale: SupportedLocale, validateOnly: boolean = false): TranslationMessages | null {

    try {
      const storageKey = `${this.STORAGE_KEY}_${locale}`

      const stored = localStorage.getItem(storageKey)

      if (!stored) {        
        return null
      }

      const data = JSON.parse(stored)

      // Validate version - invalidate if mismatch
      if (data.version !== this.CACHE_VERSION) {       
        localStorage.removeItem(storageKey)
        return null
      }

      // Check if cache is too old (7 days = 604800000ms)
      const MAX_AGE = 7 * 24 * 60 * 60 * 1000
      const age = Date.now() - data.timestamp
      if (age > MAX_AGE) {        
        localStorage.removeItem(storageKey)
        return null
      }

      // Only restore to in-memory cache if NOT validating
      // During validation, we just check existence without overwriting in-memory cache
      if (!validateOnly) {
        this.cache[locale] = data.translations
      }

      return data.translations
    } catch (error) {
      if (!validateOnly) {
        console.error(`[TranslationService] Error loading from localStorage for ${locale}:`, error)
      }
      return null
    }
  }

  // Save available locales to localStorage
  private saveLocalesToStorage(locales: LocaleInfo[]): void {
    try {
      const data = {
        version: this.CACHE_VERSION,
        timestamp: Date.now(),
        locales: locales,
      }
      localStorage.setItem(this.LOCALES_STORAGE_KEY, JSON.stringify(data))
    } catch (error) {
      console.warn('[TranslationService] Failed to save locales to localStorage:', error)
    }
  }

  // Load available locales from localStorage
  private loadLocalesFromStorage(): LocaleInfo[] | null {
    try {
      const stored = localStorage.getItem(this.LOCALES_STORAGE_KEY)
      if (!stored) return null

      const data = JSON.parse(stored)

      // Validate version
      if (data.version !== this.CACHE_VERSION) {
        localStorage.removeItem(this.LOCALES_STORAGE_KEY)
        return null
      }

      // Check if cache is too old (24 hours = 86400000ms)
      // Locales change very rarely, so longer cache is acceptable
      const MAX_AGE = 24 * 60 * 60 * 1000
      if (Date.now() - data.timestamp > MAX_AGE) {
        localStorage.removeItem(this.LOCALES_STORAGE_KEY)
        return null
      }

      return data.locales
    } catch (error) {
      console.warn('[TranslationService] Failed to load locales from localStorage:', error)
      return null
    }
  }

  // Force refresh available locales (for admin use)
  async refreshAvailableLocales(): Promise<LocaleInfo[]> {
    this.availableLocalesCache = null
    localStorage.removeItem(this.LOCALES_STORAGE_KEY)
    return await this.fetchAvailableLocales()
  }

  // Fallback locales if API completely fails
  private getFallbackLocales(): LocaleInfo[] {
    return [
      { code: 'de', name: 'German', native: 'Deutsch', flag: 'de' },
      { code: 'en', name: 'English', native: 'English', flag: 'gb' },
    ]
  }
}

// Singleton instance - exported for use across the app
export const translationService = new TranslationService()
