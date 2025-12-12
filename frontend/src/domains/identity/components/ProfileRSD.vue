<template>
  <div class="profile-rsd">
    <!-- View Mode -->
    <v-container v-if="rsdStore.isViewMode" class="pa-4 pl-2 pr-4">
      <v-row>
        <v-col cols="12">
          <h3 class="text-h6 mb-4">Profil-Details</h3>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Vorname</div>
          <div class="text-body-1">{{ rsdStore.data?.first_name || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Nachname</div>
          <div class="text-body-1">{{ rsdStore.data?.last_name || '-' }}</div>
        </v-col>
        
        <v-col cols="12">
          <div class="text-caption text-grey mb-1">Account</div>
          <div class="text-body-1">{{ rsdStore.data?.account?.email || '-' }}</div>
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
            {{ rsdStore.isCreateMode ? 'Neues Profil erstellen' : 'Profil bearbeiten' }}
          </h3>
        </v-col>
        
        <v-col cols="12">
          <v-text-field
            v-model="form.first_name"
            label="Vorname *"
            variant="outlined"
            density="compact"
            :error-messages="errors.first_name"
          />
        </v-col>
        
        <v-col cols="12">
          <v-text-field
            v-model="form.last_name"
            label="Nachname *"
            variant="outlined"
            density="compact"
            :error-messages="errors.last_name"
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
  first_name: '',
  last_name: '',
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
      first_name: newData.first_name || '',
      last_name: newData.last_name || '',
    }
  }
}, { immediate: true })

// Reset form when switching to create mode
watch(() => rsdStore.mode, (newMode) => {
  if (newMode === 'create') {
    form.value = {
      first_name: '',
      last_name: '',
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
    // TODO: Implement profile service calls
    emit('success', 'Profil-Funktionalität wird noch implementiert')
    
  } catch (error: any) {
    console.error('Error saving profile:', error)
    emit('error', 'Fehler beim Speichern des Profils')
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  if (rsdStore.isCreateMode) {
    rsdStore.close()
  } else {
    rsdStore.open('profile', 'view', rsdStore.data)
  }
}
</script>

<style scoped>
.profile-rsd {
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