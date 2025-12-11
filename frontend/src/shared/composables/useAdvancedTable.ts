/**
 * Advanced Data Table Composable
 * Portiert von V0 nach V1 - erweitert für cbApp V1
 */

import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue'
import type { TableState, TableColumn, PaginatedResponse, FilterState } from '../../types/table'
import { apiClient } from '@/core/api'

export function useAdvancedTable(
  apiEndpoint: string,
  columns: TableColumn[],
  options: {
    itemKey?: string
    defaultItemsPerPage?: number
    defaultSort?: { field: string; direction: 'asc' | 'desc' }
    autoRefresh?: boolean
  } = {}
) {
  const {
    itemKey = 'id',
    defaultItemsPerPage = 25,
    defaultSort,
    autoRefresh = true
  } = options

  // API client is available globally

  // State
  const state = reactive<TableState>({
    page: 1,
    itemsPerPage: defaultItemsPerPage,
    sortBy: defaultSort?.field || null,
    sortDirection: defaultSort?.direction || null,
    filters: {}
  })

  const items = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalItems = ref(0)
  const totalPages = ref(0)

  // Reactive filters state
  const filters = reactive<FilterState>({})

  // Computed
  const hasFilters = computed(() => Object.keys(state.filters).length > 0)
  const currentPage = computed(() => state.page)
  const itemsPerPageOptions = [10, 25, 50, 100]

  // Initialize filters from columns
  const initializeFilters = () => {
    columns.forEach(column => {
      if (column.filterable) {
        filters[column.key] = {
          value: null,
          type: column.filterType || 'text',
          options: column.filterOptions || []
        }
      }
    })
  }

  // Build query parameters
  const buildQueryParams = () => {
    const params: Record<string, any> = {
      page: state.page,
      per_page: state.itemsPerPage
    }

    // Sortierung
    if (state.sortBy) {
      params.sort_by = state.sortBy
      params.sort_direction = state.sortDirection || 'asc'
    }

    // Filter
    Object.entries(state.filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params[`filter[${key}]`] = value
      }
    })

    return params
  }

  // Load data from API
  const loadData = async (showLoading = true) => {
    if (showLoading) loading.value = true
    error.value = null

    try {
      const params = buildQueryParams()
      const response = await apiClient.get<PaginatedResponse>(apiEndpoint, { params })

      items.value = response.data.data
      totalItems.value = response.data.total
      totalPages.value = response.data.last_page
      state.page = response.data.current_page

    } catch (err: any) {
      error.value = err.message || 'Fehler beim Laden der Daten'
      items.value = []
      totalItems.value = 0
      totalPages.value = 0
    } finally {
      loading.value = false
    }
  }

  // Refresh data
  const refresh = () => {
    loadData(false)
  }

  // Pagination
  const setPage = (page: number) => {
    state.page = page
    loadData()
  }

  const setItemsPerPage = (perPage: number) => {
    state.itemsPerPage = perPage
    state.page = 1
    loadData()
  }

  const nextPage = () => {
    if (state.page < totalPages.value) {
      setPage(state.page + 1)
    }
  }

  const previousPage = () => {
    if (state.page > 1) {
      setPage(state.page - 1)
    }
  }

  // Sorting
  const setSorting = (field: string, direction: 'asc' | 'desc' | null = null) => {
    const column = columns.find(col => col.key === field)
    if (!column?.sortable) return

    if (state.sortBy === field) {
      // Toggle direction
      if (state.sortDirection === 'asc') {
        state.sortDirection = 'desc'
      } else if (state.sortDirection === 'desc') {
        state.sortBy = null
        state.sortDirection = null
      } else {
        state.sortDirection = 'asc'
      }
    } else {
      state.sortBy = field
      state.sortDirection = direction || 'asc'
    }

    state.page = 1
    loadData()
  }

  // Filtering
  const setFilter = (field: string, value: any) => {
    if (value === null || value === undefined || value === '') {
      delete state.filters[field]
    } else {
      state.filters[field] = value
    }

    state.page = 1
    loadData()
  }

  const clearFilters = () => {
    state.filters = {}
    state.page = 1
    loadData()
  }

  const clearFilter = (field: string) => {
    delete state.filters[field]
    state.page = 1
    loadData()
  }

  // Search functionality
  const search = (query: string) => {
    if (query.trim()) {
      state.filters.search = query
    } else {
      delete state.filters.search
    }

    state.page = 1
    loadData()
  }

  // Event listeners for global refresh
  const handleGlobalRefresh = (event: CustomEvent) => {
    const { entity, action } = event.detail || {}
    
    // Refresh auf relevante Events
    if (!entity || entity === getEntityNameFromEndpoint(apiEndpoint)) {
      refresh()
    }
  }

  const getEntityNameFromEndpoint = (endpoint: string): string => {
    const parts = endpoint.split('/')
    return parts[parts.length - 1] // Letzter Teil der URL
  }

  // Lifecycle
  onMounted(() => {
    initializeFilters()
    loadData()

    if (autoRefresh) {
      window.addEventListener('table-refresh', handleGlobalRefresh as EventListener)
    }
  })

  onUnmounted(() => {
    if (autoRefresh) {
      window.removeEventListener('table-refresh', handleGlobalRefresh as EventListener)
    }
  })

  // Watch for external state changes
  watch(
    () => state.filters,
    () => {
      // Auto-refresh bei Filter-Änderungen
      loadData()
    },
    { deep: true }
  )

  // Public API
  return {
    // State
    items,
    loading,
    error,
    totalItems,
    totalPages,
    currentPage,
    filters,
    hasFilters,
    state,

    // Pagination
    setPage,
    setItemsPerPage,
    nextPage,
    previousPage,
    itemsPerPageOptions,

    // Sorting
    setSorting,

    // Filtering
    setFilter,
    clearFilters,
    clearFilter,
    search,

    // Data management
    loadData,
    refresh,

    // Computed helpers
    getSortDirection: (field: string) => {
      return state.sortBy === field ? state.sortDirection : null
    },
    isSorted: (field: string) => {
      return state.sortBy === field
    },
    getFilterValue: (field: string) => {
      return state.filters[field] || null
    }
  }
}