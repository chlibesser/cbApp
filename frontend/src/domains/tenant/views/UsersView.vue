<template>
  <v-container fluid>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Benutzerverwaltung</h1>
        <p class="text-body-1 text-medium-emphasis mt-1">
          Verwalten Sie Benutzer und Einladungen in Ihrem Tenant
        </p>
        <v-chip 
          v-if="userStats"
          size="small" 
          color="primary" 
          variant="tonal"
          class="mt-2"
        >
          {{ userStats.total }} Benutzer
        </v-chip>
      </div>
    </div>

    <!-- Advanced Data Table -->
    <AdvancedDataTable
      :columns="tableColumns"
      :api-endpoint="'/tenant/users'"
      @create="handleCreateUser"
      @item-selected="handleUserSelected"
      :loading="loading"
      :enable-create="canInviteUsers"
      create-button-text="Benutzer einladen"
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
                @click="handleViewUser(item)"
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
                @click="handleEditUser(item)"
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
                @click="handleToggleStatus(item)"
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
                @click="handleResendInvitation(item)"
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
    </AdvancedDataTable>

    <!-- RSD Wrapper für Create/Edit/View -->
    <GenericRSDWrapper />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRSDStore } from '@/infrastructure/stores/rsdStore';
import { useToast } from '@/shared/composables/useToast';
import { useApi } from '@/core/api';
import AdvancedDataTable, { type TableColumn } from '@/shared/components/tables/AdvancedDataTable.vue';
import GenericRSDWrapper from '@/shared/components/GenericRSDWrapper.vue';

// Stores & Composables
const rsdStore = useRSDStore();
const toast = useToast();
const api = useApi();

// State
const loading = ref(false);
const userStats = ref<{total: number, active: number, invited: number} | null>(null);

// Permissions (sollten vom aktuellen User/Profile kommen)
const canInviteUsers = ref(true); // TODO: Von Auth Store
const canManageUsers = ref(true); // TODO: Von Auth Store

// Table Configuration
const tableColumns: TableColumn[] = [
  {
    key: 'full_name',
    label: 'Name',
    type: 'text',
    sortable: true,
    searchable: true,
    width: '200px'
  },
  {
    key: 'email',
    label: 'E-Mail',
    type: 'email',
    sortable: true,
    searchable: true,
    width: '250px'
  },
  {
    key: 'system_role',
    label: 'Rolle',
    type: 'enum',
    sortable: true,
    filterable: true,
    width: '150px',
    options: [
      { value: 'tenant_admin', label: 'Tenant Admin' },
      { value: 'tenant_member', label: 'Tenant Mitglied' }
    ]
  },
  {
    key: 'status',
    label: 'Status',
    type: 'enum',
    sortable: true,
    filterable: true,
    width: '120px',
    options: [
      { value: 'active', label: 'Aktiv' },
      { value: 'invited', label: 'Eingeladen' },
      { value: 'inactive', label: 'Inaktiv' }
    ]
  },
  {
    key: 'last_login_at',
    label: 'Letzter Login',
    type: 'datetime',
    sortable: true,
    width: '180px'
  },
  {
    key: 'actions',
    label: 'Aktionen',
    type: 'custom',
    sortable: false,
    width: '150px'
  }
];

// Event Handlers
const handleCreateUser = () => {
  rsdStore.openCreate('user');
};

const handleUserSelected = (user: any) => {
  handleViewUser(user);
};

const handleViewUser = (user: any) => {
  rsdStore.openView('user', user);
};

const handleEditUser = (user: any) => {
  rsdStore.openEdit('user', user);
};

const handleToggleStatus = async (user: any) => {
  try {
    loading.value = true;
    
    const action = user.is_active ? 'deactivate' : 'activate';
    await api.patch(`/tenant/users/${user.id}/${action}`);
    
    toast.success(
      user.is_active 
        ? 'Benutzer wurde deaktiviert' 
        : 'Benutzer wurde aktiviert'
    );
    
    // Refresh table
    // TODO: Trigger table refresh
    
  } catch (error) {
    toast.error('Fehler beim Ändern des Status');
  } finally {
    loading.value = false;
  }
};

const handleResendInvitation = async (user: any) => {
  try {
    loading.value = true;
    
    await api.post(`/tenant/users/${user.id}/resend-invitation`);
    toast.success('Einladung wurde erneut versendet');
    
  } catch (error) {
    toast.error('Fehler beim Versenden der Einladung');
  } finally {
    loading.value = false;
  }
};

const handleDeleteUser = async (user: any) => {
  // TODO: Confirmation dialog
  if (!confirm(`Möchten Sie ${user.full_name} wirklich entfernen?`)) {
    return;
  }

  try {
    loading.value = true;
    
    await api.delete(`/tenant/users/${user.id}`);
    toast.success('Benutzer wurde entfernt');
    
    // Refresh table
    // TODO: Trigger table refresh
    
  } catch (error) {
    toast.error('Fehler beim Entfernen des Benutzers');
  } finally {
    loading.value = false;
  }
};

// Helper Functions
const canManageUser = (user: any): boolean => {
  // TODO: Implement proper permission check
  return canManageUsers.value && user.id !== 'current-user-id';
};

const canDeleteUser = (user: any): boolean => {
  return canManageUser(user);
};

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'active': return 'success';
    case 'invited': return 'warning';
    case 'inactive': return 'error';
    default: return 'grey';
  }
};

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'active': return 'Aktiv';
    case 'invited': return 'Eingeladen';
    case 'inactive': return 'Inaktiv';
    default: return status;
  }
};

const getRoleColor = (role: string): string => {
  switch (role) {
    case 'tenant_admin': return 'primary';
    case 'tenant_member': return 'info';
    default: return 'grey';
  }
};

const getRoleLabel = (role: string): string => {
  switch (role) {
    case 'tenant_admin': return 'Tenant Admin';
    case 'tenant_member': return 'Tenant Mitglied';
    default: return role;
  }
};

const formatDateTime = (datetime: string): string => {
  return new Date(datetime).toLocaleString('de-DE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Load initial data
onMounted(async () => {
  try {
    loading.value = true;
    
    const response = await api.get('/tenant/users');
    userStats.value = response.data.meta;
    
  } catch (error) {
    toast.error('Fehler beim Laden der Benutzer');
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.gap-1 {
  gap: 4px;
}
</style>