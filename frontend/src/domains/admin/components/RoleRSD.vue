<template>
  <div class="role-rsd">
    <!-- View Mode -->
    <v-container v-if="rsdStore.isViewMode" class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">Role-Details</h3>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Name</div>
          <div class="text-body-1">{{ rsdStore.data?.name || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Beschreibung</div>
          <div class="text-body-1">{{ rsdStore.data?.description || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Tenant</div>
          <div class="text-body-1">{{ rsdStore.data?.tenant?.name || '-' }}</div>
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
            {{ rsdStore.isCreateMode ? 'Neue Role erstellen' : 'Role bearbeiten' }}
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
          <v-textarea
            v-model="form.description"
            label="Beschreibung"
            variant="outlined"
            density="compact"
            rows="3"
            :error-messages="errors.description"
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

const rsdStore = useRSDStore()

// Form data
const form = ref({
  name: '',
  description: '',
})

// Form state
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

// Emits
const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
}>()

// Watch for data changes
watch(() => rsdStore.data, (newData) => {
  if (newData && (rsdStore.isEditMode || rsdStore.isViewMode)) {
    form.value = {
      name: newData.name || '',
      description: newData.description || '',
    }
  }
}, { immediate: true })

// Reset form when switching to create mode
watch(() => rsdStore.mode, (newMode) => {
  if (newMode === 'create') {
    form.value = {
      name: '',
      description: '',
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
    // TODO: Implement role service calls
    emit('success', 'Role-Funktionalität wird noch implementiert')
    
  } catch (error: any) {
    console.error('Error saving role:', error)
    emit('error', 'Fehler beim Speichern der Role')
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  if (rsdStore.isCreateMode) {
    rsdStore.close()
  } else {
    rsdStore.open('role', 'view', rsdStore.data)
  }
}
</script>

<style scoped>
.role-rsd {
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