<template>
  <div class="advanced-data-table">
    <!-- Toolbar -->
    <v-card class="mb-4" flat>
      <v-card-text class="d-flex align-center gap-3 py-2">
        <!-- Search -->
        <v-text-field
          v-if="showSearch"
          v-model="searchQuery"
          :placeholder="searchPlaceholder"
          variant="outlined"
          density="compact"
          clearable
          hide-details
          prepend-inner-icon="mdi-magnify"
          class="search-field"
          @update:model-value="handleSearch"
        />

        <v-spacer />

        <!-- Filters Toggle -->
        <v-btn
          v-if="showFilters && hasFilterableColumns"
          :variant="filtersVisible ? 'flat' : 'outlined'"
          :color="filtersVisible ? 'primary' : undefined"
          @click="filtersVisible = !filtersVisible"
        >
          <v-icon>mdi-filter</v-icon>
          Filter
          <v-badge
            v-if="activeFiltersCount > 0"
            :content="activeFiltersCount"
            color="error"
            inline
          />
        </v-btn>

        <!-- Column Settings -->
        <v-menu v-if="showColumnSettings" offset-y>
          <template #activator="{ props }">
            <v-btn v-bind="props" variant="outlined">
              <v-icon>mdi-table-cog</v-icon>
              Spalten
            </v-btn>
          </template>
          <v-card min-width="250">
            <v-card-title>Spalten anzeigen</v-card-title>
            <v-card-text>
              <v-checkbox
                v-for="column in columns"
                :key="column.key"
                v-model="visibleColumns[column.key]"
                :label="column.title"
                density="compact"
                hide-details
              />
            </v-card-text>
          </v-card>
        </v-menu>

        <!-- Refresh Button -->
        <v-btn
          variant="outlined"
          :loading="loading"
          @click="refresh"
        >
          <v-icon>mdi-refresh</v-icon>
          Aktualisieren
        </v-btn>

        <!-- Create Button -->
        <slot name="toolbar-actions">
          <v-btn
            color="primary"
            @click="$emit('create')"
          >
            <v-icon>mdi-plus</v-icon>
            Erstellen
          </v-btn>
        </slot>
      </v-card-text>

      <!-- Filter Row -->
      <v-expand-transition>
        <div v-if="filtersVisible && hasFilterableColumns" class="filter-row">
          <v-card-text class="pt-0">
            <v-row dense>
              <v-col
                v-for="column in filterableColumns"
                :key="`filter-${column.key}`"
                cols="12"
                sm="6"
                md="4"
                lg="3"
              >
                <ColumnFilter
                  :column="column"
                  :model-value="getFilterValue(column.key)"
                  @update:model-value="(value) => setFilter(column.key, value)"
                />
              </v-col>
            </v-row>
            
            <!-- Clear Filters Button -->
            <div v-if="activeFiltersCount > 0" class="mt-3">
              <v-btn
                variant="text"
                color="error"
                size="small"
                @click="clearFilters"
              >
                <v-icon size="small">mdi-filter-remove</v-icon>
                Filter zurücksetzen
              </v-btn>
            </div>
          </v-card-text>
        </div>
      </v-expand-transition>
    </v-card>

    <!-- Data Table -->
    <v-card>
      <v-data-table
        :headers="tableHeaders"
        :items="items"
        :loading="loading"
        :items-length="totalItems"
        :items-per-page="state.itemsPerPage"
        :page="state.page"
        :sort-by="sortBy"
        :must-sort="false"
        :item-key="itemKey"
        :density="dense ? 'compact' : 'default'"
        :height="height"
        :no-data-text="emptyText"
        :loading-text="'Daten werden geladen...'"
        :items-per-page-text="'Einträge pro Seite:'"
        :items-per-page-options="itemsPerPageOptions"
        :show-select="allowRowSelection"
        @update:options="handleTableUpdate"
        @click:row="handleRowClick"
        @update:model-value="handleRowSelection"
      >
        <!-- Header Slots -->
        <template
          v-for="column in visibleTableColumns"
          :key="`header-${column.key}`"
          #[`header.${column.key}`]="{ column: headerColumn }"
        >
          <div class="table-header">
            <span>{{ headerColumn.title }}</span>
            <v-icon
              v-if="isSorted(column.key)"
              :icon="getSortIcon(column.key)"
              size="small"
              class="ml-1"
            />
          </div>
        </template>

        <!-- Data Cell Slots -->
        <template
          v-for="column in visibleTableColumns"
          :key="`item-${column.key}`"
          #[`item.${column.key}`]="{ item }"
        >
          <slot
            :name="`item.${column.key}`"
            :item="item"
            :value="getItemValue(item, column.key)"
            :column="column"
          >
            <TableCell
              :column="column"
              :value="getItemValue(item, column.key)"
            />
          </slot>
        </template>

        <!-- Actions Slot -->
        <template v-if="$slots.actions" #item.actions="{ item }">
          <slot name="actions" :item="item" />
        </template>

        <!-- No Data Slot -->
        <template #no-data>
          <div class="text-center py-8">
            <v-icon size="48" color="grey-lighten-1">mdi-database-search</v-icon>
            <p class="text-h6 mt-2">{{ emptyText }}</p>
            <p class="text-body-2 text-grey">
              Versuchen Sie, Ihre Filter zu ändern oder neue Daten hinzuzufügen.
            </p>
          </div>
        </template>

        <!-- Loading Slot -->
        <template #loading>
          <div class="text-center py-8">
            <v-progress-circular indeterminate size="48" />
            <p class="mt-2">Daten werden geladen...</p>
          </div>
        </template>

        <!-- Bottom Pagination -->
        <template #bottom>
          <div class="v-data-table-footer">
            <v-pagination
              v-if="totalPages > 1"
              :model-value="currentPage"
              :length="totalPages"
              :total-visible="7"
              @update:model-value="setPage"
            />
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Error Snackbar -->
    <v-snackbar
      v-model="showError"
      color="error"
      timeout="5000"
      location="bottom"
    >
      {{ error }}
      <template #actions>
        <v-btn variant="text" @click="showError = false">
          Schließen
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, useSlots } from 'vue'
import { useAdvancedTable } from '@/shared/composables/useAdvancedTable'
import ColumnFilter from './ColumnFilter.vue'
import TableCell from './TableCell.vue'
import type { TableColumn, TableProps, TableEmits } from '../../../types/table'
import type { EntityConfig } from '../../../types/entity'
import { entityFieldsToColumns } from '../../../types/entity'

