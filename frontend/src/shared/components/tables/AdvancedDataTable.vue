<template>
  <v-card>
    <!-- Toolbar -->
    <v-card-title class="d-flex align-center justify-space-between">
      <div class="d-flex align-center gap-4">
        <v-text-field
          v-model="searchInput"
          prepend-inner-icon="mdi-magnify"
          :label="t('buttons.search')"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 300px;"
          @update:model-value="handleSearchInput"
        />

        <!-- Filter Quick-Access Buttons -->
        <template v-if="enableFilters && tableKey && buttonFilters.length > 0">
          <v-btn
            v-for="filter in buttonFilters"
            :key="filter.id"
            :color="activeFilterId === filter.id ? filter.color : undefined"
            :variant="activeFilterId === filter.id ? 'flat' : 'outlined'"
            size="small"
            @click="handleQuickFilterClick(filter)"
          >
            {{ filter.name }}
          </v-btn>
        </template>

        <!-- Combined Settings Menu -->
        <v-menu
          v-model="settingsMenuOpen"
          :close-on-content-click="false"
          location="bottom end"
        >
          <template #activator="{ props: menuProps }">
            <v-btn
              v-bind="menuProps"
              variant="text"
              size="small"
            >
              <v-icon start>mdi-filter-variant</v-icon>
              Filter
              <v-badge
                v-if="filterCount > 0"
                :content="filterCount"
                color="primary"
                inline
              />
              <v-icon end>mdi-cog</v-icon>
            </v-btn>
          </template>

          <v-card min-width="280">
            <!-- Filter Section -->
            <template v-if="enableFilters && tableKey">
              <v-card-title class="text-subtitle-1 d-flex align-center justify-space-between py-2">
                Gespeicherte Filter
                <v-btn
                  icon
                  variant="text"
                  size="small"
                  @click="openSaveDialog(null)"
                >
                  <v-icon>mdi-plus</v-icon>
                  <v-tooltip activator="parent" location="top">
                    Aktuellen Filter speichern
                  </v-tooltip>
                </v-btn>
              </v-card-title>

              <v-list v-if="filters.length > 0" density="compact" class="py-0">
                <v-list-item
                  v-for="filter in filters"
                  :key="filter.id"
                  :active="activeFilterId === filter.id"
                  @click="handleFilterClick(filter)"
                >
                  <template #prepend>
                    <v-avatar :color="filter.color" size="24">
                      <v-icon size="small" color="white">mdi-filter</v-icon>
                    </v-avatar>
                  </template>

                  <v-list-item-title>{{ filter.name }}</v-list-item-title>

                  <template #append>
                    <v-btn
                      icon
                      variant="text"
                      size="x-small"
                      @click.stop="openSaveDialog(filter)"
                    >
                      <v-icon size="small">mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn
                      icon
                      variant="text"
                      size="x-small"
                      color="error"
                      @click.stop="handleDeleteFilter(filter)"
                    >
                      <v-icon size="small">mdi-delete</v-icon>
                    </v-btn>
                  </template>
                </v-list-item>
              </v-list>

              <v-card-text v-else class="text-center text-medium-emphasis py-3">
                Keine Filter gespeichert
              </v-card-text>

              <template v-if="activeFilterId">
                <v-list-item @click="handleResetFilters">
                  <template #prepend>
                    <v-icon>mdi-filter-off</v-icon>
                  </template>
                  <v-list-item-title>Filter zurücksetzen</v-list-item-title>
                </v-list-item>
              </template>

              <v-divider />
            </template>

            <!-- Column Settings Section -->
            <template v-if="enableColumnReorder">
              <v-list-subheader>Spalten</v-list-subheader>

              <v-list-item
                :disabled="!hasCustomColumnOrder"
                @click="handleResetColumnOrder"
              >
                <template #prepend>
                  <v-icon>mdi-table-column</v-icon>
                </template>
                <v-list-item-title>Spaltenreihenfolge zurücksetzen</v-list-item-title>
              </v-list-item>

              <v-list-item
                :disabled="!hasCustomColumnWidths"
                @click="handleResetColumnWidths"
              >
                <template #prepend>
                  <v-icon>mdi-arrow-expand-horizontal</v-icon>
                </template>
                <v-list-item-title>Spaltenbreiten zurücksetzen</v-list-item-title>
              </v-list-item>

              <v-divider class="my-1" />

              <v-list-item
                :disabled="!hasCustomSettings"
                color="error"
                @click="handleResetAllSettings"
              >
                <template #prepend>
                  <v-icon>mdi-refresh</v-icon>
                </template>
                <v-list-item-title>Alles zurücksetzen</v-list-item-title>
              </v-list-item>
            </template>
          </v-card>
        </v-menu>
      </div>

      <v-btn
        v-if="enableCreate"
        color="primary"
        prepend-icon="mdi-plus"
        @click="$emit('create')"
      >
        {{ createButtonText || t('buttons.create') }}
      </v-btn>
    </v-card-title>

    <v-divider />

    <!-- Server-Side Data Table -->
    <v-data-table-server
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      v-model:sort-by="sortBy"
      :headers="orderedHeaders"
      :items="items"
      :items-length="totalItems"
      :loading="loading"
      item-value="id"
      class="elevation-0 adt-table"
      :items-per-page-options="itemsPerPageOptions"
      @update:options="handleOptionsUpdate"
      @click:row="handleRowClick"
    >
      <!-- Custom Headers with Drag & Drop and Resize -->
      <template #headers="{ columns: headerColumns, isSorted, getSortIcon, toggleSort }">
        <tr>
          <th
            v-for="(header, index) in headerColumns"
            :key="header.key"
            :class="[
              'adt-header',
              { 'adt-header--dragging': draggedIndex === index },
              { 'adt-header--drag-over': dragOverIndex === index && dragOverIndex !== draggedIndex },
              { 'adt-header--resizing': resizingColumn === header.key }
            ]"
            :style="getHeaderStyle(header)"
            :draggable="enableColumnReorder && header.key !== 'actions' && !resizingColumn"
            @dragstart.stop="handleDragStart($event, index, header.key)"
            @dragend.stop="handleDragEnd"
            @dragover.prevent="handleDragOver($event, index)"
            @dragleave="handleDragLeave"
            @drop.prevent="handleDrop($event, index)"
          >
            <div class="adt-header-content">
              <!-- Drag Handle -->
              <v-icon
                v-if="enableColumnReorder && header.key !== 'actions'"
                class="adt-drag-handle"
                size="x-small"
              >
                mdi-drag-vertical
              </v-icon>

              <!-- Header Content -->
              <span
                class="adt-header-title"
                :class="{ 'cursor-pointer': header.sortable }"
                @click.stop="header.sortable ? toggleSort(header) : null"
              >
                {{ header.title }}
              </span>

              <!-- Sort Icon -->
              <v-icon
                v-if="isSorted(header)"
                class="ml-1"
                size="small"
              >
                {{ getSortIcon(header) }}
              </v-icon>
            </div>

            <!-- Resize Handle -->
            <div
              v-if="header.key !== 'actions'"
              class="adt-resize-handle"
              @mousedown.stop.prevent="handleResizeStart($event, header.key)"
            />
          </th>
        </tr>
      </template>

      <!-- Dynamic Slots for Custom Cell Content -->
      <template
        v-for="column in (columns || [])"
        :key="column.key"
        #[`item.${column.key}`]="slotProps"
      >
        <slot
          :name="`item.${column.key}`"
          v-bind="slotProps"
        >
          {{ slotProps.value }}
        </slot>
      </template>

      <!-- Loading State -->
      <template #loading>
        <v-skeleton-loader type="table-row@10" />
      </template>

      <!-- No Data State -->
      <template #no-data>
        <div class="text-center pa-8">
          <v-icon size="48" color="grey-lighten-1">mdi-database-off</v-icon>
          <p class="text-h6 mt-4 mb-2">{{ t('messages.no_data') }}</p>
          <p class="text-body-2 text-medium-emphasis">
            {{ t('messages.no_entries') }}
          </p>
          <v-btn
            v-if="enableCreate"
            color="primary"
            class="mt-4"
            @click="$emit('create')"
          >
            {{ t('buttons.create_first') }}
          </v-btn>
        </div>
      </template>
    </v-data-table-server>

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
import { useTranslations } from '@/core/localization/composables/useTranslations'
import { useTableFilterStore } from '@/infrastructure/stores/tableFilterStore'
import { useTableSettingsStore } from '@/infrastructure/stores/tableSettingsStore'
import FilterSaveDialog from './FilterSaveDialog.vue'
import type { TableColumn } from '@/types/table'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

