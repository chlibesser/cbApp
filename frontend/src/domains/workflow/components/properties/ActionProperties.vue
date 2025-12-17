<template>
  <div class="action-properties">
    <v-card class="mb-4" variant="outlined">
      <v-card-title class="text-h6 pb-2">Action-Konfiguration</v-card-title>
      <v-card-text>
        <!-- Action Type -->
        <v-select
          v-model="localData.actionType"
          :items="actionTypes"
          label="Action-Typ"
          prepend-icon="mdi-cog"
          variant="outlined"
          density="compact"
          @update:model-value="emitUpdate"
        />
        
        <!-- API Configuration -->
        <template v-if="localData.actionType === 'api'">
          <v-select
            v-model="localData.method"
            :items="httpMethods"
            label="HTTP-Methode"
            prepend-icon="mdi-web"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
          
          <v-text-field
            v-model="localData.endpoint"
            label="Endpoint URL"
            prepend-icon="mdi-link"
            variant="outlined"
            density="compact"
            placeholder="https://api.example.com/endpoint"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Email Configuration -->
        <template v-if="localData.actionType === 'email'">
          <v-text-field
            v-model="localData.parameters.to"
            label="Empfänger"
            prepend-icon="mdi-email"
            variant="outlined"
            density="compact"
            placeholder="user@example.com"
            @update:model-value="emitUpdate"
          />
          
          <v-text-field
            v-model="localData.parameters.subject"
            label="Betreff"
            prepend-icon="mdi-email-subject"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
          
          <v-textarea
            v-model="localData.parameters.body"
            label="Nachricht"
            prepend-icon="mdi-message-text"
            variant="outlined"
            density="compact"
            rows="3"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Database Configuration -->
        <template v-if="localData.actionType === 'database'">
          <v-select
            v-model="localData.parameters.operation"
            :items="dbOperations"
            label="Datenbankoperation"
            prepend-icon="mdi-database"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
          
          <v-text-field
            v-model="localData.parameters.table"
            label="Tabelle"
            prepend-icon="mdi-table"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- File Operations -->
        <template v-if="localData.actionType === 'file'">
          <v-select
            v-model="localData.parameters.operation"
            :items="fileOperations"
            label="Datei-Operation"
            prepend-icon="mdi-file"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
          
          <v-text-field
            v-model="localData.parameters.path"
            label="Dateipfad"
            prepend-icon="mdi-folder"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Notification Configuration -->
        <template v-if="localData.actionType === 'notification'">
          <v-text-field
            v-model="localData.parameters.title"
            label="Titel"
            prepend-icon="mdi-bell"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
          
          <v-textarea
            v-model="localData.parameters.message"
            label="Nachricht"
            prepend-icon="mdi-message"
            variant="outlined"
            density="compact"
            rows="2"
            @update:model-value="emitUpdate"
          />
        </template>
      </v-card-text>
    </v-card>
    
    <!-- Advanced Settings -->
    <v-card variant="outlined">
      <v-card-title class="text-h6 pb-2">Erweiterte Einstellungen</v-card-title>
      <v-card-text>
        <v-text-field
          v-model.number="localData.timeout"
          label="Timeout (Sekunden)"
          prepend-icon="mdi-timer"
          variant="outlined"
          density="compact"
          type="number"
          min="1"
          max="3600"
          @update:model-value="emitUpdate"
        />
        
        <v-text-field
          v-model.number="localData.retries"
          label="Wiederholungsversuche"
          prepend-icon="mdi-repeat"
          variant="outlined"
          density="compact"
          type="number"
          min="0"
          max="10"
          @update:model-value="emitUpdate"
        />
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { ActionNodeData } from '../../types'

interface Props {
  data: ActionNodeData
}

interface Emits {
  (e: 'update', data: ActionNodeData): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const localData = ref<ActionNodeData>({ 
  ...props.data,
  parameters: props.data.parameters || {}
})

const actionTypes = [
  { title: 'API-Aufruf', value: 'api' },
  { title: 'E-Mail senden', value: 'email' },
  { title: 'Datenbank-Operation', value: 'database' },
  { title: 'Datei-Operation', value: 'file' },
  { title: 'Benachrichtigung', value: 'notification' },
  { title: 'Benutzerdefiniert', value: 'custom' }
]

const httpMethods = [
  { title: 'GET', value: 'GET' },
  { title: 'POST', value: 'POST' },
  { title: 'PUT', value: 'PUT' },
  { title: 'PATCH', value: 'PATCH' },
  { title: 'DELETE', value: 'DELETE' }
]

const dbOperations = [
  { title: 'Einfügen', value: 'insert' },
  { title: 'Aktualisieren', value: 'update' },
  { title: 'Löschen', value: 'delete' },
  { title: 'Abfragen', value: 'select' }
]

const fileOperations = [
  { title: 'Lesen', value: 'read' },
  { title: 'Schreiben', value: 'write' },
  { title: 'Kopieren', value: 'copy' },
  { title: 'Verschieben', value: 'move' },
  { title: 'Löschen', value: 'delete' }
]

// Watch for external changes
watch(() => props.data, (newData) => {
  localData.value = { 
    ...newData,
    parameters: newData.parameters || {}
  }
}, { deep: true })

// Emit changes
function emitUpdate() {
  emit('update', { ...localData.value })
}
</script>

<style scoped>
/* Keine zusätzlichen Styles erforderlich */
</style>