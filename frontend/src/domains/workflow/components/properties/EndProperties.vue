<template>
  <div class="end-properties">
    <v-card class="mb-4" variant="outlined">
      <v-card-title class="text-h6 pb-2">End-Konfiguration</v-card-title>
      <v-card-text>
        <!-- End Type -->
        <v-select
          v-model="localData.endType"
          :items="endTypes"
          label="End-Typ"
          prepend-icon="mdi-stop-circle"
          variant="outlined"
          density="compact"
          @update:model-value="emitUpdate"
        />
        
        <!-- Return Message -->
        <v-textarea
          v-model="localData.message"
          label="Abschlussnachricht"
          prepend-icon="mdi-message-text"
          variant="outlined"
          density="compact"
          rows="2"
          placeholder="Nachricht, die beim Ende des Workflows angezeigt wird"
          @update:model-value="emitUpdate"
        />
        
        <!-- Return Value -->
        <v-textarea
          v-model="returnValueString"
          label="Rückgabewert (JSON)"
          prepend-icon="mdi-code-json"
          variant="outlined"
          density="compact"
          rows="3"
          placeholder='{ "status": "completed", "result": "success" }'
          @update:model-value="updateReturnValue"
        />
        
        <!-- Cleanup Option -->
        <v-switch
          v-model="localData.cleanup"
          label="Aufräumen nach Beendigung"
          color="primary"
          inset
          @update:model-value="emitUpdate"
        >
          <template #details>
            Temporäre Dateien und Ressourcen nach dem Workflow löschen
          </template>
        </v-switch>
      </v-card-text>
    </v-card>
    
    <!-- Preview -->
    <v-card variant="outlined">
      <v-card-title class="text-h6 pb-2">Vorschau</v-card-title>
      <v-card-text>
        <v-alert
          :type="getAlertType(localData.endType)"
          variant="tonal"
          density="compact"
        >
          <template #prepend>
            <v-icon>{{ getEndIcon(localData.endType) }}</v-icon>
          </template>
          
          <div class="font-weight-medium mb-1">
            Workflow wird beendet: {{ getEndTypeLabel(localData.endType) }}
          </div>
          
          <div v-if="localData.message" class="text-body-2">
            {{ localData.message }}
          </div>
          
          <div v-if="localData.returnValue" class="text-caption mt-2">
            Rückgabe: <code>{{ JSON.stringify(localData.returnValue) }}</code>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { EndNodeData } from '../../types'

interface Props {
  data: EndNodeData
}

interface Emits {
  (e: 'update', data: EndNodeData): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const localData = ref<EndNodeData>({ ...props.data })

const returnValueString = ref(
  localData.value.returnValue 
    ? JSON.stringify(localData.value.returnValue, null, 2)
    : ''
)

const endTypes = [
  { title: 'Erfolgreich', value: 'success' },
  { title: 'Fehlgeschlagen', value: 'failure' },
  { title: 'Anhalten', value: 'stop' },
  { title: 'Beenden', value: 'terminate' }
]

// Watch for external changes
watch(() => props.data, (newData) => {
  localData.value = { ...newData }
  returnValueString.value = newData.returnValue 
    ? JSON.stringify(newData.returnValue, null, 2)
    : ''
}, { deep: true })

// Helper functions
function getEndTypeLabel(endType: string): string {
  const labels = {
    success: 'Erfolgreich',
    failure: 'Fehlgeschlagen', 
    stop: 'Angehalten',
    terminate: 'Beendet'
  }
  return labels[endType as keyof typeof labels] || 'Unbekannt'
}

function getEndIcon(endType: string): string {
  const icons = {
    success: 'mdi-check-circle',
    failure: 'mdi-alert-circle',
    stop: 'mdi-pause-circle',
    terminate: 'mdi-stop-circle'
  }
  return icons[endType as keyof typeof icons] || 'mdi-stop-circle'
}

function getAlertType(endType: string): string {
  const types = {
    success: 'success',
    failure: 'error',
    stop: 'warning',
    terminate: 'info'
  }
  return types[endType as keyof typeof types] || 'info'
}

// Update return value from JSON string
function updateReturnValue() {
  try {
    if (returnValueString.value.trim()) {
      localData.value.returnValue = JSON.parse(returnValueString.value)
    } else {
      localData.value.returnValue = undefined
    }
    emitUpdate()
  } catch (error) {
    // Invalid JSON - don't update the value
    console.warn('Invalid JSON in return value:', error)
  }
}

// Emit changes
function emitUpdate() {
  emit('update', { ...localData.value })
}
</script>

<style scoped>
code {
  background-color: rgba(var(--v-theme-surface-variant), 0.1);
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 0.85em;
}
</style>