const { t } = useTranslations('admin.common')

interface Props {
  columns: TableColumn[]
  apiEndpoint: string
  enableCreate?: boolean
  createButtonText?: string
  defaultItemsPerPage?: number
  tableKey?: string
  enableFilters?: boolean
  enableColumnReorder?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  enableCreate: false,
  createButtonText: '',
  defaultItemsPerPage: 10,
  tableKey: '',
  enableFilters: true,
  enableColumnReorder: true
})

const emit = defineEmits<{
  create: []
  itemSelected: [item: any]
  'update:count': [count: number]
}>()

// Composables
const api = useApi()
const { showError, showSuccess } = useNotifications()
const filterStore = useTableFilterStore()
const settingsStore = useTableSettingsStore()

// Filter State
const saveDialogOpen = ref(false)
const editingFilter = ref<TableFilter | null>(null)
const settingsMenuOpen = ref(false)

// Filter Computed
const filters = computed(() => filterStore.getFiltersForTable(props.tableKey))
const buttonFilters = computed(() => filterStore.getButtonFilters(props.tableKey))
const activeFilterId = computed(() => filterStore.activeFilterId)
const filterCount = computed(() => filters.value.length)

// Pagination State
const page = ref(1)
const itemsPerPage = ref(props.defaultItemsPerPage)
const totalItems = ref(0)
const sortBy = ref<Array<{ key: string; order: 'asc' | 'desc' }>>([])

