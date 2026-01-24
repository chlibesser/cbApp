<template>
  <div class="view-container">
    <!-- Toolbar -->
    <v-toolbar flat color="primary" variant="outlined" rounded density="compact" class="view-toolbar">
      <v-toolbar-title class="text-h5 font-weight-bold view-title">
        Benutzerverwaltung
      </v-toolbar-title>
      <v-chip
        v-if="totalUsers > 0"
        size="small"
        color="primary"
        variant="tonal"
        class="ml-2"
      >
        {{ totalUsers }} Benutzer
      </v-chip>
      <v-spacer />

      <!-- TableToolbar in View-Toolbar -->
      <TableToolbar
        v-model="search"
        :table-key="TABLE_KEY"
        :enable-filters="true"
        :enable-create="canInviteUsers"
        create-button-text="Benutzer einladen"
        :filters="filters"
        :button-filters="buttonFilters"
        :active-filter-id="activeFilterId"
        :has-custom-column-order="tableRef?.hasCustomColumnOrder"
        :has-custom-column-widths="tableRef?.hasCustomColumnWidths"
        :has-custom-settings="tableRef?.hasCustomSettings"
        @create="handleCreateUser"
        @filter-apply="handleFilterApply"
        @filter-reset="handleFilterReset"
        @filter-save="openSaveDialog(null)"
        @filter-edit="openSaveDialog"
        @filter-delete="handleDeleteFilter"
        @reset-column-order="tableRef?.resetColumnOrder()"
        @reset-column-widths="tableRef?.resetColumnWidths()"
        @reset-all-settings="tableRef?.resetAllSettings()"
      />
    </v-toolbar>

    <!-- Scrollable Content -->
    <div class="view-content">
      <DataTableCore
        ref="tableRef"
        :table-key="TABLE_KEY"
        :columns="tableColumns"
        :items="items"
        :total-items="totalItems"
        :loading="loading"
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        v-model:sort-by="sortBy"
        @row-click="handleUserSelected"
        @options-update="loadData"
      >
        <!-- Status Cell -->
        <template #item.status="{ item, value }">
          <v-chip
            :color="getStatusColor(value)"
            size="small"
            variant="tonal"
          >
            {{ getStatusLabel(value) }}
          </v-chip>
        </template>

        <!-- System Role Cell -->
        <template #item.system_role="{ item, value }">
          <v-chip
            :color="getRoleColor(value)"
            size="small"
            variant="outlined"
          >
            {{ getRoleLabel(value) }}
          </v-chip>
        </template>

        <!-- Last Login Cell -->
        <template #item.last_login_at="{ item, value }">
          <span v-if="value" class="text-body-2">
            {{ formatDateTime(value) }}
          </span>
          <span v-else class="text-body-2 text-medium-emphasis">
            Nie angemeldet
          </span>
        </template>

        <!-- Actions Cell -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center gap-1">
            <v-tooltip text="Details anzeigen">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click.stop="handleViewUser(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip text="Bearbeiten">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click.stop="handleEditUser(item)"
                  :disabled="!canManageUser(item)"
                />
              </template>
            </v-tooltip>

            <!-- Status Toggle -->
            <v-tooltip :text="item.is_active ? 'Deaktivieren' : 'Aktivieren'">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  :icon="item.is_active ? 'mdi-account-cancel' : 'mdi-account-check'"
                  size="small"
                  variant="text"
                  @click.stop="handleToggleStatus(item)"
                  :disabled="!canManageUser(item)"
                  :color="item.is_active ? 'error' : 'success'"
                />
              </template>
            </v-tooltip>

            <!-- Resend Invitation (nur für eingeladene User) -->
            <v-tooltip
              v-if="item.status === 'invited'"
              text="Einladung erneut senden"
            >
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-email-send"
                  size="small"
                  variant="text"
                  @click.stop="handleResendInvitation(item)"
                  :disabled="!canInviteUsers"
                  color="primary"
                />
              </template>
            </v-tooltip>

            <v-menu>
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-dots-vertical"
                  size="small"
                  variant="text"
                />
              </template>
              <v-list density="compact">
                <v-list-item
                  @click="handleDeleteUser(item)"
                  :disabled="!canDeleteUser(item)"
                >
                  <template #prepend>
                    <v-icon icon="mdi-delete" color="error" />
                  </template>
                  <v-list-item-title class="text-error">
                    Entfernen
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
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

    <!-- RSD Wrapper für Create/Edit/View -->
    <GenericRSDWrapper />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/core/api'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { useTableFilterStore } from '@/infrastructure/stores/tableFilterStore'
import { DataTableCore, TableToolbar, FilterSaveDialog } from '@/shared/components'
import GenericRSDWrapper from '@/shared/components/GenericRSDWrapper.vue'
import type { TableColumn } from '@/types/table'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

const TABLE_KEY = 'tenant.users'
const API_ENDPOINT = '/tenant/users'

// Stores & Composables
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()
const filterStore = useTableFilterStore()
const api = useApi()

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
const items = ref<any[]>([])
const loading = ref(false)
const totalUsers = ref(0)

// Filter State
const saveDialogOpen = ref(false)
const editingFilter = ref<TableFilter | null>(null)

// Permissions
const canInviteUsers = ref(true)
const canManageUsers = ref(true)

// Filter Computed
const filters = computed(() => filterStore.getFiltersForTable(TABLE_KEY))
const buttonFilters = computed(() => filterStore.getButtonFilters(TABLE_KEY))
const activeFilterId = computed(() => filterStore.activeFilterId)

