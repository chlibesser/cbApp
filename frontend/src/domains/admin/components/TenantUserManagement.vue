<template>
  <div class="tenant-user-management">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2">mdi-account-multiple</v-icon>
        Benutzer-Verwaltung: {{ tenant?.name }}
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
              <small>{{ tenant.users_count || 0 }} Benutzer zugewiesen</small>
            </div>
          </div>
        </v-alert>

        <!-- Toolbar -->
        <div class="d-flex justify-space-between align-center mb-4">
          <v-text-field
            v-model="searchQuery"
            label="Benutzer durchsuchen..."
            variant="outlined"
            prepend-inner-icon="mdi-magnify"
            density="compact"
            style="max-width: 300px"
            clearable
            hide-details
          />

          <v-btn
            color="primary"
            @click="showAssignDialog = true"
            :disabled="loading"
          >
            <v-icon class="mr-1">mdi-account-plus</v-icon>
            Benutzer hinzufügen
          </v-btn>
        </div>

        <!-- Users Table -->
        <div v-if="loading && users.length === 0" class="text-center py-8">
          <v-progress-circular indeterminate />
          <p class="mt-2">Benutzer werden geladen...</p>
        </div>

        <div v-else-if="filteredUsers.length === 0" class="text-center py-8">
          <v-icon size="64" color="grey-lighten-1">
            {{ users.length === 0 ? 'mdi-account-off' : 'mdi-account-search' }}
          </v-icon>
          <p class="text-h6 mt-2">
            {{ users.length === 0 ? 'Keine Benutzer zugewiesen' : 'Keine Benutzer gefunden' }}
          </p>
          <p class="text-body-2 text-medium-emphasis">
            {{ users.length === 0 
              ? 'Diesem Tenant sind noch keine Benutzer zugewiesen.' 
              : 'Versuchen Sie einen anderen Suchbegriff.'
            }}
          </p>
          <v-btn v-if="users.length === 0" color="primary" @click="showAssignDialog = true">
            Ersten Benutzer hinzufügen
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
              {{ item.is_active ? 'Aktiv' : 'Inaktiv' }}
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
                <v-tooltip activator="parent">Rolle bearbeiten</v-tooltip>
              </v-btn>

              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="removeUser(item)"
              >
                <v-icon>mdi-delete</v-icon>
                <v-tooltip activator="parent">Aus Tenant entfernen</v-tooltip>
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
        <v-card-title>Benutzer zum Tenant hinzufügen</v-card-title>
        <v-card-text>
          <v-form ref="assignForm" v-model="assignFormValid">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="assignForm.account_id"
                  label="Account auswählen"
                  :items="availableAccounts"
                  :loading="loadingAccounts"
                  variant="outlined"
                  :rules="[v => !!v || 'Account ist erforderlich']"
                  item-title="text"
                  item-value="value"
                  return-object
                  @update:model-value="onAccountSelected"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.first_name"
                  label="Vorname"
                  variant="outlined"
                  :rules="[v => !!v || 'Vorname ist erforderlich']"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.last_name"
                  label="Nachname"
                  variant="outlined"
                  :rules="[v => !!v || 'Nachname ist erforderlich']"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.email"
                  label="E-Mail"
                  type="email"
                  variant="outlined"
                  :rules="emailRules"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="assignForm.phone"
                  label="Telefon (optional)"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-select
                  v-model="assignForm.system_role"
                  label="Rolle"
                  :items="tenantRoles"
                  variant="outlined"
                  :rules="[v => !!v || 'Rolle ist erforderlich']"
                  item-title="text"
                  item-value="value"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showAssignDialog = false">Abbrechen</v-btn>
          <v-btn
            color="primary"
            :loading="assignLoading"
            :disabled="!assignFormValid"
            @click="assignUser"
          >
            Hinzufügen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Role Dialog -->
    <v-dialog v-model="showEditDialog" max-width="400">
      <v-card>
        <v-card-title>Benutzerrolle bearbeiten</v-card-title>
        <v-card-text>
          <p class="mb-4">
            <strong>{{ editingUser?.first_name }} {{ editingUser?.last_name }}</strong>
          </p>
          <v-select
            v-model="newRole"
            label="Neue Rolle"
            :items="tenantRoles"
            variant="outlined"
            item-title="text"
            item-value="value"
          />
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showEditDialog = false">Abbrechen</v-btn>
          <v-btn
            color="primary"
            :loading="editLoading"
            @click="updateRole"
          >
            Speichern
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import type { Tenant, TenantUser, AssignUserToTenantRequest } from '../types'
import { tenantService } from '../services/tenantService'
import { accountService } from '../services/accountService'

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
const assignFormValid = ref(false)
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

const tableHeaders = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Account', key: 'account', sortable: false },
  { title: 'Rolle', key: 'system_role', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Hinzugefügt', key: 'created_at', sortable: true },
  { title: 'Aktionen', key: 'actions', sortable: false }
]

const tenantRoles = [
  { value: 'tenant_admin', text: 'Tenant Admin' },
  { value: 'tenant_member', text: 'Tenant Member' }
]

const emailRules = [
  (v: string) => !!v || 'E-Mail ist erforderlich',
  (v: string) => /.+@.+\..+/.test(v) || 'E-Mail muss gültig sein'
]

// Methods
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const getRoleLabel = (role: string) => {
  const roleMap: Record<string, string> = {
    'global_admin': 'Global Admin',
    'tenant_admin': 'Tenant Admin',
    'tenant_member': 'Tenant Member'
  }
  return roleMap[role] || role
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
    error.value = err.response?.data?.message || 'Fehler beim Laden der Benutzer'
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
    error.value = err.response?.data?.message || 'Fehler beim Laden der Accounts'
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
    error.value = err.response?.data?.message || 'Fehler beim Hinzufügen des Benutzers'
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
    error.value = err.response?.data?.message || 'Fehler beim Aktualisieren der Rolle'
  } finally {
    editLoading.value = false
  }
}

const removeUser = async (user: TenantUser) => {
  const confirmed = confirm(
    `Möchten Sie ${user.first_name} ${user.last_name} wirklich aus diesem Tenant entfernen?`
  )
  
  if (!confirmed) return

  try {
    const response = await tenantService.removeUserFromTenant(tenant.value!.id, user.id)
    success.value = response.message
    await loadUsers()
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Fehler beim Entfernen des Benutzers'
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