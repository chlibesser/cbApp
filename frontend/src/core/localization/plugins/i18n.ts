/**
 * ============================================================================
 * VUE I18N PLUGIN - Vue Integration for Translations
 * ============================================================================
 *
 * This file creates and configures the vue-i18n instance.
 *
 * WHAT IS VUE-I18N:
 * - Official Vue.js internationalization plugin
 * - Provides $t() function in templates
 * - Handles message interpolation (parameters)
 * - Manages locale switching
 *
 * OUR ARCHITECTURE:
 * - vue-i18n provides the INTERFACE (template usage)
 * - translationService provides the DATA (from backend API)
 * - localeStore provides the STATE MANAGEMENT (Pinia)
 *
 * CONFIGURATION:
 * - legacy: false → Use Composition API (modern)
 * - messages: {} → Empty, loaded dynamically from API
 * - missing handler → Show key when translation missing
 * - globalInjection: true → Enable $t() in all components
 */

import { createI18n } from 'vue-i18n'
import type { I18n } from 'vue-i18n'

/**
 * Custom missing translation handler
 * CALLED WHEN: Translation key not found in messages
 */
function handleMissing(locale: string, key: string): string {
  // Log in development for debugging
  if (import.meta.env.DEV) {
    console.warn(`[i18n] Missing translation for key "${key}" in locale "${locale}"`)
  }
  // Example: 'admin.tenants.page_title' → 'page_title'
  const parts = key.split('.')
  return parts[parts.length - 1] || key
}

// Vue I18n instance
// CONFIGURATION OPTIONS:
export const i18n: I18n = createI18n({
  
  legacy: false,
  locale: import.meta.env.DEFAULT_LOCALE || 'de',
  fallbackLocale: import.meta.env.FALLBACK_LOCALE || 'de',
  messages: {},
  missingWarn: import.meta.env.DEV, // Only warn in development
  fallbackWarn: false, // Don't warn on fallback usage
  // Custom missing translation handler
  missing: handleMissing,
  globalInjection: true,
})

export default i18n