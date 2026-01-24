/**
 * useTableFilters Composable
 * Zentralisierte Filter-Logik für ADT Views
 */

import { ref, computed, watch, type Ref, type ComputedRef } from 'vue'
import { useTableFilterStore } from '@/infrastructure/stores/tableFilterStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import type { TableFilter, TableFilterState, ColumnFilter } from '@/types/tableFilter'

// Interface für TableRef (DataTableCore exposed methods)
interface TableRefMethods {
  getColumnOrder: () => string[]
  getColumnWidths: () => Record<string, number>
  getHiddenColumns: () => string[]
  setColumnOrder: (order: string[]) => void
  setColumnWidths: (widths: Record<string, number>) => void
  setHiddenColumns: (columns: string[]) => void
  resetAllSettings: () => void
  hasCustomSettings?: boolean
}

export interface UseTableFiltersOptions {
  tableKey: string
  tableRef: Ref<TableRefMethods | null>
  loadData: () => void | Promise<void>
}

export interface UseTableFiltersReturn {
  // State
  page: Ref<number>
  itemsPerPage: Ref<number>
  sortBy: Ref<Array<{ key: string; order: 'asc' | 'desc' }>>
  search: Ref<string>
  columnFilters: Ref<ColumnFilter[]>
  saveDialogOpen: Ref<boolean>
  editingFilter: Ref<TableFilter | null>

  // Computed
  filters: ComputedRef<TableFilter[]>
  buttonFilters: ComputedRef<TableFilter[]>
  activeFilterId: ComputedRef<string | null>
  currentFilterState: ComputedRef<TableFilterState>

  // Methods
  handleFilterApply: (filter: TableFilter) => void
  handleFilterReset: () => void
  handleResetAllSettings: () => void
  openSaveDialog: (filter: TableFilter | null) => void
  handleSaveFilter: (data: { name: string; color: string; showAsButton: boolean }) => Promise<void>
  handleDeleteFilter: (filter: TableFilter) => Promise<void>
  handleUpdateFilter: () => Promise<void>
  buildQueryParams: () => Record<string, any>

  // Lifecycle
  initFilters: () => Promise<void>
}

