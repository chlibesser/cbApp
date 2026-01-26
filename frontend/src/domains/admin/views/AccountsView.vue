<template>
  <div class="view-container">
    <!-- Toolbar -->
    <v-toolbar flat color="primary" variant="outlined" rounded density="compact" class="view-toolbar">
      <v-toolbar-title class="text-h5 font-weight-bold view-title">
        {{ $t('admin.accounts.page_title') }}
      </v-toolbar-title>
      <v-spacer />

      <!-- TableToolbar in View-Toolbar (Filter via Provide/Inject) -->
      <TableToolbar
        :table-key="TABLE_KEY"
        :enable-create="true"
        :has-custom-settings="tableRef?.hasCustomSettings"
        @create="createAccount"
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
        v-model:column-filters="columnFilters"
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
            v-if="item.system_role"
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
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useApi } from '@/core/api'
import { DataTableCore, TableToolbar, FilterSaveDialog } from '@/shared/components'
import { useTableFilters, provideFilterContext } from '@/shared/composables'
import { accountEntityConfig } from '@/config/entities'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'
import { accountService } from '../services/accountService'
import type { Account } from '../types'

const { t } = useI18n()
const api = useApi()

const TABLE_KEY = 'admin.accounts'

// Stores
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()

// Table Ref
const tableRef = ref<InstanceType<typeof DataTableCore> | null>(null)

// Data State
const items = ref<Account[]>([])
const totalItems = ref(0)
const loading = ref(false)

// Load Data from API (view-spezifisch)
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
</style>