// Search State
const searchInput = ref('')
const search = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Data State
const items = ref<any[]>([])
const loading = ref(false)

// Column Order State
const columnOrder = ref<string[]>([])
const draggedIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

// Column Width State
const columnWidths = ref<Record<string, number>>({})
const resizingColumn = ref<string | null>(null)
const resizeStartX = ref(0)
const resizeStartWidth = ref(0)

// Items per page options
const itemsPerPageOptions = [
  { value: 10, title: '10' },
  { value: 25, title: '25' },
  { value: 50, title: '50' },
  { value: 100, title: '100' },
]

// Check if any custom settings exist
const hasCustomSettings = computed(() =>
  hasCustomColumnOrder.value || hasCustomColumnWidths.value
)

// Check if custom column order exists
const hasCustomColumnOrder = computed(() => {
  if (!columnOrder.value.length) return false
  const defaultOrder = props.columns.map(c => c.key)
  return JSON.stringify(columnOrder.value) !== JSON.stringify(defaultOrder)
})

// Helper to translate title if it's a translation key
const translateTitle = (title: string): string => {
  // If title contains dots and looks like a translation key, translate it
  if (title && title.includes('.') && !title.includes(' ')) {
    const translated = t(title)
    // If translation returns the same key, it wasn't found - return original
    return translated !== title ? translated : title
  }
  return title
}

// Computed Headers (base)
const computedHeaders = computed(() => {
  if (!props.columns || !Array.isArray(props.columns)) {
    return []
  }
  return props.columns
    .filter(column => column.visible !== false)
    .map(column => ({
      title: translateTitle(column.title),
      key: column.key,
      sortable: column.sortable !== false,
      width: column.width ? `${column.width}px` : undefined,
      align: column.align || 'start',
    }))
})

// Ordered Headers (respects column order)
const orderedHeaders = computed(() => {
  if (!columnOrder.value.length || !props.enableColumnReorder) {
    return computedHeaders.value
  }

  const headerMap = new Map(computedHeaders.value.map(h => [h.key, h]))
  const ordered: typeof computedHeaders.value = []

  // Add headers in the saved order
  for (const key of columnOrder.value) {
    const header = headerMap.get(key)
    if (header) {
      ordered.push(header)
      headerMap.delete(key)
    }
  }

  // Add any remaining headers (new columns)
  for (const header of headerMap.values()) {
    ordered.push(header)
  }

  return ordered
})

// Get header style with custom width
const getHeaderStyle = (header: { key: string; width?: string }) => {
  const customWidth = columnWidths.value[header.key]
  if (customWidth) {
    return {
      width: `${customWidth}px`,
    }
  }
  return {
    width: header.width,
  }
}

