<template>
  <v-card-text>
    <v-row>
      <!-- Basic Information -->
      <v-col cols="12" md="6">
        <v-card variant="outlined" class="h-100">
          <v-card-title class="text-h6 pb-2">
            <v-icon icon="mdi-account" class="me-2" />
            Benutzer-Informationen
          </v-card-title>
          
          <v-card-text>
            <div class="info-row">
              <span class="label">Name:</span>
              <span class="value">{{ userData.full_name }}</span>
            </div>
            
            <div class="info-row">
              <span class="label">E-Mail:</span>
              <span class="value">{{ userData.email }}</span>
            </div>
            
            <div v-if="userData.phone" class="info-row">
              <span class="label">Telefon:</span>
              <span class="value">{{ userData.phone }}</span>
            </div>
            
            <div class="info-row">
              <span class="label">Rolle:</span>
              <v-chip
                :color="getRoleColor(userData.system_role)"
                size="small"
                variant="tonal"
              >
                {{ getRoleLabel(userData.system_role) }}
              </v-chip>
            </div>
            
            <div class="info-row">
              <span class="label">Status:</span>
              <v-chip
                :color="getStatusColor(userData.status)"
                size="small"
                variant="tonal"
              >
                {{ getStatusLabel(userData.status) }}
              </v-chip>
            </div>
            
            <div class="info-row">
              <span class="label">Aktiv:</span>
              <v-chip
                :color="userData.is_active ? 'success' : 'error'"
                size="small"
                variant="tonal"
              >
                {{ userData.is_active ? 'Ja' : 'Nein' }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Account Information -->
      <v-col cols="12" md="6">
        <v-card variant="outlined" class="h-100">
          <v-card-title class="text-h6 pb-2">
            <v-icon icon="mdi-account-key" class="me-2" />
            Account-Informationen
          </v-card-title>
          
          <v-card-text>
            <div v-if="userData.account">
              <div class="info-row">
                <span class="label">Account-E-Mail:</span>
                <span class="value">{{ userData.account.email }}</span>
              </div>
              
              <div class="info-row">
                <span class="label">Letzter Login:</span>
                <span class="value">
                  {{ userData.account.last_login_at 
                     ? formatDateTime(userData.account.last_login_at)
                     : 'Nie angemeldet' }}
                </span>
              </div>
              
              <div class="info-row">
                <span class="label">Account-ID:</span>
                <span class="value text-caption text-medium-emphasis">
                  {{ userData.account.id }}
                </span>
              </div>
            </div>
            
            <div v-else>
              <v-alert type="warning" variant="tonal">
                <div class="text-body-2">
                  <strong>Nicht verknüpft</strong>
                  <br>
                  Dieser Benutzer ist noch nicht mit einem Account verknüpft.
                  <br>
                  <br>
                  
                  <v-btn 
                    v-if="canResendInvitation"
                    variant="text" 
                    size="small"
                    color="primary"
                    @click="handleResendInvitation"
                    :loading="resendingInvitation"
                    prepend-icon="mdi-email-send"
                  >
                    Einladung erneut senden
                  </v-btn>
                </div>
              </v-alert>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Timestamps -->
      <v-col cols="12">
        <v-card variant="outlined">
          <v-card-title class="text-h6 pb-2">
            <v-icon icon="mdi-clock" class="me-2" />
            Zeitstempel
          </v-card-title>
          
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <div class="info-row">
                  <span class="label">Erstellt:</span>
                  <span class="value">{{ formatDateTime(userData.created_at) }}</span>
                </div>
              </v-col>
              
              <v-col cols="12" md="4">
                <div class="info-row">
                  <span class="label">Aktualisiert:</span>
                  <span class="value">{{ formatDateTime(userData.updated_at) }}</span>
                </div>
              </v-col>
              
              <v-col cols="12" md="4">
                <div class="info-row">
                  <span class="label">Profile-ID:</span>
                  <span class="value text-caption text-medium-emphasis">
                    {{ userData.id }}
                  </span>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Actions -->
      <v-col v-if="canManageUser" cols="12">
        <v-card variant="outlined">
          <v-card-title class="text-h6 pb-2">
            <v-icon icon="mdi-cogs" class="me-2" />
            Aktionen
          </v-card-title>
          
          <v-card-text>
            <div class="d-flex flex-wrap gap-3">
              <v-btn
                color="primary"
                variant="tonal"
                @click="handleEdit"
                prepend-icon="mdi-pencil"
              >
                Bearbeiten
              </v-btn>
              
              <v-btn
                :color="userData.is_active ? 'error' : 'success'"
                variant="tonal"
                @click="handleToggleStatus"
                :loading="togglingStatus"
                :prepend-icon="userData.is_active ? 'mdi-account-cancel' : 'mdi-account-check'"
              >
                {{ userData.is_active ? 'Deaktivieren' : 'Aktivieren' }}
              </v-btn>
              
              <v-btn
                v-if="userData.status === 'invited'"
                color="primary"
                variant="outlined"
                @click="handleResendInvitation"
                :loading="resendingInvitation"
                prepend-icon="mdi-email-send"
              >
                Einladung erneut senden
              </v-btn>
              
              <v-spacer />
              
              <v-btn
                color="error"
                variant="outlined"
                @click="handleDelete"
                prepend-icon="mdi-delete"
              >
                Entfernen
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-card-text>

  <v-card-actions class="px-6 pb-6">
    <v-spacer />
    <v-btn 
      variant="text" 
      @click="handleClose"
    >
      Schließen
    </v-btn>
  </v-card-actions>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRSDStore } from '@/infrastructure/stores/rsdStore';
