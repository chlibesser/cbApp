/**
 * ============================================================================
 * I18N ROUTER GUARD - Automatic Namespace Loading
 * ============================================================================
 *
 * This router guard ensures translations are loaded BEFORE components render.
 *
 */

import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useLocaleStore } from '../stores/localeStore'
import { getNamespacesForRoute } from '../utils/namespaceMapper'

// I18n router guard function
export async function i18nGuard(
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext
): Promise<void> {
  // Get locale store
  const localeStore = useLocaleStore()

  // If i18n not initialized yet, skip loading
  if (!localeStore.initialized) {
    next()
    return
  }

  try {
    // Determine required namespaces for this route
    const namespaces = getNamespacesForRoute(to.name as string)

    const allCached = namespaces.every((ns) => localeStore.isNamespaceLoaded(ns))
    if (allCached) {
      console.log('[i18nGuard] All namespaces already loaded, skipping:', namespaces)
      next()
      return
    }
    // Load required namespaces
    if (namespaces.length > 0) {
      await localeStore.loadNamespaces(namespaces)
    }
    next()
  } catch (error) {
    console.error('[i18nGuard] Failed to load namespaces:', error)

    next()
  }
}
