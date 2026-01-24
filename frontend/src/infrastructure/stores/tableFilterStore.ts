/**
 * Table Filter Store
 * Zentrales State-Management für Table Filter
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { tableFilterService } from '@/shared/services/tableFilterService'
import type {
  TableFilter,
  TableFilterState,
  CreateTableFilterPayload,
  UpdateTableFilterPayload
} from '@/types/tableFilter'

export const useTableFilterStore = defineStore('tableFilter', () => {
  // State
  const filters = ref<Map<string, TableFilter[]>>(new Map())
  const loading = ref(false)
  const error = ref<string | null>(null)
  const activeFilterId = ref<string | null>(null)

  // Getters
  const getFiltersForTable = computed(() => {
    return (tableKey: string) => filters.value.get(tableKey) || []
  })

  const getButtonFilters = computed(() => {
    return (tableKey: string) =>
      (filters.value.get(tableKey) || []).filter(f => f.show_as_button)
  })

  const activeFilter = computed(() => {
    if (!activeFilterId.value) return null
    for (const tableFilters of filters.value.values()) {
      const found = tableFilters.find(f => f.id === activeFilterId.value)
      if (found) return found
    }
    return null
  })

  // Actions
  async function loadFilters(tableKey: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const data = await tableFilterService.getFilters(tableKey)
      filters.value.set(tableKey, data)
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Laden der Filter'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function createFilter(payload: CreateTableFilterPayload): Promise<TableFilter> {
    loading.value = true
    error.value = null

    try {
      const newFilter = await tableFilterService.createFilter(payload)

      const tableFilters = filters.value.get(payload.table_key) || []
      filters.value.set(payload.table_key, [...tableFilters, newFilter])

      return newFilter
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Erstellen des Filters'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateFilter(
    filterId: string,
    tableKey: string,
    payload: UpdateTableFilterPayload
  ): Promise<TableFilter> {
    loading.value = true
    error.value = null

    try {
      const updatedFilter = await tableFilterService.updateFilter(filterId, payload)

      const tableFilters = filters.value.get(tableKey) || []
      const index = tableFilters.findIndex(f => f.id === filterId)
      if (index !== -1) {
        tableFilters[index] = updatedFilter
        filters.value.set(tableKey, [...tableFilters])
      }

      return updatedFilter
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Aktualisieren des Filters'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteFilter(filterId: string, tableKey: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await tableFilterService.deleteFilter(filterId)

      const tableFilters = filters.value.get(tableKey) || []
      filters.value.set(
        tableKey,
        tableFilters.filter(f => f.id !== filterId)
      )

      if (activeFilterId.value === filterId) {
        activeFilterId.value = null
      }
    } catch (e: any) {
      error.value = e.message || 'Fehler beim Löschen des Filters'
      throw e
    } finally {
      loading.value = false
    }
  }

  function setActiveFilter(filterId: string | null): void {
    activeFilterId.value = filterId
  }

  function clearActiveFilter(): void {
    activeFilterId.value = null
  }

  return {
    // State
    filters,
    loading,
    error,
    activeFilterId,

    // Getters
    getFiltersForTable,
    getButtonFilters,
    activeFilter,

    // Actions
    loadFilters,
    createFilter,
    updateFilter,
    deleteFilter,
    setActiveFilter,
    clearActiveFilter,
  }
})
