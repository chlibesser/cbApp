<template>
  <div class="account-rsd">
    <!-- View Mode -->
    <v-container v-if="rsdStore.isViewMode" class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">Account-Details</h3>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">E-Mail</div>
          <div class="text-body-1">{{ rsdStore.data?.email || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">System-Rolle</div>
          <v-chip 
            :color="getSystemRoleColor(rsdStore.data?.system_role)"
            variant="tonal"
            size="small"
          >
            {{ getSystemRoleLabel(rsdStore.data?.system_role) }}
          </v-chip>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Status</div>
          <v-chip 
            :color="rsdStore.data?.email_verified_at ? 'success' : 'warning'"
            variant="tonal"
            size="small"
          >
            <v-icon size="small" class="mr-1">
              {{ rsdStore.data?.email_verified_at ? 'mdi-check-circle' : 'mdi-clock-outline' }}
            </v-icon>
            {{ rsdStore.data?.email_verified_at ? 'Verifiziert' : 'Nicht verifiziert' }}
          </v-chip>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Erstellt am</div>
          <div class="text-body-1">{{ formatDateTime(rsdStore.data?.created_at) }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Aktualisiert am</div>
          <div class="text-body-1">{{ formatDateTime(rsdStore.data?.updated_at) }}</div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Edit/Create Mode -->
    <v-container v-else class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">
            {{ rsdStore.isCreateMode ? 'Neuen Account erstellen' : 'Account bearbeiten' }}
          </h3>
        </v-col>
        
        <v-col cols="12">
          <v-text-field
            v-model="form.email"
            label="E-Mail *"
            variant="outlined"
            density="compact"
            type="email"
            :error-messages="errors.email"
            required
          />
        </v-col>
        
        <v-col cols="12" v-if="rsdStore.isCreateMode">
          <v-text-field
            v-model="form.password"
            label="Passwort *"
            variant="outlined"
            density="compact"
            type="password"
            :error-messages="errors.password"
            required
          />
        </v-col>
        
        <v-col cols="12">
          <v-select
            v-model="form.system_role"
            label="System-Rolle *"
            variant="outlined"
            density="compact"
            :items="systemRoles"
            item-title="text"
            item-value="value"
            :error-messages="errors.system_role"
            required
          />
        </v-col>
        
        <v-col cols="12" class="pt-6">
          <div class="d-flex gap-2">
            <v-btn
              color="primary"
              variant="flat"
              :loading="loading"
              @click="handleSave"
            >
              {{ rsdStore.isCreateMode ? 'Erstellen' : 'Speichern' }}
            </v-btn>
            
            <v-btn
              variant="outlined"
              @click="handleCancel"
            >
              Abbrechen
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'
import { accountService } from '../services/accountService'
import type { Account } from '../types'

const rsdStore = useRSDStore()

// Form data
const form = ref({
  email: '',
  password: '',
  system_role: 'member',
})

// Form state
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const systemRoles = ref<Array<{ value: string, text: string }>>([])

// Emits
const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
}>()

// Watch for data changes (when opening edit mode)
watch(() => rsdStore.data, (newData) => {
  if (newData && (rsdStore.isEditMode || rsdStore.isViewMode)) {
    form.value = {
      email: newData.email || '',
      password: '',
      system_role: newData.system_role || 'member',
    }
  }
}, { immediate: true })

// Reset form when switching to create mode
watch(() => rsdStore.mode, (newMode) => {
  if (newMode === 'create') {
    form.value = {
      email: '',
      password: '',
      system_role: 'member',
    }
    errors.value = {}
  }
})

// Load system roles on mount
onMounted(async () => {
  try {
    // For now, use static roles - could be loaded from API later
    systemRoles.value = [
      { value: 'global_admin', text: 'Global Administrator' },
      { value: 'tenant_admin', text: 'Tenant Administrator' },
      { value: 'member', text: 'Mitglied' },
    ]
  } catch (error) {
    console.error('Error loading system roles:', error)
  }
})

// Methods
const formatDateTime = (dateString?: string) => {
  if (!dateString) return '-'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getSystemRoleLabel = (role?: string) => {
  const roleMap = {
    'global_admin': 'Global Administrator',
    'tenant_admin': 'Tenant Administrator', 
    'member': 'Mitglied'
  }
  return roleMap[role as keyof typeof roleMap] || role || '-'
}

const getSystemRoleColor = (role?: string) => {
  const colorMap = {
    'global_admin': 'error',
    'tenant_admin': 'warning',
    'member': 'primary'
  }
  return colorMap[role as keyof typeof colorMap] || 'grey'
}

const handleSave = async () => {
  loading.value = true
  errors.value = {}
  
  try {
    let result: Account
    
    if (rsdStore.isCreateMode) {
      result = await accountService.createAccount(form.value)
      emit('success', 'Account wurde erfolgreich erstellt')
    } else {
      result = await accountService.updateAccount(rsdStore.data.id, {
        email: form.value.email,
        system_role: form.value.system_role
      })
      emit('success', 'Account wurde erfolgreich aktualisiert')
    }
    
    // Update RSD data with result
    rsdStore.setData(result)
    rsdStore.handleSuccess()
    
  } catch (error: any) {
    console.error('Error saving account:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
    
    const message = error.response?.data?.message || 'Fehler beim Speichern des Accounts'
    emit('error', message)
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  if (rsdStore.isCreateMode) {
    rsdStore.close()
  } else {
    // Switch back to view mode
    rsdStore.open('account', 'view', rsdStore.data)
  }
}
</script>

<style scoped>
.account-rsd {
  height: 100%;
}

.text-caption {
  font-size: 0.75rem;
  opacity: 0.7;
}

.text-body-1 {
  font-size: 0.875rem;
  line-height: 1.5;
}

.d-flex.gap-2 {
  gap: 8px;
}
</style>