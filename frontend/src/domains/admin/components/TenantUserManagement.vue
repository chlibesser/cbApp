<template>
  <div class="tenant-user-management">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2">mdi-account-multiple</v-icon>
        {{ $t('admin.tenants.components.user_management.title', { name: tenant?.name }) }}
      </v-card-title>

      <v-card-text>
        <!-- Tenant Info -->
        <v-alert
          v-if="tenant"
          type="info"
          variant="tonal"
          class="mb-4"
        >
          <div class="d-flex align-center">
            <div>
              <strong>{{ tenant.name }}</strong> ({{ tenant.slug }})
              <br>
              <small>{{ $t('admin.tenants.components.user_management.users_assigned', { count: tenant.users_count || 0 }) }}</small>
            </div>
          </div>
        </v-alert>

        <!-- Toolbar -->
        <div class="d-flex justify-space-between align-center mb-4">
          <v-text-field
            v-model="searchQuery"
            :placeholder="$t('admin.common.buttons.search')"
            variant="outlined"
            prepend-inner-icon="mdi-magnify"
            density="compact"
            style="min-width: 200px"
            clearable
            hide-details
            color="primary"
            base-color="primary"
          />

          <v-btn
            color="primary"
            @click="showAssignDialog = true"
            :disabled="loading"
          >
            <v-icon class="mr-1">mdi-account-plus</v-icon>
            {{ $t('admin.tenants.components.user_management.add_user_button') }}
          </v-btn>
        </div>

        <!-- Users Table -->
        <div v-if="loading && users.length === 0" class="text-center py-8">
          <v-progress-circular indeterminate />
          <p class="mt-2">{{ $t('admin.tenants.components.user_management.loading') }}</p>
        </div>

        <div v-else-if="filteredUsers.length === 0" class="text-center py-8">
          <v-icon size="64" color="grey-lighten-1">
            {{ users.length === 0 ? 'mdi-account-off' : 'mdi-account-search' }}
          </v-icon>
          <p class="text-h6 mt-2">
            {{ users.length === 0 ? $t('admin.tenants.components.user_management.no_users_assigned') : $t('admin.tenants.components.user_management.no_users_found') }}
          </p>
          <p class="text-body-2 text-medium-emphasis">
            {{ users.length === 0
              ? $t('admin.tenants.components.user_management.no_users_assigned_description')
              : $t('admin.tenants.components.user_management.no_users_found_description')
            }}
          </p>
          <v-btn v-if="users.length === 0" color="primary" @click="showAssignDialog = true">
            {{ $t('admin.tenants.components.user_management.add_first_user') }}
          </v-btn>
        </div>

        <v-data-table
          v-else
          :headers="tableHeaders"
          :items="filteredUsers"
          :loading="loading"
          item-key="id"
          class="elevation-1"
        >
          <template #item.name="{ item }">
            <div class="d-flex align-center">
              <v-avatar size="32" color="grey-lighten-2" class="mr-3">
                <v-icon>mdi-account</v-icon>
              </v-avatar>
              <div>
                <div class="font-weight-medium">{{ item.first_name }} {{ item.last_name }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
              </div>
            </div>
          </template>

          <template #item.account="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.account.username }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.account.email }}</div>
            </div>
          </template>

          <template #item.system_role="{ item }">
            <v-chip
              :color="getRoleColor(item.system_role)"
              size="small"
              variant="flat"
            >
              {{ getRoleLabel(item.system_role) }}
            </v-chip>
          </template>

          <template #item.is_active="{ item }">
            <v-chip
              :color="item.is_active ? 'success' : 'error'"
              size="small"
              variant="flat"
            >
              <v-icon size="small" class="mr-1">
                {{ item.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
              </v-icon>
              {{ item.is_active ? $t('admin.tenants.components.user_management.status.active') : $t('admin.tenants.components.user_management.status.inactive') }}
            </v-chip>
          </template>

          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                @click="editUser(item)"
              >
                <v-icon>mdi-pencil</v-icon>
                <v-tooltip activator="parent">{{ $t('admin.tenants.components.user_management.tooltips.edit_role') }}</v-tooltip>
              </v-btn>

              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="removeUser(item)"
              >
                <v-icon>mdi-delete</v-icon>
                <v-tooltip activator="parent">{{ $t('admin.tenants.components.user_management.tooltips.remove') }}</v-tooltip>
              </v-btn>
            </div>
          </template>
        </v-data-table>

        <!-- Error Display -->
        <v-alert
          v-if="error"
          type="error"
          class="mt-4"
          dismissible
          @click:close="error = null"
        >
          {{ error }}
        </v-alert>

        <!-- Success Display -->
        <v-alert
          v-if="success"
          type="success"
          class="mt-4"
          dismissible
          @click:close="success = null"
        >
          {{ success }}
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Assign User Dialog -->
    <v-dialog v-model="showAssignDialog" max-width="600">
      <v-card>
        <v-card-title>{{ $t('admin.tenants.components.user_management.assign_dialog.title') }}</v-card-title>
        <v-card-text>
          <v-form ref="assignForm">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="assignForm.account_id"
                  :label="$t('admin.tenants.components.user_management.assign_dialog.account_label')"
                  :items="availableAccounts"
                  :loading="loadingAccounts"
                  variant="outlined"
                  item-title="text"
                  item-value="value"
                  return-object
                  @update:model-value="onAccountSelected"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.first_name"
                  :label="$t('admin.tenants.components.user_management.assign_dialog.first_name')"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.last_name"
                  :label="$t('admin.tenants.components.user_management.assign_dialog.last_name')"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.email"
                  :label="$t('admin.tenants.components.user_management.assign_dialog.email')"
                  type="email"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.phone"
                  :label="$t('admin.tenants.components.user_management.assign_dialog.phone')"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-select
                  v-model="assignForm.system_role"
                  :label="$t('admin.tenants.components.user_management.assign_dialog.role')"
                  :items="tenantRoles"
                  variant="outlined"
                  item-title="text"
                  item-value="value"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="showAssignDialog = false">{{ $t('admin.tenants.components.user_management.assign_dialog.cancel') }}</v-btn>
          <v-btn
            color="primary"
            :loading="assignLoading"
            :disabled="false"
            @click="assignUser"
          >
            {{ $t('admin.tenants.components.user_management.assign_dialog.add') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Role Dialog -->
    <v-dialog v-model="showEditDialog" max-width="400">
      <v-card>
        <v-card-title>{{ $t('admin.tenants.components.user_management.edit_dialog.title') }}</v-card-title>
        <v-card-text>
          <p class="mb-4">
            <strong>{{ editingUser?.first_name }} {{ editingUser?.last_name }}</strong>
          </p>
          <v-select
            v-model="newRole"
            :label="$t('admin.tenants.components.user_management.edit_dialog.new_role')"
            :items="tenantRoles"
            variant="outlined"
            item-title="text"
            item-value="value"
          />
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="showEditDialog = false">{{ $t('admin.tenants.components.user_management.edit_dialog.cancel') }}</v-btn>
          <v-btn
            color="primary"
            :loading="editLoading"
            @click="updateRole"
          >
            {{ $t('admin.tenants.components.user_management.edit_dialog.save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Tenant, TenantUser, AssignUserToTenantRequest } from '../types'
import { tenantService } from '../services/tenantService'
import { accountService } from '../services/accountService'

const { t } = useI18n()

interface Props {
  tenant: Tenant | null
}

const props = defineProps<Props>()

// State
const users = ref<TenantUser[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const searchQuery = ref('')

// Assign user dialog
const showAssignDialog = ref(false)
const assignForm = ref<AssignUserToTenantRequest>({
  account_id: '',
  system_role: 'tenant_member',
  first_name: '',
  last_name: '',
  email: '',
  phone: ''
})
const assignLoading = ref(false)
const availableAccounts = ref<Array<{value: string, text: string}>>([])
const loadingAccounts = ref(false)

// Edit role dialog
const showEditDialog = ref(false)
const editingUser = ref<TenantUser | null>(null)
const newRole = ref('')
const editLoading = ref(false)

// Computed
const tenant = computed(() => props.tenant)

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(user => 
    user.first_name.toLowerCase().includes(query) ||
    user.last_name.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query) ||
    user.account.username.toLowerCase().includes(query)
  )
})

const tableHeaders = computed(() => [
  { title: t('admin.tenants.components.user_management.table.name'), key: 'name', sortable: true },
  { title: t('admin.tenants.components.user_management.table.account'), key: 'account', sortable: false },
  { title: t('admin.tenants.components.user_management.table.role'), key: 'system_role', sortable: true },
  { title: t('admin.tenants.components.user_management.table.status'), key: 'is_active', sortable: true },
  { title: t('admin.tenants.components.user_management.table.added'), key: 'created_at', sortable: true },
  { title: t('admin.tenants.components.user_management.table.actions'), key: 'actions', sortable: false }
])

const tenantRoles = computed(() => [
  { value: 'tenant_admin', text: t('admin.tenants.components.user_management.roles.tenant_admin') },
  { value: 'tenant_member', text: t('admin.tenants.components.user_management.roles.tenant_member') }
])


// Methods
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const getRoleLabel = (role: string) => {
  const roleKey = role.replace(/_/g, '_')
  return t(`admin.tenants.components.user_management.roles.${roleKey}`, role)
}

const getRoleColor = (role: string) => {
  const colorMap: Record<string, string> = {
    'global_admin': 'purple',
    'tenant_admin': 'primary',
    'tenant_member': 'info'
  }
  return colorMap[role] || 'grey'
}

const loadUsers = async () => {
  if (!tenant.value) return

  loading.value = true
  try {
    const response = await tenantService.getTenantUsers(tenant.value.id)
    users.value = response.users
  } catch (err: any) {
    error.value = err.response?.data?.message || t('admin.tenants.components.user_management.messages.load_users_error')
  } finally {
    loading.value = false
  }
}

const loadAvailableAccounts = async () => {
  loadingAccounts.value = true
  try {
    const response = await accountService.getAccounts()
    availableAccounts.value = response.data.map(account => ({
      value: account.id,
      text: `${account.username} (${account.email})`
    }))
  } catch (err: any) {
    error.value = err.response?.data?.message || t('admin.tenants.components.user_management.messages.load_accounts_error')
  } finally {
    loadingAccounts.value = false
  }
}

const onAccountSelected = (account: {value: string, text: string}) => {
  // Auto-fill form fields based on account selection
  // This would ideally fetch more details from the account
}

const assignUser = async () => {
  assignLoading.value = true
  try {
    const response = await tenantService.assignUserToTenant(tenant.value!.id, assignForm.value)
    success.value = response.message
    showAssignDialog.value = false
    resetAssignForm()
    await loadUsers()
  } catch (err: any) {
    error.value = err.response?.data?.message || t('admin.tenants.components.user_management.messages.assign_error')
  } finally {
    assignLoading.value = false
  }
}

const editUser = (user: TenantUser) => {
  editingUser.value = user
  newRole.value = user.system_role
  showEditDialog.value = true
}

const updateRole = async () => {
  if (!editingUser.value) return

  editLoading.value = true
  try {
    const response = await tenantService.updateUserRole(
      tenant.value!.id,
      editingUser.value.id,
      newRole.value
    )
    success.value = response.message
    showEditDialog.value = false
    await loadUsers()
  } catch (err: any) {
    error.value = err.response?.data?.message || t('admin.tenants.components.user_management.messages.update_role_error')
  } finally {
    editLoading.value = false
  }
}

const removeUser = async (user: TenantUser) => {
  const userName = `${user.first_name} ${user.last_name}`
  const confirmed = confirm(
    t('admin.tenants.components.user_management.messages.remove_confirm', { name: userName })
  )

  if (!confirmed) return

  try {
    const response = await tenantService.removeUserFromTenant(tenant.value!.id, user.id)
    success.value = response.message
    await loadUsers()
  } catch (err: any) {
    error.value = err.response?.data?.message || t('admin.tenants.components.user_management.messages.remove_error')
  }
}

const resetAssignForm = () => {
  assignForm.value = {
    account_id: '',
    system_role: 'tenant_member',
    first_name: '',
    last_name: '',
    email: '',
    phone: ''
  }
}

// Watch for tenant changes
watch(tenant, (newTenant) => {
  if (newTenant) {
    loadUsers()
  }
}, { immediate: true })

// Watch for assign dialog open
watch(showAssignDialog, (isOpen) => {
  if (isOpen) {
    loadAvailableAccounts()
  }
})

onMounted(() => {
  if (tenant.value) {
    loadUsers()
  }
})
</script>

<style scoped>
.tenant-user-management {
  max-width: 100%;
}

.v-data-table {
  border-radius: 8px;
}
</style>