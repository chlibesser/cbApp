<template>
  <v-card>
    <!-- Toolbar -->
    <v-card-title class="d-flex align-center justify-space-between">
      <TableToolbar
        v-model="search"
        :table-key="tableKey"
        :enable-search="true"
        :enable-filters="enableFilters && !!tableKey"
        :enable-create="enableCreate"
        :create-button-text="createButtonText"
        :filters="filters"
        :button-filters="buttonFilters"
        :active-filter-id="activeFilterId"
        :has-custom-column-order="tableRef?.hasCustomColumnOrder"
        :has-custom-column-widths="tableRef?.hasCustomColumnWidths"
        :has-custom-settings="tableRef?.hasCustomSettings"
        @create="$emit('create')"
        @filter-apply="handleFilterApply"
        @filter-reset="handleFilterReset"
        @filter-save="openSaveDialog(null)"
        @filter-edit="openSaveDialog"
        @filter-delete="handleDeleteFilter"
        @reset-column-order="tableRef?.resetColumnOrder()"
        @reset-column-widths="tableRef?.resetColumnWidths()"
        @reset-all-settings="tableRef?.resetAllSettings()"
      />
    </v-card-title>

    <v-divider />

    <!-- Data Table -->
    <DataTableCore
      ref="tableRef"
      :table-key="tableKey"
      :columns="columns"
      :items="items"
      :total-items="totalItems"
      :loading="loading"
      v-model:page="page"
      v-model:items-per-page="itemsPerPage"
      v-model:sort-by="sortBy"
      @row-click="handleRowClick"
      @options-update="loadData"
    >
      <!-- Pass through all slots -->
      <template v-for="(_, slotName) in $slots" :key="slotName" #[slotName]="slotProps">
        <slot :name="slotName" v-bind="slotProps" />
      </template>
    </DataTableCore>

    <!-- Filter Save Dialog -->
    <FilterSaveDialog
      v-if="enableFilters && tableKey"
      v-model="saveDialogOpen"
      :filter-state="currentFilterState"
      :table-key="tableKey"
      :existing-filter="editingFilter"
      @save="handleSaveFilter"
    />
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useApi } from '@/core/api'
import { useNotifications } from '@/core/composables/useNotifications'
import { useTableFilterStore } from '@/infrastructure/stores/tableFilterStore'
import DataTableCore from './DataTableCore.vue'
import TableToolbar from './TableToolbar.vue'
import FilterSaveDialog from './FilterSaveDialog.vue'
import type { TableColumn } from '@/types/table'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

interface Props {
  columns: TableColumn[]
  apiEndpoint: string
  enableCreate?: boolean
  createButtonText?: string
  defaultItemsPerPage?: number
  tableKey?: string
  enableFilters?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  enableCreate: false,
  createButtonText: '',
  defaultItemsPerPage: 10,
  tableKey: '',
  enableFilters: true
})

const emit = defineEmits<{
  create: []
  itemSelected: [item: any]
  'item-double-click': [item: any]
  'update:count': [count: number]
}>()

// Composables
const api = useApi()
const { showError, showSuccess } = useNotifications()
const filterStore = useTableFilterStore()

// Table Ref
const tableRef = ref<InstanceType<typeof DataTableCore> | null>(null)

// Filter State
const saveDialogOpen = ref(false)
const editingFilter = ref<TableFilter | null>(null)

// Filter Computed
const filters = computed(() => filterStore.getFiltersForTable(props.tableKey))
const buttonFilters = computed(() => filterStore.getButtonFilters(props.tableKey))
const activeFilterId = computed(() => filterStore.activeFilterId)

// Pagination State
const page = ref(1)
const itemsPerPage = ref(props.defaultItemsPerPage)
const totalItems = ref(0)
const sortBy = ref<Array<{ key: string; order: 'asc' | 'desc' }>>([])

// Search State
const search = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Data State
const items = ref<any[]>([])
const loading = ref(false)

// Current Filter State
const currentFilterState = computed((): TableFilterState => ({
  page: page.value,
  itemsPerPage: itemsPerPage.value,
  sortBy: sortBy.value,
  search: search.value
}))

// Watch search for debounced reload
watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    loadData()
  }, 300)
})