// Check if custom column widths exist
const hasCustomColumnWidths = computed(() => {
  return Object.keys(columnWidths.value).length > 0
})

// Drag & Drop Handlers
const draggedKey = ref<string | null>(null)

const handleDragStart = (event: DragEvent, index: number, key: string) => {
  if (!props.enableColumnReorder) return

  draggedIndex.value = index
  draggedKey.value = key

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', key)
    // Kleines Drag-Image für besseres UX
    const dragImage = document.createElement('div')
    dragImage.textContent = orderedHeaders.value[index]?.title || key
    dragImage.style.cssText = 'position:absolute;top:-1000px;padding:8px 12px;background:#1976D2;color:white;border-radius:4px;font-size:14px;'
    document.body.appendChild(dragImage)
    event.dataTransfer.setDragImage(dragImage, 0, 0)
    setTimeout(() => document.body.removeChild(dragImage), 0)
  }
}

const handleDragEnd = () => {
  draggedIndex.value = null
  draggedKey.value = null
  dragOverIndex.value = null
}

const handleDragOver = (event: DragEvent, index: number) => {
  if (!props.enableColumnReorder || draggedIndex.value === null) return

  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }

  if (index !== draggedIndex.value) {
    dragOverIndex.value = index
  }
}

const handleDragLeave = () => {
  // Nur zurücksetzen wenn wir die Tabelle verlassen
}

const handleDrop = (event: DragEvent, targetIndex: number) => {
  if (!props.enableColumnReorder) return

  const sourceIndex = draggedIndex.value
  if (sourceIndex === null || sourceIndex === targetIndex) {
    handleDragEnd()
    return
  }

  // Get current order or initialize from headers
  const currentOrder = columnOrder.value.length
    ? [...columnOrder.value]
    : orderedHeaders.value.map(h => h.key)

  // Move the column
  const [movedKey] = currentOrder.splice(sourceIndex, 1)
  currentOrder.splice(targetIndex, 0, movedKey)

  // Update state
  columnOrder.value = currentOrder

  // Persist to localStorage
  saveColumnOrder()

  handleDragEnd()
}

// Save column order to backend (via store with debounce)
const saveColumnOrder = () => {
  if (!props.tableKey) return
  settingsStore.saveColumnOrder(props.tableKey, columnOrder.value)
}

// Load settings from backend
const loadTableSettings = async () => {
  if (!props.tableKey) return

  try {
    const settings = await settingsStore.loadSettings(props.tableKey)
    if (settings.column_order) {
      columnOrder.value = settings.column_order
    }
    if (settings.column_widths) {
      columnWidths.value = settings.column_widths
    }
  } catch (e) {
    console.warn('Failed to load table settings:', e)
  }
}

// Reset column order to default
const resetColumnOrder = () => {
  columnOrder.value = []
  if (props.tableKey) {
    // Save empty order to backend
    settingsStore.saveColumnOrder(props.tableKey, [])
  }
}

// Column Resize Handlers
const handleResizeStart = (event: MouseEvent, columnKey: string) => {
  resizingColumn.value = columnKey
  resizeStartX.value = event.clientX

  // Get current width
  const currentWidth = columnWidths.value[columnKey]
  if (currentWidth) {
    resizeStartWidth.value = currentWidth
  } else {
    // Get from DOM if not set
    const th = (event.target as HTMLElement).parentElement
    resizeStartWidth.value = th?.offsetWidth || 150
  }

  // Add global listeners
  document.addEventListener('mousemove', handleResizeMove)
  document.addEventListener('mouseup', handleResizeEnd)
  document.body.style.cursor = 'col-resize'
  document.body.style.userSelect = 'none'
}

const handleResizeMove = (event: MouseEvent) => {
  if (!resizingColumn.value) return

  const diff = event.clientX - resizeStartX.value
  const newWidth = Math.max(20, resizeStartWidth.value + diff) // Min 20px für Usability

  columnWidths.value = {
    ...columnWidths.value,
    [resizingColumn.value]: newWidth
  }
}

