<template>
  <v-data-table-server
    v-model:items-per-page="localItemsPerPage"
    v-model:page="localPage"
    v-model:sort-by="localSortBy"
    :headers="orderedHeaders"
    :items="items"
    :items-length="totalItems"
    :loading="loading"
    item-value="id"
    class="elevation-0 dtc-table"
    :items-per-page-options="itemsPerPageOptions"
    fixed-header
    fixed-footer
    height="100%"
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
            'dtc-header',
            { 'dtc-header--dragging': draggedIndex === index },
            { 'dtc-header--drag-over': dragOverIndex === index && dragOverIndex !== draggedIndex },
            { 'dtc-header--resizing': resizingColumn === header.key }
          ]"
          :style="getHeaderStyle(header)"
          :draggable="header.key !== 'actions' && !resizingColumn"
          @dragstart.stop="handleDragStart($event, index, header.key)"
          @dragend.stop="handleDragEnd"
          @dragover.prevent="handleDragOver($event, index)"
          @dragleave="handleDragLeave"
          @drop.prevent="handleDrop($event, index)"
        >
          <div class="dtc-header-content">
            <!-- Drag Handle -->
            <v-icon
              v-if="header.key !== 'actions'"
              class="dtc-drag-handle"
              size="x-small"
            >
              mdi-drag-vertical
            </v-icon>

            <!-- Header Content -->
            <span
              class="dtc-header-title"
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
            class="dtc-resize-handle"
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
      </div>
    </template>
  </v-data-table-server>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useTranslations } from '@/core/localization/composables/useTranslations'
import { useTableSettingsStore } from '@/infrastructure/stores/tableSettingsStore'
import { useNotifications } from '@/core/composables/useNotifications'
import type { TableColumn } from '@/types/table'

const { t } = useTranslations('admin.common')
const { showSuccess, showError } = useNotifications()

interface Props {
  tableKey: string
  columns: TableColumn[]
  items: any[]
  totalItems: number
  loading?: boolean
  page?: number
  itemsPerPage?: number
  sortBy?: Array<{ key: string; order: 'asc' | 'desc' }>
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  page: 1,
  itemsPerPage: 10,
  sortBy: () => []
})

const emit = defineEmits<{
  'update:page': [page: number]
  'update:itemsPerPage': [count: number]
  'update:sortBy': [sortBy: Array<{ key: string; order: 'asc' | 'desc' }>]
  'row-click': [item: any]
  'options-update': []
}>()

// Store
const settingsStore = useTableSettingsStore()

// Local state for v-model bindings
const localPage = ref(props.page)
const localItemsPerPage = ref(props.itemsPerPage)
const localSortBy = ref(props.sortBy)

// Sync props to local state
watch(() => props.page, (val) => { localPage.value = val })
watch(() => props.itemsPerPage, (val) => { localItemsPerPage.value = val })
watch(() => props.sortBy, (val) => { localSortBy.value = val })

// Emit changes
watch(localPage, (val) => emit('update:page', val))
watch(localItemsPerPage, (val) => emit('update:itemsPerPage', val))
watch(localSortBy, (val) => emit('update:sortBy', val))

// Column Order State
const columnOrder = ref<string[]>([])
const draggedIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)
const draggedKey = ref<string | null>(null)

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

// Check if custom column order exists
const hasCustomColumnOrder = computed(() => {
  if (!columnOrder.value.length) return false
  const defaultOrder = props.columns.map(c => c.key)
  return JSON.stringify(columnOrder.value) !== JSON.stringify(defaultOrder)
})

// Check if custom column widths exist
const hasCustomColumnWidths = computed(() => {
  return Object.keys(columnWidths.value).length > 0
})

// Check if any custom settings exist
const hasCustomSettings = computed(() =>
  hasCustomColumnOrder.value || hasCustomColumnWidths.value
)

// Helper to translate title if it's a translation key
const translateTitle = (title: string): string => {
  if (title && title.includes('.') && !title.includes(' ')) {
    const translated = t(title)
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
  if (!columnOrder.value.length) {
    return computedHeaders.value
  }

  const headerMap = new Map(computedHeaders.value.map(h => [h.key, h]))
  const ordered: typeof computedHeaders.value = []

  for (const key of columnOrder.value) {
    const header = headerMap.get(key)
    if (header) {
      ordered.push(header)
      headerMap.delete(key)
    }
  }

  for (const header of headerMap.values()) {
    ordered.push(header)
  }

  return ordered
})

// Get header style with custom width
const getHeaderStyle = (header: { key: string; width?: string }) => {
  const customWidth = columnWidths.value[header.key]
  if (customWidth) {
    return { width: `${customWidth}px` }
  }
  return { width: header.width }
}

// Drag & Drop Handlers
const handleDragStart = (event: DragEvent, index: number, key: string) => {
  draggedIndex.value = index
  draggedKey.value = key

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', key)
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
  if (draggedIndex.value === null) return

  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }

  if (index !== draggedIndex.value) {
    dragOverIndex.value = index
  }
}

const handleDragLeave = () => {
  // Only reset when leaving the table
}

const handleDrop = (event: DragEvent, targetIndex: number) => {
  const sourceIndex = draggedIndex.value
  if (sourceIndex === null || sourceIndex === targetIndex) {
    handleDragEnd()
    return
  }

  const currentOrder = columnOrder.value.length
    ? [...columnOrder.value]
    : orderedHeaders.value.map(h => h.key)

  const [movedKey] = currentOrder.splice(sourceIndex, 1)
  currentOrder.splice(targetIndex, 0, movedKey)

  columnOrder.value = currentOrder
  saveColumnOrder()
  handleDragEnd()
}

