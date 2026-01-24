/**
 * useFilterContext Composable
 * Provide/Inject Pattern für Filter-Logik
 * Ermöglicht TableToolbar direkten Zugriff auf Filter-State ohne Prop-Drilling
 */

import { provide, inject, type InjectionKey } from 'vue'
import type { UseTableFiltersReturn } from './useTableFilters'

// Typisierter Injection Key
export const FILTER_CONTEXT: InjectionKey<UseTableFiltersReturn> = Symbol('FilterContext')

/**
 * Stellt den Filter-Context für Child-Komponenten bereit
 * Wird in Views aufgerufen (AccountsView, UsersView, etc.)
 */
export function provideFilterContext(filterState: UseTableFiltersReturn): void {
  provide(FILTER_CONTEXT, filterState)
}

/**
 * Holt den Filter-Context aus dem Parent
 * Wird in TableToolbar und anderen Filter-Komponenten verwendet
 */
export function useFilterContext(): UseTableFiltersReturn {
  const context = inject(FILTER_CONTEXT)
  if (!context) {
    throw new Error(
      'useFilterContext must be used within a component that provides FilterContext. ' +
      'Make sure provideFilterContext() is called in the parent View.'
    )
  }
  return context
}

/**
 * Optionale Version die undefined zurückgibt wenn kein Context vorhanden
 * Nützlich für Komponenten die auch ohne Context funktionieren sollen
 */
export function useFilterContextOptional(): UseTableFiltersReturn | undefined {
  return inject(FILTER_CONTEXT, undefined)
}