import { useToast } from '@/shared/composables/useToast';
import { useApi } from '@/core/api';

// Stores & Composables
const rsdStore = useRSDStore();
const toast = useToast();
const api = useApi();

// Props from RSD
const userData = computed(() => rsdStore.currentItem || {});

// State
const togglingStatus = ref(false);
const resendingInvitation = ref(false);

// Permissions
const canManageUser = computed(() => {
  // TODO: Check if this is current user and permissions
  return true;
});

const canResendInvitation = computed(() => {
  return canManageUser.value && !userData.value.is_linked_to_account;
});

// Methods
const handleEdit = () => {
  rsdStore.openEdit('user', userData.value);
};

const handleToggleStatus = async () => {
  try {
    togglingStatus.value = true;
    
    const action = userData.value.is_active ? 'deactivate' : 'activate';
    await api.patch(`/tenant/users/${userData.value.id}/${action}`);
    
    toast.success(
      userData.value.is_active 
        ? 'Benutzer wurde deaktiviert' 
        : 'Benutzer wurde aktiviert'
    );
    
    // Update local data
    userData.value.is_active = !userData.value.is_active;
    userData.value.status = userData.value.is_active ? 'active' : 'inactive';
    
    // Emit success event
    rsdStore.emitSuccess('user-updated', userData.value);
    
  } catch (error) {
    toast.error('Fehler beim Ändern des Status');
  } finally {
    togglingStatus.value = false;
  }
};

const handleResendInvitation = async () => {
  try {
    resendingInvitation.value = true;
    
    await api.post(`/tenant/users/${userData.value.id}/resend-invitation`);
    toast.success('Einladung wurde erneut versendet');
    
  } catch (error) {
    toast.error('Fehler beim Versenden der Einladung');
  } finally {
    resendingInvitation.value = false;
  }
};

const handleDelete = async () => {
  if (!confirm(`Möchten Sie ${userData.value.full_name} wirklich entfernen?`)) {
    return;
  }

  try {
    await api.delete(`/tenant/users/${userData.value.id}`);
    toast.success('Benutzer wurde entfernt');
    
    // Emit success event
    rsdStore.emitSuccess('user-deleted', userData.value);
    
    // Close RSD
    rsdStore.close();
    
  } catch (error) {
    toast.error('Fehler beim Entfernen des Benutzers');
  }
};

const handleClose = () => {
  rsdStore.close();
};

// Helper Functions
const getRoleColor = (role: string): string => {
  switch (role) {
    case 'tenant_admin': return 'primary';
    case 'tenant_member': return 'info';
    default: return 'grey';
  }
};

const getRoleLabel = (role: string): string => {
  switch (role) {
    case 'tenant_admin': return 'Tenant Administrator';
    case 'tenant_member': return 'Tenant Mitglied';
    default: return role;
  }
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

const formatDateTime = (datetime: string): string => {
  return new Date(datetime).toLocaleString('de-DE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  gap: 12px;
}

.label {
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface));
  opacity: 0.7;
  min-width: 100px;
}

.value {
  text-align: right;
  flex: 1;
}

.gap-3 {
  gap: 12px;
}
</style>