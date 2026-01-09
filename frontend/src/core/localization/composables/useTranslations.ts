/**
 * ============================================================================
 * USE TRANSLATIONS COMPOSABLE - Easy Translation Access for Components
 * ============================================================================
 *
 * This composable provides the easiest way for components to use translations.
 *
 * FEATURES:
 * - Simple t() function for translations
 * - Optional namespace auto-loading
 * - Reactive locale state
 * - Loading indicators
 * - Error state
 */

import { useI18n } from 'vue-i18n'
import { useLocaleStore } from '../stores/localeStore'
import type { SupportedLocale } from '../types/locale.types'
import { computed, watch, ref } from 'vue'

// Translation composable with optional namespace
export function useTranslations(namespace?: string) {

  // Get vue-i18n instance
  const { t: vueI18nT, locale } = useI18n()

  // Get locale store
  const localeStore = useLocaleStore()

  // Namespace-specific loading state
  const namespaceLoading = ref(false)

  // NAMESPACE AUTO-LOADING
  if (namespace) {
    const namespaceKey = `${locale.value}:${namespace}`

    if (!localeStore.loadedNamespaces.has(namespaceKey)) {
      namespaceLoading.value = true

      localeStore.loadNamespace(namespace).finally(() => {
        namespaceLoading.value = false
      })
    }
  }

  // Watch locale changes and reload namespace
  if (namespace) {
    watch(locale, async newLocale => {
      const namespaceKey = `${newLocale}:${namespace}`

      if (!localeStore.loadedNamespaces.has(namespaceKey)) {
        namespaceLoading.value = true
        await localeStore.loadNamespace(namespace)
        namespaceLoading.value = false
      }
    })
  }

  // Translation function with optional namespace prefix
  const translate = (key: string, params?: Record<string, any>) => {
    // Build full key with namespace prefix if provided
    const fullKey = namespace ? `${namespace}.${key}` : key

    // Call vue-i18n translation function
    return vueI18nT(fullKey, params)
  }

  // Change current locale
  const setLocale = async (newLocale: SupportedLocale) => {
    await localeStore.setLocale(newLocale)
  }


  return {
    t: translate,
    $t: vueI18nT,
    locale,
    setLocale,
    availableLocales: computed(() => localeStore.availableLocales),
    currentLocaleInfo: computed(() => localeStore.currentLocaleInfo),
    loading: computed(() => localeStore.loading || namespaceLoading.value),
    namespaceLoading,
    error: computed(() => localeStore.error),
  }
}
