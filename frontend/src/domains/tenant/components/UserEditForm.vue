<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card-text>
      <v-row>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="form.first_name"
            label="Vorname"
            variant="outlined"
            :readonly="!canEditProfile"
            :error-messages="fieldErrors.first_name"
            @input="clearFieldError('first_name')"
          />
        </v-col>
        
        <v-col cols="12" md="6">
          <v-text-field
            v-model="form.last_name"
            label="Nachname"
            variant="outlined"
            :readonly="!canEditProfile"
            :error-messages="fieldErrors.last_name"
            @input="clearFieldError('last_name')"
          />
        </v-col>

        <v-col cols="12">
          <v-text-field
            v-model="form.email"
            label="E-Mail-Adresse"
            type="email"
            variant="outlined"
            readonly
            hint="E-Mail-Adresse kann nach der Erstellung nicht geändert werden"
            :error-messages="fieldErrors.email"
          />
        </v-col>

        <v-col cols="12">
          <v-text-field
            v-model="form.phone"
            label="Telefonnummer"
            variant="outlined"
            prepend-inner-icon="mdi-phone"
            :readonly="!canEditProfile"
            :error-messages="fieldErrors.phone"
            @input="clearFieldError('phone')"
          />
        </v-col>

        <v-col cols="12">
          <v-select
            v-model="form.system_role"
            label="System-Rolle"
            :items="roleOptions"
            item-title="label"
            item-value="value"
            variant="outlined"
            :readonly="!canEditRole"
            :error-messages="fieldErrors.system_role"
            @update:model-value="clearFieldError('system_role')"
          >
            <template #item="{ props, item }">
              <v-list-item v-bind="props">
                <template #prepend>
                  <v-chip 
                    :color="getRoleColor(item.raw.value)"
                    size="small"
                    variant="tonal"
                  >
                    {{ item.raw.label }}
                  </v-chip>
                </template>
                <v-list-item-subtitle>
                  {{ item.raw.description }}
                </v-list-item-subtitle>
              </v-list-item>
            </template>
          </v-select>
          <div v-if="!canEditRole" class="text-caption text-medium-emphasis mt-1">
            Sie können Ihre eigene Rolle nicht ändern
          </div>
        </v-col>

        <!-- Status Information -->
        <v-col cols="12">
          <v-card variant="outlined" class="pa-4">
            <div class="text-subtitle-2 mb-2">Status-Informationen</div>
            
            <div class="d-flex align-center gap-3 mb-2">
              <span class="text-body-2">Status:</span>
              <v-chip
                :color="getStatusColor(userData.status)"
                size="small"
                variant="tonal"
              >
                {{ getStatusLabel(userData.status) }}
              </v-chip>
            </div>

            <div class="d-flex align-center gap-3 mb-2">
              <span class="text-body-2">Aktiv:</span>
              <v-chip
                :color="userData.is_active ? 'success' : 'error'"
                size="small"
                variant="tonal"
              >
                {{ userData.is_active ? 'Ja' : 'Nein' }}
              </v-chip>
            </div>

            <div v-if="userData.account" class="d-flex align-center gap-3">
              <span class="text-body-2">Letzter Login:</span>
              <span class="text-body-2">
                {{ userData.account.last_login_at 
                   ? formatDateTime(userData.account.last_login_at)
                   : 'Nie angemeldet' }}
              </span>
            </div>

            <div v-if="!userData.is_linked_to_account" class="mt-3">
              <v-alert type="warning" variant="tonal" class="text-body-2">
                Dieser Benutzer ist noch nicht mit einem Account verknüpft.
                <br>
                <v-btn 
                  variant="text" 
                  size="small"
                  @click="handleResendInvitation"
                  :loading="resendingInvitation"
                  class="mt-2"
                >
                  Einladung erneut senden
                </v-btn>
              </v-alert>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>

    <v-card-actions class="px-6 pb-6">
      <div class="d-flex align-center gap-2">
        <v-btn
          v-if="canToggleStatus"
          :color="userData.is_active ? 'error' : 'success'"
          variant="tonal"
          @click="handleToggleStatus"
          :loading="togglingStatus"
        >
          {{ userData.is_active ? 'Deaktivieren' : 'Aktivieren' }}
        </v-btn>
      </div>
      
      <v-spacer />
      
      <v-btn 
        variant="text" 
        @click="handleCancel"
        :disabled="loading"
      >
        Abbrechen
      </v-btn>
      
      <v-btn 
        v-if="hasChanges"
        type="submit"
        color="primary"
        :loading="loading"
      >
        Änderungen speichern
      </v-btn>
    </v-card-actions>
  </v-form>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { useRSDStore } from '@/infrastructure/stores/rsdStore';