// Current Filter State
const currentFilterState = computed((): TableFilterState => ({
  page: page.value,
  itemsPerPage: itemsPerPage.value,
  sortBy: sortBy.value,
  search: search.value
}))

// Table Configuration
const tableColumns: TableColumn[] = [
  {
    key: 'full_name',
    title: 'Name',
    type: 'text',
    sortable: true,
    width: 200
  },
  {
    key: 'email',
    title: 'E-Mail',
    type: 'email',
    sortable: true,
    width: 250
  },
  {
    key: 'system_role',
    title: 'Rolle',
    type: 'select',
    sortable: true,
    filterable: true,
    width: 150,
    filterOptions: [
      { value: 'tenant_admin', text: 'Tenant Admin' },
      { value: 'tenant_member', text: 'Tenant Mitglied' }
    ]
  },
  {
    key: 'status',
    title: 'Status',
    type: 'select',
    sortable: true,
    filterable: true,
    width: 120,
    filterOptions: [
      { value: 'active', text: 'Aktiv' },
      { value: 'invited', text: 'Eingeladen' },
      { value: 'inactive', text: 'Inaktiv' }
    ]
  },
  {
    key: 'last_login_at',
    title: 'Letzter Login',
    type: 'datetime',
    sortable: true,
    width: 180
  },
  {
    key: 'actions',
    title: 'Aktionen',
    sortable: false,
    width: 150
  }
]

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
    const url = `${API_ENDPOINT}?${queryString}`

    const response = await api.get(url)

    if (response.data.users && typeof response.data.meta?.total !== 'undefined') {
      items.value = response.data.users
      totalItems.value = response.data.meta.total
      totalUsers.value = response.data.meta.total
    } else if (response.data.data && typeof response.data.total !== 'undefined') {
      items.value = response.data.data
      totalItems.value = response.data.total
      totalUsers.value = response.data.total
    } else if (Array.isArray(response.data)) {
      items.value = response.data
      totalItems.value = response.data.length
      totalUsers.value = response.data.length
    } else {
      items.value = []
      totalItems.value = 0
      totalUsers.value = 0
    }
  } catch (error: any) {
    console.error('Error loading data:', error)
    layoutStore.showError('Fehler beim Laden der Daten')
    items.value = []
    totalItems.value = 0
    totalUsers.value = 0
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

// Event Handlers
const handleCreateUser = () => {
  rsdStore.openCreate('user')
}

const handleUserSelected = (user: any) => {
  handleViewUser(user)
}

const handleViewUser = (user: any) => {
  rsdStore.openView('user', user)
}

const handleEditUser = (user: any) => {
  rsdStore.openEdit('user', user)
}

const handleToggleStatus = async (user: any) => {
  try {
    loading.value = true

    const action = user.is_active ? 'deactivate' : 'activate'
    await api.patch(`/tenant/users/${user.id}/${action}`)

    layoutStore.showSuccess(
      user.is_active
        ? 'Benutzer wurde deaktiviert'
        : 'Benutzer wurde aktiviert'
    )

    loadData()
  } catch (error) {
    layoutStore.showError('Fehler beim Ändern des Status')
  } finally {
    loading.value = false
  }
}

const handleResendInvitation = async (user: any) => {
  try {
    loading.value = true

    await api.post(`/tenant/users/${user.id}/resend-invitation`)
    layoutStore.showSuccess('Einladung wurde erneut versendet')
  } catch (error) {
    layoutStore.showError('Fehler beim Versenden der Einladung')
  } finally {
    loading.value = false
  }
}

const handleDeleteUser = async (user: any) => {
  if (!confirm(`Möchten Sie ${user.full_name} wirklich entfernen?`)) {
    return
  }

  try {
    loading.value = true

    await api.delete(`/tenant/users/${user.id}`)
    layoutStore.showSuccess('Benutzer wurde entfernt')

    loadData()
  } catch (error) {
    layoutStore.showError('Fehler beim Entfernen des Benutzers')
  } finally {
    loading.value = false
  }
}

// Helper Functions
const canManageUser = (user: any): boolean => {
  return canManageUsers.value && user.id !== 'current-user-id'
}

const canDeleteUser = (user: any): boolean => {
  return canManageUser(user)
}

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'active': return 'success'
    case 'invited': return 'warning'
    case 'inactive': return 'error'
    default: return 'grey'
  }
}

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'active': return 'Aktiv'
    case 'invited': return 'Eingeladen'
    case 'inactive': return 'Inaktiv'
    default: return status
  }
}

const getRoleColor = (role: string): string => {
  switch (role) {
    case 'tenant_admin': return 'primary'
    case 'tenant_member': return 'info'
    default: return 'grey'
  }
}

const getRoleLabel = (role: string): string => {
  switch (role) {
    case 'tenant_admin': return 'Tenant Admin'
    case 'tenant_member': return 'Tenant Mitglied'
    default: return role
  }
}

const formatDateTime = (datetime: string): string => {
  return new Date(datetime).toLocaleString('de-DE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
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
  padding: 16px;
  border: 2px solid rgba(25, 118, 210, 0.8);
  border-radius: 4px;
  background-color: rgba(25, 118, 210, 0.02);
  display: flex;
  flex-direction: column;
}

.view-title {
  color: rgba(25, 118, 210, 0.8) !important;
}

.gap-1 {
  gap: 4px;
}
</style>