// Props
const props = withDefaults(defineProps<TableProps>(), {
  itemKey: 'id',
  showSearch: true,
  showFilters: true,
  showPagination: true,
  showColumnSettings: true,
  allowColumnResize: true,
  allowRowSelection: false,
  dense: false,
  errorText: 'Fehler beim Laden der Daten'
})

// Emits
const emit = defineEmits<TableEmits>()

// Slots
const slots = useSlots()

// Local state
const searchQuery = ref('')
const filtersVisible = ref(false)
const showError = ref(false)
const visibleColumns = ref<Record<string, boolean>>({})
const clickTimer = ref<number | null>(null)
const clickDelay = 300

// Computed columns - either from direct prop or from entityConfig
const columns = computed((): TableColumn[] => {
  if (props.columns) {
    return props.columns
  }
  
  if (props.entityConfig) {
    return entityFieldsToColumns(props.entityConfig.fields)
  }
  
  return []
})

// Computed API endpoint
const apiEndpoint = computed(() => {
  return props.apiEndpoint || props.entityConfig?.apiEndpoint || ''
})

// Computed search placeholder
const searchPlaceholder = computed(() => {
  return props.searchPlaceholder || props.entityConfig?.searchPlaceholder || 'Suchen...'
})

// Computed empty text
const emptyText = computed(() => {
  return props.emptyText || props.entityConfig?.emptyText || 'Keine Daten gefunden'
})

// Computed items per page
const itemsPerPageDefault = computed(() => {
  return props.itemsPerPage || props.entityConfig?.itemsPerPage || 25
})

// Initialize visible columns
const initializeVisibleColumns = () => {
  columns.value.forEach(column => {
    visibleColumns.value[column.key] = column.visible !== false
  })
}