import { useToast } from '@/shared/composables/useToast';
import { useApi } from '@/core/api';

// Stores & Composables
const rsdStore = useRSDStore();
const toast = useToast();
const api = useApi();

// Props from RSD
const userData = computed(() => rsdStore.currentItem || {});

// Refs
const formRef = ref();
const loading = ref(false);
const togglingStatus = ref(false);
const resendingInvitation = ref(false);

// Form Data
const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  system_role: ''
});

const originalForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  system_role: ''
});

// Field Errors
const fieldErrors = reactive<Record<string, string[]>>({});

// Permissions
const canEditProfile = computed(() => {
  // TODO: Check actual permissions
  return true;
});

const canEditRole = computed(() => {
  // Can't edit own role
  // TODO: Check if this is current user
  return true;
});

const canToggleStatus = computed(() => {
  // Can't toggle own status
  // TODO: Check if this is current user
  return true;
});

// Role Options
const roleOptions = [
  {
    value: 'tenant_admin',
    label: 'Tenant Administrator',
    description: 'Verwaltung des Tenants und aller Benutzer'
  },
  {
    value: 'tenant_member',
    label: 'Tenant Mitglied',
    description: 'Normales Mitglied mit Basis-Zugriffsrechten'
  }
];

// Computed
const hasChanges = computed(() => {
  return JSON.stringify(form) !== JSON.stringify(originalForm);
});


// Methods
const clearFieldError = (fieldName: string) => {
  if (fieldErrors[fieldName]) {
    delete fieldErrors[fieldName];
  }
};

const clearAllFieldErrors = () => {
  Object.keys(fieldErrors).forEach(key => {
    delete fieldErrors[key];
  });
};

const initializeForm = () => {
  const data = userData.value;
  
  Object.assign(form, {
    first_name: data.first_name || '',
    last_name: data.last_name || '',
    email: data.email || '',
    phone: data.phone || '',
    system_role: data.system_role || 'tenant_member'
  });
  
  Object.assign(originalForm, { ...form });
};

const handleSubmit = async () => {
  try {
    loading.value = true;
    
    // Clear existing field errors
    clearAllFieldErrors();
    
    // Check if role changed
    if (form.system_role !== originalForm.system_role) {
      await api.patch(`/tenant/users/${userData.value.id}/role`, {
        system_role: form.system_role
      });
      toast.success('Rolle wurde erfolgreich aktualisiert');
    }

    // TODO: Handle other field updates if API supports them
    
    // Emit success event to refresh parent table
    rsdStore.emitSuccess('user-updated', { ...userData.value, ...form });
    
    // Close RSD
    rsdStore.close();
    
  } catch (error: any) {
    // Our API Client throws ApiError objects directly, not response objects
    if (error.errors) {
      // Backend validation errors - assign to fieldErrors
      const errors = error.errors;
      Object.entries(errors).forEach(([field, messages]: [string, any]) => {
        fieldErrors[field] = Array.isArray(messages) ? messages : [messages];
      });
    } else {
      toast.error(error.message || 'Fehler beim Speichern der Änderungen');
    }
  } finally {
    loading.value = false;
  }
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

const handleCancel = () => {
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

// Initialize form when component mounts
onMounted(() => {
  initializeForm();
});
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}
</style>