export function useTableFilters(options: UseTableFiltersOptions): UseTableFiltersReturn {
  const { tableKey, tableRef, loadData } = options

  // Stores
  const filterStore = useTableFilterStore()
  const layoutStore = useLayoutStore()

  // Pagination State
  const page = ref(1)
  const itemsPerPage = ref(25)
  const sortBy = ref<Array<{ key: string; order: 'asc' | 'desc' }>>([])

  // Search State
  const search = ref('')

  // Column Filters State
  const columnFilters = ref<ColumnFilter[]>([])
  let skipColumnFilterWatch = false

  // Filter Dialog State
  const saveDialogOpen = ref(false)
  const editingFilter = ref<TableFilter | null>(null)

  // Filter Computed
  const filters = computed(() => filterStore.getFiltersForTable(tableKey))
  const buttonFilters = computed(() => filterStore.getButtonFilters(tableKey))
  const activeFilterId = computed(() => filterStore.activeFilterId)

  // Current Filter State (inkl. Spalteneinstellungen)
  const currentFilterState = computed((): TableFilterState => ({
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    sortBy: sortBy.value,
    search: search.value,
    columnFilters: columnFilters.value,
    columnOrder: tableRef.value?.getColumnOrder() || [],
    columnWidths: tableRef.value?.getColumnWidths() || {},
    hiddenColumns: tableRef.value?.getHiddenColumns() || []
  }))

  // Build Query Parameters
  const buildQueryParams = (): Record<string, any> => {
    const params: Record<string, any> = {
      page: page.value,
      per_page: itemsPerPage.value,
    }

    if (search.value) {
      params.search = search.value
    }

    // Sortierung - Default: created_at desc
    if (sortBy.value.length > 0) {
      params.sort_by = sortBy.value[0].key
      params.sort_order = sortBy.value[0].order
    } else {
      params.sort_by = 'created_at'
      params.sort_order = 'desc'
    }

    // Spalten-Filter hinzufügen
    if (columnFilters.value.length > 0) {
      params.filters = JSON.stringify(columnFilters.value)
    }

    return params
  }

  // Watch search for debounced reload
  let searchTimeout: ReturnType<typeof setTimeout> | null = null
  watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      page.value = 1
      loadData()
    }, 300)
  })

  // Watch column filters for reload (nur bei User-Interaktion)
  watch(columnFilters, () => {
    if (skipColumnFilterWatch) {
      skipColumnFilterWatch = false
      return
    }
    page.value = 1
    loadData()
  }, { deep: true })

  // Apply Filter State
  const applyFilterState = (state: TableFilterState) => {
    page.value = state.page || 1
    itemsPerPage.value = state.itemsPerPage || 25
    sortBy.value = state.sortBy || []
    search.value = state.search || ''

    // Skip watch um doppeltes Laden zu vermeiden
    skipColumnFilterWatch = true
    columnFilters.value = state.columnFilters || []

    // Spalteneinstellungen anwenden
    if (state.columnOrder && state.columnOrder.length > 0) {
      tableRef.value?.setColumnOrder(state.columnOrder)
    }
    if (state.columnWidths && Object.keys(state.columnWidths).length > 0) {
      tableRef.value?.setColumnWidths(state.columnWidths)
    }
    if (state.hiddenColumns && state.hiddenColumns.length > 0) {
      tableRef.value?.setHiddenColumns(state.hiddenColumns)
    }

    loadData()
  }

  // Filter Methods
  const handleFilterApply = (filter: TableFilter) => {
    filterStore.setActiveFilter(filter.id)
    applyFilterState(filter.filter_state)
  }

  const handleFilterReset = () => {
    filterStore.clearActiveFilter()
    page.value = 1
    search.value = ''
    sortBy.value = []
    skipColumnFilterWatch = true
    columnFilters.value = []
    tableRef.value?.resetAllSettings()
    loadData()
  }

  const handleResetAllSettings = () => {
    filterStore.clearActiveFilter()
    page.value = 1
    search.value = ''
    sortBy.value = []
    skipColumnFilterWatch = true
    columnFilters.value = []
    tableRef.value?.resetAllSettings()
    loadData()
  }

  const openSaveDialog = (filter: TableFilter | null) => {
    editingFilter.value = filter
    saveDialogOpen.value = true
  }

  const handleSaveFilter = async (data: { name: string; color: string; showAsButton: boolean }) => {
    try {
      if (editingFilter.value) {
        await filterStore.updateFilter(editingFilter.value.id, tableKey, {
          name: data.name,
          color: data.color,
          show_as_button: data.showAsButton,
          filter_state: currentFilterState.value
        })
        layoutStore.showSuccess('Filter erfolgreich aktualisiert')
      } else {
        await filterStore.createFilter({
          table_key: tableKey,
          name: data.name,
          color: data.color,
          show_as_button: data.showAsButton,
          filter_state: currentFilterState.value
        })
        layoutStore.showSuccess('Filter erfolgreich gespeichert')
      }
    } catch (e: any) {
      layoutStore.showError(e.message || 'Fehler beim Speichern des Filters')
    }
  }

  const handleDeleteFilter = async (filter: TableFilter) => {
    try {
      await filterStore.deleteFilter(filter.id, tableKey)
      layoutStore.showSuccess('Filter erfolgreich gelöscht')
    } catch (e: any) {
      layoutStore.showError(e.message || 'Fehler beim Löschen')
    }
  }

  const handleUpdateFilter = async () => {
    if (!activeFilterId.value) return

    const activeFilter = filters.value.find((f: TableFilter) => f.id === activeFilterId.value)
    if (!activeFilter) return

    try {
      await filterStore.updateFilter(activeFilter.id, tableKey, {
        filter_state: currentFilterState.value
      })
      layoutStore.showSuccess('Filter erfolgreich aktualisiert')
    } catch (e: any) {
      layoutStore.showError(e.message || 'Fehler beim Aktualisieren')
    }
  }

  // Initialize filters
  const initFilters = async () => {
    try {
      await filterStore.loadFilters(tableKey)
    } catch (e) {
      console.warn('Fehler beim Laden der Filter:', e)
    }
  }

  return {
    // State
    page,
    itemsPerPage,
    sortBy,
    search,
    columnFilters,
    saveDialogOpen,
    editingFilter,

    // Computed
    filters,
    buttonFilters,
    activeFilterId,
    currentFilterState,

    // Methods
    handleFilterApply,
    handleFilterReset,
    handleResetAllSettings,
    openSaveDialog,
    handleSaveFilter,
    handleDeleteFilter,
    handleUpdateFilter,
    buildQueryParams,

    // Lifecycle
    initFilters
  }
}