// Use advanced table composable
const {
  items,
  loading,
  error,
  totalItems,
  totalPages,
  currentPage,
  state,
  itemsPerPageOptions,
  setPage,
  setItemsPerPage,
  setSorting,
  setFilter,
  clearFilters,
  search,
  refresh,
  getSortDirection,
  isSorted,
  getFilterValue
} = useAdvancedTable(apiEndpoint.value, columns.value, {
  itemKey: props.itemKey,
  defaultItemsPerPage: itemsPerPageDefault.value
})

// Computed properties
const visibleTableColumns = computed(() => {
  return columns.value.filter(column => visibleColumns.value[column.key] !== false)
})

const tableHeaders = computed(() => {
  const headers = visibleTableColumns.value.map(column => ({
    title: column.title,
    key: column.key,
    align: column.align || 'start',
    sortable: column.sortable !== false,
    width: column.width,
    minWidth: column.minWidth || 100
  }))

  // Actions Spalte hinzufügen wenn Slot vorhanden
  if (slots.actions) {
    headers.push({
      title: 'Aktionen',
      key: 'actions',
      align: 'end',
      sortable: false,
      width: 120
    })
  }

  return headers
})

const filterableColumns = computed(() => {
  return columns.value.filter(column => column.filterable !== false)
})

const hasFilterableColumns = computed(() => filterableColumns.value.length > 0)

const activeFiltersCount = computed(() => {
  return Object.keys(state.filters).length
})

const sortBy = computed(() => {
  if (!state.sortBy) return []
  return [{ key: state.sortBy, order: state.sortDirection || 'asc' }]
})

// Methods
const handleSearch = (query: string) => {
  search(query || '')
}

const handleTableUpdate = async (options: any) => {
  // Pagination
  if (options.page !== state.page) {
    setPage(options.page)
  }
  
  if (options.itemsPerPage !== state.itemsPerPage) {
    setItemsPerPage(options.itemsPerPage)
  }

  // Sortierung
  if (options.sortBy?.length > 0) {
    const sort = options.sortBy[0]
    setSorting(sort.key, sort.order)
  } else if (state.sortBy) {
    setSorting(state.sortBy, null)
  }
}

const handleRowClick = (event: Event, { item }: { item: any }) => {
  if (clickTimer.value) {
    // Double click detected
    clearTimeout(clickTimer.value)
    clickTimer.value = null
    emit('item-double-click', item)
  } else {
    // Single click - wait to see if double click follows
    clickTimer.value = window.setTimeout(() => {
      emit('item-selected', item)
      clickTimer.value = null
    }, clickDelay)
  }
}

const handleRowSelection = (selectedItems: any[]) => {
  emit('items-selected', selectedItems)
}

const getSortIcon = (field: string) => {
  const direction = getSortDirection(field)
  if (direction === 'asc') return 'mdi-arrow-up'
  if (direction === 'desc') return 'mdi-arrow-down'
  return 'mdi-sort'
}

const getItemValue = (item: any, key: string) => {
  return key.split('.').reduce((obj, k) => obj?.[k], item)
}

// Watch for error changes
watch(error, (newError) => {
  if (newError) {
    showError.value = true
  }
})

// Watch for total items changes and emit count
watch(totalItems, (newCount) => {
  emit('update:count', newCount)
}, { immediate: true })

// Initialize component
initializeVisibleColumns()
</script>

<style scoped>
.advanced-data-table {
  width: 100%;
}

.search-field {
  max-width: 300px;
}

.filter-row {
  border-top: 1px solid rgb(var(--v-border-color));
  background-color: rgb(var(--v-theme-surface-variant));
}

.table-header {
  display: flex;
  align-items: center;
  font-weight: 500;
}

.v-data-table-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  border-top: 1px solid rgb(var(--v-border-color));
}

:deep(.v-data-table__wrapper) {
  border-radius: 8px;
}

:deep(.v-data-table-row--clickable:hover) {
  background-color: rgb(var(--v-theme-surface-variant)) !important;
}

:deep(.v-pagination__item) {
  margin: 0 1px;
}

.gap-3 {
  gap: 12px;
}
</style>