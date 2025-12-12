<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card-text>
      <v-row>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="form.first_name"
            label="Vorname"
            variant="outlined"
            :error-messages="fieldErrors.first_name"
            @input="clearFieldError('first_name')"
          />
        </v-col>
        
        <v-col cols="12" md="6">
          <v-text-field
            v-model="form.last_name"
            label="Nachname"
            variant="outlined"
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
            :error-messages="fieldErrors.email"
            @input="clearFieldError('email')"
          />
        </v-col>

        <v-col cols="12">
          <v-text-field
            v-model="form.phone"
            label="Telefonnummer (optional)"
            variant="outlined"
            prepend-inner-icon="mdi-phone"
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
        </v-col>

        <v-col cols="12">
          <v-alert type="info" variant="tonal" class="text-body-2">
            <strong>Einladungsprozess:</strong>
            <br>
            1. Benutzer erhält Einladungs-E-Mail
            <br>
            2. Benutzer registriert sich oder meldet sich an
            <br>
            3. Profile wird automatisch aktiviert
          </v-alert>
        </v-col>
      </v-row>
    </v-card-text>

    <v-card-actions class="px-6 pb-6">
      <v-spacer />
      <v-btn 
        variant="text" 
        @click="handleCancel"
        :disabled="loading"
      >
        Abbrechen
      </v-btn>
      <v-btn 
        type="submit"
        color="primary"
        :loading="loading"
        prepend-icon="mdi-email-send"
      >
        Einladung senden
      </v-btn>
    </v-card-actions>
  </v-form>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRSDStore } from '@/infrastructure/stores/rsdStore';
import { useToast } from '@/shared/composables/useToast';
import { useApi } from '@/core/api';

// Stores & Composables
const rsdStore = useRSDStore();
const toast = useToast();
const api = useApi();

// Refs
const formRef = ref();
const loading = ref(false);

// Form Data
const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  system_role: 'tenant_member'
});

// Field Errors
const fieldErrors = reactive<Record<string, string[]>>({});

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

const handleSubmit = async () => {
  try {
    loading.value = true;
    
    // Clear existing field errors
    clearAllFieldErrors();
    
    const response = await api.post('/tenant/users', form);
    
    toast.success('Einladung wurde erfolgreich versendet');
    
    // Emit success event to refresh parent table
    rsdStore.emitSuccess('user-created', response.data);
    
    // Close RSD
    rsdStore.close();
    
  } catch (error: any) {
    console.log('Full error:', error);
    console.log('Error status:', error.status);
    console.log('Error errors:', error.errors);
    
    // Our API Client throws ApiError objects directly, not response objects
    if (error.errors) {
      // Backend validation errors - assign to fieldErrors
      const errors = error.errors;
      console.log('Processing errors:', errors);
      Object.entries(errors).forEach(([field, messages]: [string, any]) => {
        console.log(`Setting error for field ${field}:`, messages);
        fieldErrors[field] = Array.isArray(messages) ? messages : [messages];
      });
      console.log('Final fieldErrors:', fieldErrors);
    } else if (error.message) {
      toast.error(error.message);
    } else {
      toast.error(`Fehler beim Senden der Einladung: ${error.message || 'Unbekannter Fehler'}`);
    }
  } finally {
    loading.value = false;
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
</script>