// LOCALE STORE - Pinia State Management for i18n
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { SupportedLocale, LocaleInfo, TranslationMessages } from '../types/locale.types'
import { translationService } from '../services/translationService'
import { i18n } from '../plugins/i18n'
import { syncVuetifyLocale } from '@/infrastructure/plugins/vuetify'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'

// Locale Store Definition
export const useLocaleStore = defineStore('locale', () => {

  // STATE
  const defaultLocale = (import.meta.env.VITE_DEFAULT_LOCALE || 'de') as SupportedLocale
  const currentLocale = ref<SupportedLocale>(defaultLocale)
  
  // Fallback locale when translation missing
  const fallbackLocaleValue = (import.meta.env.VITE_FALLBACK_LOCALE || 'de') as SupportedLocale
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

    // Sort entries to process longer paths first (auth.login before auth)
    // This prevents empty parent namespaces from overwriting populated children
    const sortedEntries = Object.entries(backendTranslations).sort(([a], [b]) => {
      return b.split('.').length - a.split('.').length
    })
    
    for (const [namespace, translations] of sortedEntries) {
      
      // Skip empty translations to prevent overwriting
      if (!translations || (typeof translations === 'object' && Object.keys(translations).length === 0)) {
        continue
      }

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

      // Set translations at final level (only if not already populated)
      const lastPart = parts[parts.length - 1]
      
      if (!current[lastPart] || Object.keys(current[lastPart]).length === 0) {
        current[lastPart] = translations
      }

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
    async function initialize(): Promise<void> {
    // Prevent multiple initializations
    if (initialized.value) {
      return
    }

    loading.value = true
    error.value = null

    try {

      // Step 1: Get available locales (with validation)
      const locales = await translationService.getAvailableLocales()
      availableLocales.value = locales

      // Step 2: Try cache (memory + localStorage) for instant render
      const cachedTranslations = translationService.getCachedLocale(currentLocale.value)
      const hasCache = Object.keys(cachedTranslations).length > 0
      let transformedTranslationsData: TranslationMessages = {}

      if (hasCache) {
        transformedTranslationsData = transformTranslations(cachedTranslations)

        // Apply cached translations immediately
        i18n.global.setLocaleMessage(currentLocale.value, transformedTranslationsData)
        i18n.global.locale.value = currentLocale.value
        syncVuetifyLocale(currentLocale.value)
        initialized.value = true

      } else {
        // Cache miss - fetch from API
        // Fetch fresh translations from API
        const translations = await translationService.fetchAllTranslations(currentLocale.value)
        transformedTranslationsData = transformTranslations(translations)

        // Apply fresh translations
        i18n.global.setLocaleMessage(currentLocale.value, transformedTranslationsData)
        i18n.global.locale.value = currentLocale.value
        syncVuetifyLocale(currentLocale.value)
        initialized.value = true
      }

    } catch (err) {

      error.value = 'Failed to initialize translations'
      console.error('[LocaleStore] Initialization failed:', err)

      // Show error toast
      showToast('Failed to load translations. Using cached version if available.', 'warning')

      // Check if we have ANY cached data to work with
      const cachedTranslations = translationService.getCachedLocale(currentLocale.value)
      if (Object.keys(cachedTranslations).length > 0) {
        const transformedCache = transformTranslations(cachedTranslations)
        i18n.global.setLocaleMessage(currentLocale.value, transformedCache)
        i18n.global.locale.value = currentLocale.value
        syncVuetifyLocale(currentLocale.value)
        initialized.value = true // Mark as initialized even with cache
        showToast('Using cached translations', 'success')
      } else {
        // No cache available - this is critical but not fatal
        showToast('No translations available. Please check your connection.', 'error')
        console.error('[LocaleStore] CRITICAL: No translations available at all')
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
        console.log(`[LocaleStore] No cache for ${locale}, fetching from API`)
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
            showToast('Language changed locally (not saved to server)', 'warning')
          }
        })
      }

      // Step 6: Update state
      currentLocale.value = locale
      loadedNamespaces.value.clear() // Clear namespace tracking

      // Step 7: Success toast
      const localeInfo = availableLocales.value.find(l => l.code === locale)
      showToast(`Language changed to ${localeInfo?.native || locale}`, 'success')

    } catch (err) {
      error.value = `Failed to switch to ${locale}`
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

      const translations = await translationService.fetchNamespace(currentLocale.value, namespace)

      // Skip if empty (API returned nothing or empty object)
      if (!translations || Object.keys(translations).length === 0) {
        if (import.meta.env.DEV) {
          console.warn(`[LocaleStore] Namespace '${namespace}' is empty, skipping merge`)
        }
        return
      }

      // Merge into existing messages (preserve other namespaces)
      const currentMessages = i18n.global.getLocaleMessage(currentLocale.value)
      i18n.global.setLocaleMessage(currentLocale.value, {
        ...currentMessages,
        [namespace]: translations,
      })

      // Mark as loaded (only if we actually got data)
      loadedNamespaces.value.add(namespaceKey)
    } catch (err) {

      // Retry logic with exponential backoff
      if (retryCount < MAX_RETRIES) {

        const delay = RETRY_DELAY * (retryCount + 1) // 1s, 2s
        
        await new Promise(resolve => setTimeout(resolve, delay))
        return loadNamespace(namespace, retryCount + 1)
      } else {
        if (!namespace.includes('common') && !namespace.includes('validation')) {
          showToast(`Failed to load translations for ${namespace}`, 'warning')
        }
      }
    }
  }

  // Load multiple namespaces in parallel
  async function loadNamespaces(namespaces: string[]): Promise<void> {
    await Promise.all(namespaces.map(ns => loadNamespace(ns)))
  }

  // Set locale from user auth data
  function setLocaleFromUser(locale: SupportedLocale): void {
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
