<template>
  <div class="view-container">
    <!-- Toolbar -->
    <v-toolbar flat color="primary" variant="outlined" rounded density="compact" class="view-toolbar">
      <v-toolbar-title class="text-h5 font-weight-bold view-title">
        {{ $t('admin.accounts.page_title') }}
      </v-toolbar-title>
      <v-spacer />

      <!-- TableToolbar in View-Toolbar -->
      <TableToolbar
        v-model="search"
        :table-key="TABLE_KEY"
        :enable-filters="true"
        :enable-create="true"
        :filters="filters"
        :button-filters="buttonFilters"
        :active-filter-id="activeFilterId"
        :has-custom-settings="tableRef?.hasCustomSettings"
        @create="createAccount"
        @filter-apply="handleFilterApply"
        @filter-reset="handleFilterReset"
        @filter-save="openSaveDialog(null)"
        @filter-update="handleUpdateFilter"
        @filter-edit="openSaveDialog"
        @filter-delete="handleDeleteFilter"
        @reset-all-settings="handleResetAllSettings"
      />
    </v-toolbar>

    <!-- Scrollable Content -->
    <div class="view-content">
      <DataTableCore
        ref="tableRef"
        :table-key="TABLE_KEY"
        :columns="accountEntityConfig.fields"
        :items="items"
        :total-items="totalItems"
        :loading="loading"
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        v-model:sort-by="sortBy"
        @row-click="handleItemSelected"
        @options-update="loadData"
      >
        <!-- Custom slot for email verification status -->
        <template #item.email_verified_at="{ item }">
          <v-chip
            :color="item.email_verified_at ? 'success' : 'warning'"
            variant="tonal"
            size="x-small"
          >
            {{ item.email_verified_at ? $t('admin.accounts.verification_status.verified') : $t('admin.accounts.verification_status.not_verified') }}
          </v-chip>
        </template>

        <!-- Custom slot for system role -->
        <template #item.system_role="{ item }">
          <v-chip
            :color="getSystemRoleColor(item.system_role)"
            variant="tonal"
            size="x-small"
          >
            {{ getSystemRoleLabel(item.system_role) }}
          </v-chip>
        </template>

        <!-- Custom slot for actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <v-btn
              icon
              size="small"
              variant="text"
              @click.stop="viewAccount(item)"
            >
              <v-icon size="18">mdi-eye</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.accounts.tooltips.view') }}</v-tooltip>
            </v-btn>

            <v-btn
              icon
              size="small"
              variant="text"
              @click.stop="editAccount(item)"
            >
              <v-icon size="18">mdi-pencil</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.accounts.tooltips.edit') }}</v-tooltip>
            </v-btn>

            <v-btn
              icon
              size="small"
              variant="text"
              color="error"
              @click.stop="deleteAccount(item)"
            >
              <v-icon size="18">mdi-delete</v-icon>
              <v-tooltip activator="parent">{{ $t('admin.accounts.tooltips.delete') }}</v-tooltip>
            </v-btn>
          </div>
        </template>
      </DataTableCore>
    </div>

    <!-- Filter Save Dialog -->
    <FilterSaveDialog
      v-model="saveDialogOpen"
      :filter-state="currentFilterState"
      :table-key="TABLE_KEY"
      :existing-filter="editingFilter"
      @save="handleSaveFilter"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useApi } from '@/core/api'
import { DataTableCore, TableToolbar, FilterSaveDialog } from '@/shared/components'
import { accountEntityConfig } from '@/config/entities'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { useTableFilterStore } from '@/infrastructure/stores/tableFilterStore'
import { accountService } from '../services/accountService'
import type { Account } from '../types'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

const { t } = useI18n()
const api = useApi()

const TABLE_KEY = 'admin.accounts'

// Stores
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()
const filterStore = useTableFilterStore()

// Table Ref
const tableRef = ref<InstanceType<typeof DataTableCore> | null>(null)

// Pagination State
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref<Array<{ key: string; order: 'asc' | 'desc' }>>([])

// Search State
const search = ref('')

// Data State
const items = ref<Account[]>([])
const loading = ref(false)

// Filter State
const saveDialogOpen = ref(false)
const editingFilter = ref<TableFilter | null>(null)

// Filter Computed
const filters = computed(() => filterStore.getFiltersForTable(TABLE_KEY))
const buttonFilters = computed(() => filterStore.getButtonFilters(TABLE_KEY))
const activeFilterId = computed(() => filterStore.activeFilterId)

