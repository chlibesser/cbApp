<template>
  <div class="tenant-rsd">
    <!-- View Mode -->
    <v-container v-if="rsdStore.isViewMode" class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">Tenant-Details</h3>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Name</div>
          <div class="text-body-1">{{ rsdStore.data?.name || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Slug</div>
          <div class="text-body-1">{{ rsdStore.data?.slug || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Beschreibung</div>
          <div class="text-body-1">{{ rsdStore.data?.description || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Typ</div>
          <v-chip 
            :color="rsdStore.data?.is_personal ? 'info' : 'primary'"
            variant="tonal"
            size="small"
          >
            {{ rsdStore.data?.is_personal ? 'Persönlicher Workspace' : 'Unternehmen' }}
          </v-chip>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Status</div>
          <v-chip 
            :color="rsdStore.data?.is_active ? 'success' : 'error'"
            variant="tonal"
            size="small"
          >
            <v-icon size="small" class="mr-1">
              {{ rsdStore.data?.is_active ? 'mdi-check-circle' : 'mdi-cancel' }}
            </v-icon>
            {{ rsdStore.data?.is_active ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Benutzer</div>
          <div class="text-body-1">{{ rsdStore.data?.users_count || 0 }} Benutzer</div>
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
            {{ rsdStore.isCreateMode ? 'Neuen Tenant erstellen' : 'Tenant bearbeiten' }}
          </h3>
        </v-col>
        
        <v-col cols="12">
          <v-text-field
            v-model="form.name"
            label="Name *"
            variant="outlined"
            density="compact"
            :error-messages="errors.name"
            required
          />
        </v-col>
        
        <v-col cols="12">
          <v-text-field
            v-model="form.slug"
            label="Slug *"
            variant="outlined"
            density="compact"
            :error-messages="errors.slug"
            hint="URL-freundliche Version des Namens"
            persistent-hint
            required
          />
        </v-col>
        
        <v-col cols="12">
          <v-textarea
            v-model="form.description"
            label="Beschreibung"
            variant="outlined"
            density="compact"
            rows="3"
            :error-messages="errors.description"
          />
        </v-col>
        
        <v-col cols="12">
          <v-checkbox
            v-model="form.is_personal"
            label="Persönlicher Workspace"
            :error-messages="errors.is_personal"
            hide-details="auto"
          />
        </v-col>
        
        <v-col cols="12">
          <v-checkbox
            v-model="form.is_active"
            label="Aktiv"
            :error-messages="errors.is_active"
            hide-details="auto"
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
import { ref, watch } from 'vue'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'
import { tenantService } from '../services/tenantService'
import type { Tenant } from '../types'

const rsdStore = useRSDStore()

// Form data
const form = ref({
  name: '',
  slug: '',
  description: '',
  is_personal: false,
  is_active: true,
})

// Form state
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

// Emits
const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
}>()

// Watch for data changes (when opening edit mode)
watch(() => rsdStore.data, (newData) => {
  if (newData && (rsdStore.isEditMode || rsdStore.isViewMode)) {
    form.value = {
      name: newData.name || '',
      slug: newData.slug || '',
      description: newData.description || '',
      is_personal: newData.is_personal || false,
      is_active: newData.is_active ?? true,
    }
  }
}, { immediate: true })

// Reset form when switching to create mode
watch(() => rsdStore.mode, (newMode) => {
  if (newMode === 'create') {
    form.value = {
      name: '',
      slug: '',
      description: '',
      is_personal: false,
      is_active: true,
    }
    errors.value = {}
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

const handleSave = async () => {
  loading.value = true
  errors.value = {}
  
  try {
    let result: Tenant
    
    if (rsdStore.isCreateMode) {
      result = await tenantService.createTenant(form.value)
      emit('success', 'Tenant wurde erfolgreich erstellt')
    } else {
      result = await tenantService.updateTenant(rsdStore.data.id, form.value)
      emit('success', 'Tenant wurde erfolgreich aktualisiert')
    }
    
    // Update RSD data with result
    rsdStore.setData(result)
    rsdStore.handleSuccess()
    
  } catch (error: any) {
    console.error('Error saving tenant:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
    
    const message = error.response?.data?.message || 'Fehler beim Speichern des Tenants'
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
    rsdStore.open('tenant', 'view', rsdStore.data)
  }
}
</script>

<style scoped>
.tenant-rsd {
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