const handleResizeEnd = () => {
  if (resizingColumn.value && props.tableKey) {
    // Save to backend
    settingsStore.saveColumnWidths(props.tableKey, columnWidths.value)
  }

  resizingColumn.value = null
  document.removeEventListener('mousemove', handleResizeMove)
  document.removeEventListener('mouseup', handleResizeEnd)
  document.body.style.cursor = ''
  document.body.style.userSelect = ''
}

// Reset column widths
const resetColumnWidths = () => {
  columnWidths.value = {}
  if (props.tableKey) {
    settingsStore.saveColumnWidths(props.tableKey, {})
  }
}

// Reset all table settings
const resetAllSettings = async () => {
  columnOrder.value = []
  columnWidths.value = {}
  if (props.tableKey) {
    try {
      await settingsStore.resetSettings(props.tableKey)
      showSuccess('Tabelleneinstellungen zurückgesetzt')
    } catch (e) {
      showError('Fehler beim Zurücksetzen der Einstellungen')
    }
  }
}

// Current Filter State (für Save-Dialog)
const currentFilterState = computed((): TableFilterState => ({
  page: page.value,
  itemsPerPage: itemsPerPage.value,
  sortBy: sortBy.value,
  search: search.value
}))

// Debounced Search Handler
const handleSearchInput = (value: string | null) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    search.value = value || ''
    page.value = 1 // Reset to first page on search
    loadData()
  }, 300)
}

