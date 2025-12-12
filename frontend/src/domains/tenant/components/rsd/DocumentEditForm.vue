<template>
  <div class="document-edit pa-4">
    <v-form ref="form" v-model="valid" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-title>Dokument bearbeiten</v-card-title>
        
        <v-card-text>
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="formData.title"
                label="Titel"
                :rules="[rules.required]"
                variant="outlined"
                density="compact"
              />
            </v-col>
            
            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                label="Beschreibung"
                variant="outlined"
                density="compact"
                rows="3"
              />
            </v-col>
            
            <v-col cols="12" sm="6">
              <v-select
                v-model="formData.visibility"
                label="Sichtbarkeit"
                :items="visibilityOptions"
                item-title="label"
                item-value="value"
                variant="outlined"
                density="compact"
              />
            </v-col>
            
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="formData.expires_at"
                label="Läuft ab am"
                type="datetime-local"
                variant="outlined"
                density="compact"
                clearable
              />
            </v-col>
          </v-row>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="$emit('close')">Abbrechen</v-btn>
          <v-btn
            color="primary"
            type="submit"
            :loading="loading"
            :disabled="!valid"
          >
            Speichern
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { api } from '@/core/api'
import type { Document } from '@/shared/types/document'
import type { RSDEntity, RSDMode } from '@/infrastructure/stores/rsdStore'

interface Props {
  entity?: RSDEntity
  mode?: RSDMode
  data?: Document
  document?: Document  // Direct document prop for standalone use
}

const props = defineProps<Props>()
const emit = defineEmits<{
  success: [message: string]
  error: [error: string]
  close: []
}>()

const document = computed(() => props.data || props.document)
const form = ref()
const valid = ref(false)
const loading = ref(false)

const formData = reactive({
  title: document.value?.title || document.value?.original_filename || '',
  description: document.value?.description || '',
  visibility: document.value?.visibility || 'internal',
  expires_at: document.value?.expires_at ? new Date(document.value.expires_at).toISOString().slice(0, 16) : ''
})

const visibilityOptions = [
  { label: 'Öffentlich', value: 'public' },
  { label: 'Intern', value: 'internal' },
  { label: 'Vertraulich', value: 'confidential' },
  { label: 'Eingeschränkt', value: 'restricted' }
]

const rules = {
  required: (value: string) => !!value || 'Dieses Feld ist erforderlich'
}

const handleSubmit = async () => {
  if (!valid.value) return
  
  loading.value = true
  
  if (!document.value) {
    emit('error', 'Kein Dokument zum Bearbeiten gefunden')
    loading.value = false
    return
  }

  try {
    const response = await api.put(`/tenant/documents/${document.value.id}`, {
      title: formData.title,
      description: formData.description,
      visibility: formData.visibility,
      expires_at: formData.expires_at || null
    })
    
    emit('success', 'Dokument erfolgreich aktualisiert')
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Fehler beim Aktualisieren des Dokuments'
    emit('error', errorMessage)
  } finally {
    loading.value = false
  }
}

watch(() => document.value, (newData) => {
  if (newData) {
    formData.title = newData.title || newData.original_filename
    formData.description = newData.description || ''
    formData.visibility = newData.visibility || 'internal'
    formData.expires_at = newData.expires_at ? new Date(newData.expires_at).toISOString().slice(0, 16) : ''
  }
}, { immediate: true })
</script>
</template>