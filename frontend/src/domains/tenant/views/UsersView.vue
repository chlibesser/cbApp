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

      <!-- TableToolbar in View-Toolbar (Filter via Provide/Inject) -->
      <TableToolbar
        :table-key="TABLE_KEY"
        :enable-create="canInviteUsers"
        create-button-text="Benutzer einladen"
        :has-custom-settings="tableRef?.hasCustomSettings"
        @create="handleCreateUser"
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
        v-model:column-filters="columnFilters"
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
            variant="tonal"
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
import { ref, onMounted } from 'vue'
import { useApi } from '@/core/api'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { useTableFilters, provideFilterContext } from '@/shared/composables'
import { DataTableCore, TableToolbar, FilterSaveDialog } from '@/shared/components'
import GenericRSDWrapper from '@/shared/components/GenericRSDWrapper.vue'
import type { TableColumn } from '@/types/table'

const TABLE_KEY = 'tenant.users'
const API_ENDPOINT = '/tenant/users'

// Stores & Composables
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()
const api = useApi()

// Table Ref
const tableRef = ref<InstanceType<typeof DataTableCore> | null>(null)

// Data State (view-spezifisch)
const items = ref<any[]>([])
const totalItems = ref(0)
const totalUsers = ref(0)
const loading = ref(false)

// Permissions (view-spezifisch)
const canInviteUsers = ref(true)
const canManageUsers = ref(true)

// Table Configuration (view-spezifisch)
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

// Load Data from API (view-spezifisch)
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

// Filter Composable (zentralisierte Filter-Logik)
const filterState = useTableFilters({
  tableKey: TABLE_KEY,
  tableRef,
  loadData
})

// Provide filter context für TableToolbar (Provide/Inject Pattern)
provideFilterContext(filterState)

// Destructure nur was in der View benötigt wird
const {
  page,
  itemsPerPage,
  sortBy,
  columnFilters,
  saveDialogOpen,
  editingFilter,
  currentFilterState,
  handleSaveFilter,
  buildQueryParams,
  initFilters
} = filterState

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

  // Load filters (via Composable)
  await initFilters()

  // Load data
  await loadData()
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

.gap-1 {
  gap: 4px;
}
</style>