// Current Filter State (inkl. Spalteneinstellungen)
const currentFilterState = computed((): TableFilterState => ({
  page: page.value,
  itemsPerPage: itemsPerPage.value,
  sortBy: sortBy.value,
  search: search.value,
  columnOrder: tableRef.value?.getColumnOrder() || [],
  columnWidths: tableRef.value?.getColumnWidths() || {}
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

  // Sortierung - Default: created_at desc für konsistente Reihenfolge
  if (sortBy.value.length > 0) {
    params.sort_by = sortBy.value[0].key
    params.sort_order = sortBy.value[0].order
  } else {
    params.sort_by = 'created_at'
    params.sort_order = 'desc'
  }

  return params
}

// Load Data from API
const loadData = async () => {
  try {
    loading.value = true

    const params = buildQueryParams()
    const queryString = new URLSearchParams(params).toString()
    const url = `${accountEntityConfig.apiEndpoint}?${queryString}`

    const response = await api.get(url)

    if (response.data.data && typeof response.data.total !== 'undefined') {
      items.value = response.data.data
      totalItems.value = response.data.total
    } else if (Array.isArray(response.data)) {
      items.value = response.data
      totalItems.value = response.data.length
    } else {
      items.value = []
      totalItems.value = 0
    }
  } catch (error: any) {
    console.error('Error loading data:', error)
    layoutStore.showError('Fehler beim Laden der Daten')
    items.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
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
  itemsPerPage.value = state.itemsPerPage || 10
  sortBy.value = state.sortBy || []
  search.value = state.search || ''

  // Spalteneinstellungen anwenden
  if (state.columnOrder && state.columnOrder.length > 0) {
    tableRef.value?.setColumnOrder(state.columnOrder)
  }
  if (state.columnWidths && Object.keys(state.columnWidths).length > 0) {
    tableRef.value?.setColumnWidths(state.columnWidths)
  }

  loadData()
}

const resetFilters = () => {
  page.value = 1
  search.value = ''
  sortBy.value = []
  loadData()
}

// Alles zurücksetzen - inkl. aktiver Filter
const handleResetAllSettings = () => {
  // Aktiven Filter deaktivieren
  filterStore.clearActiveFilter()
  // Filter-State zurücksetzen
  page.value = 1
  search.value = ''
  sortBy.value = []
  // Tabellen-Settings zurücksetzen
  tableRef.value?.resetAllSettings()
  // Daten neu laden
  loadData()
}

const openSaveDialog = (filter: TableFilter | null) => {
  editingFilter.value = filter
  saveDialogOpen.value = true
}

const handleSaveFilter = async (data: { name: string; color: string; showAsButton: boolean }) => {
  try {
    if (editingFilter.value) {
      await filterStore.updateFilter(editingFilter.value.id, TABLE_KEY, {
        name: data.name,
        color: data.color,
        show_as_button: data.showAsButton,
        filter_state: currentFilterState.value
      })
      layoutStore.showSuccess('Filter erfolgreich aktualisiert')
    } else {
      await filterStore.createFilter({
        table_key: TABLE_KEY,
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
    await filterStore.deleteFilter(filter.id, TABLE_KEY)
    layoutStore.showSuccess('Filter erfolgreich gelöscht')
  } catch (e: any) {
    layoutStore.showError(e.message || 'Fehler beim Löschen')
  }
}

// Aktiven Filter mit aktuellem State aktualisieren
const handleUpdateFilter = async () => {
  if (!activeFilterId.value) return

  const activeFilter = filters.value.find(f => f.id === activeFilterId.value)
  if (!activeFilter) return

  try {
    await filterStore.updateFilter(activeFilter.id, TABLE_KEY, {
      filter_state: currentFilterState.value
    })
    layoutStore.showSuccess('Filter erfolgreich aktualisiert')
  } catch (e: any) {
    layoutStore.showError(e.message || 'Fehler beim Aktualisieren')
  }
}

// CRUD Methods
function handleItemSelected(item: Account) {
  viewAccount(item)
}

function viewAccount(item: Account) {
  rsdStore.open('account', 'view', item)
}

function editAccount(item: Account) {
  rsdStore.open('account', 'edit', item)
}

function createAccount() {
  rsdStore.open('account', 'create')
}

async function deleteAccount(item: Account) {
  const confirmed = await new Promise<boolean>(resolve => {
    resolve(confirm(t('admin.accounts.messages.delete_confirm', { email: item.email })))
  })

  if (!confirmed) return

  try {
    await accountService.deleteAccount(item.id)
    layoutStore.showSuccess(t('admin.accounts.messages.delete_success'))
    loadData()
  } catch (error: any) {
    layoutStore.showError(error.response?.data?.message || t('admin.accounts.messages.delete_error'))
  }
}

function getSystemRoleColor(role: string): string {
  const colors: Record<string, string> = {
    global_admin: 'error',
    tenant_admin: 'warning',
    tenant_member: 'primary'
  }
  return colors[role] || 'default'
}

function getSystemRoleLabel(role: string): string {
  const labels: Record<string, string> = {
    global_admin: t('admin.accounts.roles.global_admin'),
    tenant_admin: t('admin.accounts.roles.tenant_admin'),
    tenant_member: t('admin.accounts.roles.member')
  }
  return labels[role] || role
}

// Event handler for table refresh
const handleTableRefresh = () => {
  loadData()
}

// Lifecycle
onMounted(async () => {
  window.addEventListener('table-refresh', handleTableRefresh)

  // Load filters
  try {
    await filterStore.loadFilters(TABLE_KEY)
  } catch (e) {
    console.warn('Fehler beim Laden der Filter:', e)
  }

  // Load data
  loadData()
})
</script>

<style scoped>
.view-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.view-toolbar {
  flex-shrink: 0;
  background-color: rgba(25, 118, 210, 0.05) !important;
  border: 2px solid rgba(25, 118, 210, 0.8) !important;
}

.view-content {
  flex: 1;
  min-height: 0;
  overflow: hidden;
  margin-top: 8px;
  border: 2px solid rgba(25, 118, 210, 0.8);
  border-radius: 4px;
  background-color: rgba(25, 118, 210, 0.02);
  display: flex;
  flex-direction: column;
}

.view-title {
  color: rgba(25, 118, 210, 0.8) !important;
}
</style>