// Build Query Parameters
const buildQueryParams = (): Record<string, any> => {
  const params: Record<string, any> = {
    page: page.value,
    per_page: itemsPerPage.value,
  }

  if (search.value) {
    params.search = search.value
  }

  if (sortBy.value.length > 0) {
    params.sort_by = sortBy.value[0].key
    params.sort_order = sortBy.value[0].order
  }

  return params
}

// Load Data from API
const loadData = async () => {
  try {
    loading.value = true

    const params = buildQueryParams()
    const queryString = new URLSearchParams(params).toString()
    const url = `${props.apiEndpoint}?${queryString}`

    const response = await api.get(url)

    // Handle Laravel pagination response
    if (response.data.data && typeof response.data.total !== 'undefined') {
      items.value = response.data.data
      totalItems.value = response.data.total
    } else if (response.data.users && typeof response.data.meta?.total !== 'undefined') {
      items.value = response.data.users
      totalItems.value = response.data.meta.total
    } else if (Array.isArray(response.data)) {
      items.value = response.data
      totalItems.value = response.data.length
    } else if (response.data.data && Array.isArray(response.data.data)) {
      items.value = response.data.data
      totalItems.value = response.data.data.length
    } else {
      items.value = []
      totalItems.value = 0
    }

    emit('update:count', totalItems.value)
  } catch (error: any) {
    console.error('Error loading data:', error)
    showError('Fehler beim Laden der Daten')
    items.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

// Handle Row Click
const handleRowClick = (item: any) => {
  emit('itemSelected', item)
  emit('item-double-click', item)
}

// Filter Methods
const handleFilterApply = (filter: TableFilter) => {
  filterStore.setActiveFilter(filter.id)
  applyFilterState(filter.filter_state)
}

const handleFilterReset = () => {
  filterStore.clearActiveFilter()
  resetFilters()
}

const applyFilterState = (state: TableFilterState) => {
  page.value = state.page || 1
  itemsPerPage.value = state.itemsPerPage || props.defaultItemsPerPage
  sortBy.value = state.sortBy || []
  search.value = state.search || ''
  loadData()
}

const resetFilters = () => {
  page.value = 1
  search.value = ''
  sortBy.value = []
  loadData()
}

const openSaveDialog = (filter: TableFilter | null) => {
  editingFilter.value = filter
  saveDialogOpen.value = true
}

const handleSaveFilter = async (data: { name: string; color: string; showAsButton: boolean }) => {
  if (!props.tableKey) {
    showError('Kein Table-Key definiert')
    return
  }

  try {
    if (editingFilter.value) {
      await filterStore.updateFilter(editingFilter.value.id, props.tableKey, {
        name: data.name,
        color: data.color,
        show_as_button: data.showAsButton,
        filter_state: currentFilterState.value
      })
      showSuccess('Filter erfolgreich aktualisiert')
    } else {
      await filterStore.createFilter({
        table_key: props.tableKey,
        name: data.name,
        color: data.color,
        show_as_button: data.showAsButton,
        filter_state: currentFilterState.value
      })
      showSuccess('Filter erfolgreich gespeichert')
    }
  } catch (e: any) {
    showError(e.message || 'Fehler beim Speichern des Filters')
  }
}

const handleDeleteFilter = async (filter: TableFilter) => {
  try {
    await filterStore.deleteFilter(filter.id, props.tableKey)
    showSuccess('Filter erfolgreich gelöscht')
  } catch (e: any) {
    showError(e.message || 'Fehler beim Löschen')
  }
}

// Refresh function that can be called from parent
const refresh = () => {
  loadData()
}

// Reset to first page and refresh
const reset = () => {
  page.value = 1
  search.value = ''
  sortBy.value = []
  loadData()
}

// Event handler for table refresh
const handleTableRefresh = () => {
  refresh()
}

// Watch for endpoint changes
watch(() => props.apiEndpoint, () => {
  page.value = 1
  loadData()
})

// Lifecycle
onMounted(async () => {
  loadData()
  window.addEventListener('table-refresh', handleTableRefresh)

  // Load filters if enabled
  if (props.enableFilters && props.tableKey) {
    try {
      await filterStore.loadFilters(props.tableKey)
    } catch (e) {
      console.warn('Fehler beim Laden der Filter:', e)
    }
  }
})

onUnmounted(() => {
  window.removeEventListener('table-refresh', handleTableRefresh)
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
})

// Expose methods for parent components
defineExpose({
  refresh,
  reset
})
</script>