// Build Query Parameters
const buildQueryParams = (): Record<string, any> => {
  const params: Record<string, any> = {
    page: page.value,
    per_page: itemsPerPage.value,
  }

  // Add search if present
  if (search.value) {
    params.search = search.value
  }

  // Add sorting if present
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
      // Standard Laravel pagination
      items.value = response.data.data
      totalItems.value = response.data.total
    } else if (response.data.users && typeof response.data.meta?.total !== 'undefined') {
      // Custom response with users key
      items.value = response.data.users
      totalItems.value = response.data.meta.total
    } else if (Array.isArray(response.data)) {
      // Plain array (no pagination from backend)
      items.value = response.data
      totalItems.value = response.data.length
    } else if (response.data.data && Array.isArray(response.data.data)) {
      // Wrapped in data but no pagination info
      items.value = response.data.data
      totalItems.value = response.data.data.length
    } else {
      items.value = []
      totalItems.value = 0
    }

    // Emit count update
    emit('update:count', totalItems.value)

  } catch (error: any) {
    console.error('Error loading data:', error)
    showError(t('messages.load_error'))
    items.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

// Handle Vuetify Options Update (pagination, sorting)
const handleOptionsUpdate = (options: any) => {
  // Options contains: page, itemsPerPage, sortBy, groupBy, search
  // We already have v-model bindings, so just reload data
  loadData()
}

// Handle Row Click
const handleRowClick = (event: Event, row: any) => {
  emit('itemSelected', row.item)
}

// Refresh function that can be called from parent
const refresh = () => {
  loadData()
}

// Reset to first page and refresh
const reset = () => {
  page.value = 1
  search.value = ''
  searchInput.value = ''
  sortBy.value = []
  loadData()
}

// Filter Methods
const applyFilterState = (state: TableFilterState) => {
  page.value = state.page || 1
  itemsPerPage.value = state.itemsPerPage || props.defaultItemsPerPage
  sortBy.value = state.sortBy || []
  search.value = state.search || ''
  searchInput.value = state.search || ''
  loadData()
}

const resetFilters = () => {
  filterStore.clearActiveFilter()
  reset()
}

const openSaveDialog = (filter: TableFilter | null) => {
  editingFilter.value = filter
  settingsMenuOpen.value = false
  saveDialogOpen.value = true
}

const handleQuickFilterClick = (filter: TableFilter) => {
  if (activeFilterId.value === filter.id) {
    filterStore.clearActiveFilter()
    reset()
  } else {
    filterStore.setActiveFilter(filter.id)
    applyFilterState(filter.filter_state)
  }
}

const handleFilterClick = (filter: TableFilter) => {
  if (activeFilterId.value === filter.id) {
    filterStore.clearActiveFilter()
    reset()
  } else {
    filterStore.setActiveFilter(filter.id)
    applyFilterState(filter.filter_state)
  }
  settingsMenuOpen.value = false
}

const handleDeleteFilter = async (filter: TableFilter) => {
  try {
    await filterStore.deleteFilter(filter.id, props.tableKey)
    showSuccess('Filter erfolgreich gelöscht')
  } catch (e: any) {
    showError(e.message || 'Fehler beim Löschen')
  }
}

const handleResetFilters = () => {
  filterStore.clearActiveFilter()
  reset()
  settingsMenuOpen.value = false
}

const handleResetColumnOrder = () => {
  resetColumnOrder()
  settingsMenuOpen.value = false
}

const handleResetColumnWidths = () => {
  resetColumnWidths()
  settingsMenuOpen.value = false
}

const handleResetAllSettings = async () => {
  await resetAllSettings()
  settingsMenuOpen.value = false
}

const handleSaveFilter = async (data: { name: string; color: string; showAsButton: boolean }) => {
  if (!props.tableKey) {
    showError('Kein Table-Key definiert')
    return
  }

  try {
    if (editingFilter.value) {
      // Update existing filter
      await filterStore.updateFilter(editingFilter.value.id, props.tableKey, {
        name: data.name,
        color: data.color,
        show_as_button: data.showAsButton,
        filter_state: currentFilterState.value
      })
      showSuccess('Filter erfolgreich aktualisiert')
    } else {
      // Create new filter
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

// Watch for endpoint changes
watch(() => props.apiEndpoint, () => {
  page.value = 1
  loadData()
})

// Event handler für table refresh
const handleTableRefresh = () => {
  refresh()
}

// Listen for refresh events
onMounted(async () => {
  // Load table settings (column order & widths) from backend
  if (props.tableKey) {
    await loadTableSettings()
  }

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

// Cleanup
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

<style scoped>
:deep(.v-data-table__wrapper) {
  min-height: 400px;
  overflow-x: auto;
}

:deep(.v-data-table) {
  table-layout: fixed;
}

:deep(.v-data-table > .v-data-table__wrapper > table) {
  table-layout: fixed;
  width: max-content;
  min-width: 100%;
}

:deep(.v-data-table-row:hover) {
  cursor: pointer;
}

/* Drag & Drop Header Styles */
.adt-header {
  user-select: none;
  transition: background-color 0.15s ease;
  padding: 12px 16px !important;
  position: relative;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.adt-header[draggable="true"] {
  cursor: grab;
}

.adt-header[draggable="true"]:active {
  cursor: grabbing;
}

.adt-header--dragging {
  opacity: 0.4;
  background-color: rgba(var(--v-theme-primary), 0.15) !important;
}

.adt-header--drag-over {
  background-color: rgba(var(--v-theme-primary), 0.1) !important;
}

.adt-header--drag-over::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background-color: rgb(var(--v-theme-primary));
}

.adt-header-content {
  display: flex;
  align-items: center;
  gap: 4px;
}

.adt-drag-handle {
  opacity: 0.3;
  transition: opacity 0.15s ease;
  cursor: grab;
  color: rgba(var(--v-theme-on-surface), 0.6);
  flex-shrink: 0;
}

.adt-header:hover .adt-drag-handle {
  opacity: 0.8;
}

.adt-drag-handle:hover {
  opacity: 1 !important;
  color: rgb(var(--v-theme-primary));
}

.adt-header-title {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  color: rgb(var(--v-theme-primary));
}

/* Column Resize Handle */
.adt-resize-handle {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 6px;
  cursor: col-resize;
  background: transparent;
  z-index: 10;
  transition: background-color 0.15s ease;
}

.adt-resize-handle:hover {
  background-color: rgba(var(--v-theme-primary), 0.3);
}

.adt-resize-handle::after {
  content: '';
  position: absolute;
  right: 2px;
  top: 25%;
  bottom: 25%;
  width: 2px;
  background-color: transparent;
  border-radius: 1px;
  transition: background-color 0.15s ease;
}

.adt-resize-handle:hover::after {
  background-color: rgb(var(--v-theme-primary));
}

.adt-header--resizing {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}

.adt-header--resizing .adt-resize-handle {
  background-color: rgba(var(--v-theme-primary), 0.3);
}

.adt-header--resizing .adt-resize-handle::after {
  background-color: rgb(var(--v-theme-primary));
}
</style>
