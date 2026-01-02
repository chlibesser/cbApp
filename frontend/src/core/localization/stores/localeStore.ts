/**
 * ============================================================================
 * LOCALE STORE - Pinia State Management for i18n
 * ============================================================================
 *
 * This store is the CENTRAL STATE MANAGER for all locale/translation state.
 * It coordinates between:
 * - TranslationService (API calls & caching)
 * - Vue I18n (template translations)
 * - Vuetify (UI component localization)
 * - Components (reactive locale state)
 *
 * RESPONSIBILITIES:
 * - Manage current locale state
 * - Track loaded namespaces
 * - Handle locale switching
 * - Lazy load namespaces
 * - Provide loading/error states
 * - Show user feedback (toasts)
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { SupportedLocale, LocaleInfo, TranslationMessages } from '../types/locale.types'
import { translationService } from '../services/translationService'
import { i18n } from '../plugins/i18n'
import { syncVuetifyLocale } from '@/infrastructure/plugins/vuetify'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'

/**
 * Locale Store Definition
 */
export const useLocaleStore = defineStore('locale', () => {
  // STATE
  // Current active locale, DEFAULT: 'de' (German)
  const defaultLocale = (import.meta.env.DEFAULT_LOCALE || 'de') as SupportedLocale
  const currentLocale = ref<SupportedLocale>(defaultLocale)
  
  // Fallback locale when translation missing
  const fallbackLocaleValue = (import.meta.env.FALLBACK_LOCALE || 'de') as SupportedLocale
  const fallbackLocale = ref<SupportedLocale>(fallbackLocaleValue)

  // Available locales from backend
  const availableLocales = ref<LocaleInfo[]>([])

  // Loaded namespaces tracking
  const loadedNamespaces = ref<Set<string>>(new Set())

  // Loading state
  const loading = ref(false)

  // Error state
  const error = ref<string | null>(null)

  // Initialization
  const initialized = ref(false)

  // GETTERS
  // Get full info about current locale
  const currentLocaleInfo = computed(() =>
    availableLocales.value.find(l => l.code === currentLocale.value)
  )

  // Check if a namespace is loaded
  const isNamespaceLoaded = computed(() => (namespace: string) =>
    loadedNamespaces.value.has(`${currentLocale.value}:${namespace}`)
  )
  
  // HELPER FUNCTIONS
  // Transform backend translations to vue-i18n format
  // Split namespace keys by dot (e.g., "admin.accounts" → ["admin", "accounts"])
  function transformTranslations(backendTranslations: TranslationMessages): TranslationMessages {
    const result: any = {}

    for (const [namespace, translations] of Object.entries(backendTranslations)) {
      // Split namespace by dots: "admin.accounts" → ["admin", "accounts"]
      const parts = namespace.split('.')

      // Navigate/create nested structure
      let current = result
      for (let i = 0; i < parts.length - 1; i++) {
        const part = parts[i]
        if (!current[part]) {
          current[part] = {}
        }
        current = current[part]
      }

      // Set translations at final level
      const lastPart = parts[parts.length - 1]
      current[lastPart] = translations
    }

    return result
  }

  // Show toast notification
  function showToast(message: string, type: 'success' | 'error' | 'warning') {
    try {

      // Lazy load layoutStore only when needed
      const layoutStore = useLayoutStore()

      // Use the appropriate convenience method based on type
      if (type === 'success') {
        layoutStore.showSuccess(message, { timeout: 5000 })
      } else if (type === 'error') {
        layoutStore.showError(message, { timeout: 5000, persistent: false })
      } else if (type === 'warning') {
        layoutStore.showWarning(message, { timeout: 5000 })
      }
    } catch (error) {
      // Fallback: If layoutStore fails, log to console
      console.warn('[LocaleStore] Failed to show toast:', message, error)
    }
  }

  // ACTIONS
  // Initialize the i18n system
  /* Initialize the i18n system
   * WHEN TO CALL: Once on app startup (main.ts)
   *
   * PROCESS:
   * 1. Load available locales from backend
   * 2. Try to load from localStorage cache (instant render)
   * 3. Fetch fresh translations from API (background update)
   * 4. Set locale in vue-i18n
   * 5. Sync with Vuetify
   */
  async function initialize(): Promise<void> {
    // Prevent multiple initializations
    if (initialized.value) {
      return
    }

    loading.value = true
    error.value = null

    try {

      // Step 1: Load available locales
      const locales = await translationService.fetchAvailableLocales()
      availableLocales.value = locales

      // Step 2: Try localStorage cache first for instant render
      const cachedTranslations = translationService.getCachedLocale(currentLocale.value)
      if (Object.keys(cachedTranslations).length > 0) {
        const transformedCache = transformTranslations(cachedTranslations)
        i18n.global.setLocaleMessage(currentLocale.value, transformedCache)
      }

      // Step 3: Fetch fresh translations in background
      const translations = await translationService.fetchAllTranslations(currentLocale.value)

      // Check if we got new data vs cached
      // const hasNewData = JSON.stringify(translations) !== JSON.stringify(cachedTranslations)

      // Step 4: Transform and update vue-i18n
      const transformedTranslations = transformTranslations(translations)
      i18n.global.setLocaleMessage(currentLocale.value, transformedTranslations)
      i18n.global.locale.value = currentLocale.value

      // Debug: Check if translations are actually set
      // const setMessages = i18n.global.getLocaleMessage(currentLocale.value)
      // console.log('[LocaleStore] Translations after set:', Object.keys(setMessages))
      // console.log('[LocaleStore] Test translation:', i18n.global.t('admin.accounts.page_title'))

      // Step 5: Sync with Vuetify
      syncVuetifyLocale(currentLocale.value)

      initialized.value = true
      // Log cache status
      // if (Object.keys(cachedTranslations).length > 0 && !hasNewData) {
      //   console.log('[LocaleStore] Using cached translations (API returned same data)')
      // }
    } catch (err) {
      error.value = 'Failed to initialize translations'
      console.error('[LocaleStore] Initialization failed:', err)

      // Show error toast
      showToast('Failed to load translations. Using cached version if available.', 'warning')

      // Check if we have ANY cached data to work with
      const cachedTranslations = translationService.getCachedLocale(currentLocale.value)
      if (Object.keys(cachedTranslations).length > 0) {
        i18n.global.setLocaleMessage(currentLocale.value, cachedTranslations)
        i18n.global.locale.value = currentLocale.value
        syncVuetifyLocale(currentLocale.value)
        initialized.value = true // Mark as initialized even with cache
        showToast('Using cached translations', 'success')
      } else {
        // No cache available - this is critical but not fatal
        showToast('No translations available. Please check your connection.', 'error')
      }
    } finally {
      loading.value = false
    }
  }

  // Change current locale
  async function setLocale(locale: SupportedLocale): Promise<void> {
    // Skip if already current locale
    if (locale === currentLocale.value) {
      return
    }

    loading.value = true
    error.value = null

    try {

      // Step 1: Check cache first
      let translations = translationService.getCachedLocale(locale)
      const usingCache = Object.keys(translations).length > 0

      // Step 2: If not cached, fetch from API
      if (!usingCache) {
        translations = await translationService.fetchAllTranslations(locale)
      }

      // Step 3: Transform and update vue-i18n
      const transformedTranslations = transformTranslations(translations)
      i18n.global.setLocaleMessage(locale, transformedTranslations)
      i18n.global.locale.value = locale

      // Step 4: Update Vuetify
      syncVuetifyLocale(locale)

      // Step 5: Update backend (async, don't block UI)
      // Only try if user is authenticated (has auth token)
      const hasAuthToken = localStorage.getItem('auth_token')
      if (hasAuthToken) {
        translationService.updateUserLocale(locale).catch(err => {
          // Only show warning if user is authenticated but update failed
          // (401 errors when not authenticated are expected and ignored)
          if (err.status !== 401) {
            console.warn('[LocaleStore] Failed to update backend locale:', err)
            showToast('Language changed locally (not saved to server)', 'warning')
          } else {
            console.log('[LocaleStore] User not authenticated, locale not saved to server')
          }
        })
      } else {
        console.log('[LocaleStore] No auth token, skipping backend locale update')
      }

      // Step 6: Update state
      currentLocale.value = locale
      loadedNamespaces.value.clear() // Clear namespace tracking

      // Step 7: Success toast
      const localeInfo = availableLocales.value.find(l => l.code === locale)
      showToast(`Language changed to ${localeInfo?.native || locale}`, 'success')

    } catch (err) {
      error.value = `Failed to switch to ${locale}`
      console.error('[LocaleStore] Failed to set locale:', err)

      showToast(`Failed to switch language to ${locale}`, 'error')
    } finally {
      loading.value = false
    }
  }

  // Load specific namespace
  async function loadNamespace(namespace: string, retryCount = 0): Promise<void> {
    const namespaceKey = `${currentLocale.value}:${namespace}`

    // Skip if already loaded
    if (loadedNamespaces.value.has(namespaceKey)) {
      return
    }

    const MAX_RETRIES = 2
    const RETRY_DELAY = 1000 // 1 second base delay

    try {
      console.log('[LocaleStore] Loading namespace:', namespace)

      const translations = await translationService.fetchNamespace(currentLocale.value, namespace)

      // Merge into existing messages (preserve other namespaces)
      const currentMessages = i18n.global.getLocaleMessage(currentLocale.value)
      i18n.global.setLocaleMessage(currentLocale.value, {
        ...currentMessages,
        [namespace]: translations,
      })

      // Mark as loaded
      loadedNamespaces.value.add(namespaceKey)
      console.log('[LocaleStore] Namespace loaded successfully:', namespace)
    } catch (err) {
      console.error(`[LocaleStore] Failed to load namespace ${namespace}:`, err)

      // Retry logic with exponential backoff
      if (retryCount < MAX_RETRIES) {
        const delay = RETRY_DELAY * (retryCount + 1) // 1s, 2s
        console.log(
          `[LocaleStore] Retrying namespace ${namespace} in ${delay}ms (${retryCount + 1}/${MAX_RETRIES})`
        )

        await new Promise(resolve => setTimeout(resolve, delay))
        return loadNamespace(namespace, retryCount + 1)
      } else {
        // All retries failed
        console.error(`[LocaleStore] Failed to load namespace ${namespace} after ${MAX_RETRIES} retries`)

        // Only show toast if it's a critical namespace (not common/validation)
        if (!namespace.includes('common') && !namespace.includes('validation')) {
          showToast(`Failed to load translations for ${namespace}`, 'warning')
        }
      }
    }
  }

  // Load multiple namespaces in parallel
  async function loadNamespaces(namespaces: string[]): Promise<void> {
    console.log('[LocaleStore] Loading namespaces:', namespaces.join(', '))
    await Promise.all(namespaces.map(ns => loadNamespace(ns)))
  }

  // Set locale from user auth data
  function setLocaleFromUser(locale: SupportedLocale): void {
    console.log('[LocaleStore] Setting locale from user preference:', locale)
    currentLocale.value = locale
    i18n.global.locale.value = locale
    syncVuetifyLocale(locale)
  }

  // Clear all caches
  function clearCache(): void {
    console.log('[LocaleStore] Clearing all caches')
    translationService.clearCache()
    loadedNamespaces.value.clear()
    initialized.value = false
  }

  // RETURN (Public API)
  return {
    // State (reactive)
    currentLocale,
    fallbackLocale,
    availableLocales,
    loadedNamespaces,
    loading,
    error,
    initialized,

    // Getters (computed)
    currentLocaleInfo,
    isNamespaceLoaded,

    // Actions (functions)
    initialize,
    setLocale,
    loadNamespace,
    loadNamespaces,
    setLocaleFromUser,
    clearCache,
  }
})