// Save column order to backend
const saveColumnOrder = () => {
  if (!props.tableKey) return
  settingsStore.saveColumnOrder(props.tableKey, columnOrder.value)
}

// Column Resize Handlers
const handleResizeStart = (event: MouseEvent, columnKey: string) => {
  resizingColumn.value = columnKey
  resizeStartX.value = event.clientX

  const currentWidth = columnWidths.value[columnKey]
  if (currentWidth) {
    resizeStartWidth.value = currentWidth
  } else {
    const th = (event.target as HTMLElement).parentElement
    resizeStartWidth.value = th?.offsetWidth || 150
  }

  document.addEventListener('mousemove', handleResizeMove)
  document.addEventListener('mouseup', handleResizeEnd)
  document.body.style.cursor = 'col-resize'
  document.body.style.userSelect = 'none'
}

const handleResizeMove = (event: MouseEvent) => {
  if (!resizingColumn.value) return

  const diff = event.clientX - resizeStartX.value
  const newWidth = Math.max(20, resizeStartWidth.value + diff)

  columnWidths.value = {
    ...columnWidths.value,
    [resizingColumn.value]: newWidth
  }
}

const handleResizeEnd = () => {
  if (resizingColumn.value && props.tableKey) {
    settingsStore.saveColumnWidths(props.tableKey, columnWidths.value)
  }

  resizingColumn.value = null
  document.removeEventListener('mousemove', handleResizeMove)
  document.removeEventListener('mouseup', handleResizeEnd)
  document.body.style.cursor = ''
  document.body.style.userSelect = ''
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

// Handle Vuetify Options Update
const handleOptionsUpdate = () => {
  emit('options-update')
}

// Handle Row Click
const handleRowClick = (event: Event, row: any) => {
  emit('row-click', row.item)
}

// Reset methods
const resetColumnOrder = () => {
  columnOrder.value = []
  if (props.tableKey) {
    settingsStore.saveColumnOrder(props.tableKey, [])
  }
}

const resetColumnWidths = () => {
  columnWidths.value = {}
  if (props.tableKey) {
    settingsStore.saveColumnWidths(props.tableKey, {})
  }
}

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

// Event handler for table refresh
const handleTableRefresh = () => {
  emit('options-update')
}

// Lifecycle
onMounted(async () => {
  if (props.tableKey) {
    await loadTableSettings()
  }
  window.addEventListener('table-refresh', handleTableRefresh)
})

onUnmounted(() => {
  window.removeEventListener('table-refresh', handleTableRefresh)
})

// Expose methods and state for parent components
defineExpose({
  resetColumnOrder,
  resetColumnWidths,
  resetAllSettings,
  hasCustomColumnOrder,
  hasCustomColumnWidths,
  hasCustomSettings
})
</script>

<style scoped>
/* Table Container - fill available space */
.dtc-table {
  display: flex;
  flex-direction: column;
  height: 100%;
}

:deep(.v-data-table__wrapper) {
  flex: 1;
  overflow: auto;
}

:deep(.v-data-table > .v-data-table__wrapper > table) {
  table-layout: fixed;
  width: max-content;
  min-width: 100%;
}

/* Fixed header styling */
:deep(.v-data-table--fixed-header > .v-data-table__wrapper > table > thead > tr > th) {
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.12);
}

/* Fixed footer styling */
:deep(.v-data-table-footer) {
  background: rgb(var(--v-theme-surface));
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

:deep(.v-data-table-row:hover) {
  cursor: pointer;
}

/* Drag & Drop Header Styles */
.dtc-header {
  user-select: none;
  transition: background-color 0.15s ease;
  padding: 12px 16px !important;
  position: relative;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dtc-header[draggable="true"] {
  cursor: grab;
}

.dtc-header[draggable="true"]:active {
  cursor: grabbing;
}

.dtc-header--dragging {
  opacity: 0.4;
  background-color: rgba(var(--v-theme-primary), 0.15) !important;
}

.dtc-header--drag-over {
  background-color: rgba(var(--v-theme-primary), 0.1) !important;
}

.dtc-header--drag-over::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background-color: rgb(var(--v-theme-primary));
}

.dtc-header-content {
  display: flex;
  align-items: center;
  gap: 4px;
}

.dtc-drag-handle {
  opacity: 0.3;
  transition: opacity 0.15s ease;
  cursor: grab;
  color: rgba(var(--v-theme-on-surface), 0.6);
  flex-shrink: 0;
}

.dtc-header:hover .dtc-drag-handle {
  opacity: 0.8;
}

.dtc-drag-handle:hover {
  opacity: 1 !important;
  color: rgb(var(--v-theme-primary));
}

.dtc-header-title {
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
.dtc-resize-handle {
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

.dtc-resize-handle:hover {
  background-color: rgba(var(--v-theme-primary), 0.3);
}

.dtc-resize-handle::after {
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

.dtc-resize-handle:hover::after {
  background-color: rgb(var(--v-theme-primary));
}

.dtc-header--resizing {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}

.dtc-header--resizing .dtc-resize-handle {
  background-color: rgba(var(--v-theme-primary), 0.3);
}

.dtc-header--resizing .dtc-resize-handle::after {
  background-color: rgb(var(--v-theme-primary));
}
</style>
