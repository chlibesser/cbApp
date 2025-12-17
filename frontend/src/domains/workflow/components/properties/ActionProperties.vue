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
            placeholder="https://api.example.com/{{document.name}}"
            hint="Verwenden Sie {{document.name}}, {{document.size}}, {{document.type}} für dynamische Werte"
            persistent-hint
            @update:model-value="emitUpdate"
          />

          <!-- HTTP Headers Configuration -->
          <v-card class="mt-3" variant="outlined">
            <v-card-title class="text-subtitle-1 pb-2">
              <v-icon start>mdi-format-list-bulleted</v-icon>
              HTTP Headers
            </v-card-title>
            <v-card-text>
              <div v-for="(header, index) in localData.headers" :key="index" class="d-flex align-center mb-2">
                <v-text-field
                  v-model="header.key"
                  label="Header Name"
                  variant="outlined"
                  density="compact"
                  class="mr-2"
                  placeholder="Authorization"
                  @update:model-value="emitUpdate"
                />
                <v-text-field
                  v-model="header.value"
                  label="Header Value"
                  variant="outlined"
                  density="compact"
                  class="mr-2"
                  placeholder="Bearer {{token}} oder Bearer xyz123"
                  @update:model-value="emitUpdate"
                />
                <v-btn
                  icon="mdi-delete"
                  variant="text"
                  size="small"
                  color="error"
                  @click="removeHeader(index)"
                />
              </div>
              
              <v-btn
                prepend-icon="mdi-plus"
                variant="outlined"
                size="small"
                @click="addHeader"
              >
                Header hinzufügen
              </v-btn>
            </v-card-text>
          </v-card>

          <!-- Request Body Configuration -->
          <v-card class="mt-3" variant="outlined" v-if="['POST', 'PUT', 'PATCH'].includes(localData.method)">
            <v-card-title class="text-subtitle-1 pb-2">
              <v-icon start>mdi-code-json</v-icon>
              Request Body
            </v-card-title>
            <v-card-text>
              <v-tabs v-model="bodyTab" class="mb-3">
                <v-tab value="json">JSON</v-tab>
                <v-tab value="form">Form Data</v-tab>
                <v-tab value="raw">Raw</v-tab>
              </v-tabs>

              <v-window v-model="bodyTab">
                <!-- JSON Body -->
                <v-window-item value="json">
                  <v-textarea
                    v-model="localData.body.json"
                    label="JSON Body"
                    variant="outlined"
                    rows="6"
                    placeholder='{"filename": "{{document.name}}", "size": {{document.size}}, "type": "{{document.type}}"}'
                    hint="Verwenden Sie JSON-Format mit Variablen"
                    persistent-hint
                    @update:model-value="updateBodyType('json')"
                  />
                </v-window-item>

                <!-- Form Data Body -->
                <v-window-item value="form">
                  <div v-for="(field, index) in localData.body.formData" :key="index" class="d-flex align-center mb-2">
                    <v-text-field
                      v-model="field.key"
                      label="Field Name"
                      variant="outlined"
                      density="compact"
                      class="mr-2"
                      placeholder="filename"
                      @update:model-value="updateBodyType('form')"
                    />
                    <v-text-field
                      v-model="field.value"
                      label="Field Value"
                      variant="outlined"
                      density="compact"
                      class="mr-2"
                      placeholder="{{document.name}}"
                      @update:model-value="updateBodyType('form')"
                    />
                    <v-btn
                      icon="mdi-delete"
                      variant="text"
                      size="small"
                      color="error"
                      @click="removeFormField(index)"
                    />
                  </div>
                  
                  <v-btn
                    prepend-icon="mdi-plus"
                    variant="outlined"
                    size="small"
                    @click="addFormField"
                  >
                    Feld hinzufügen
                  </v-btn>
                </v-window-item>

                <!-- Raw Body -->
                <v-window-item value="raw">
                  <v-textarea
                    v-model="localData.body.raw"
                    label="Raw Body"
                    variant="outlined"
                    rows="4"
                    placeholder="Document: {{document.name}} ({{document.size}} bytes)"
                    hint="Plain Text mit Variablen"
                    persistent-hint
                    @update:model-value="updateBodyType('raw')"
                  />
                </v-window-item>
              </v-window>
            </v-card-text>
          </v-card>

          <!-- Variables Helper -->
          <v-card class="mt-3" variant="tonal" color="info">
            <v-card-title class="text-subtitle-1 pb-2">
              <v-icon start>mdi-variable</v-icon>
              Verfügbare Variablen
            </v-card-title>
            <v-card-text>
              <v-chip-group class="mb-2">
                <v-chip 
                  v-for="variable in availableVariables" 
                  :key="variable.key"
                  size="small"
                  variant="outlined"
                  @click="copyVariable(variable.key)"
                >
                  {{ variable.key }}
                </v-chip>
              </v-chip-group>
              <p class="text-caption text-medium-emphasis">
                Klicken Sie auf eine Variable, um sie zu kopieren. Die Variablen werden zur Laufzeit durch die tatsächlichen Document-Werte ersetzt.
              </p>
            </v-card-text>
          </v-card>
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

// Reactive state for body tab
const bodyTab = ref('json')

const localData = ref<ActionNodeData>({ 
  ...props.data,
  parameters: props.data.parameters || {},
  headers: props.data.headers || [],
  body: props.data.body || {
    type: 'json',
    json: '',
    formData: [],
    raw: ''
  }
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

// Available variables for template system
const availableVariables = [
  { key: '{{document.name}}', description: 'Dateiname' },
  { key: '{{document.size}}', description: 'Dateigröße in Bytes' },
  { key: '{{document.type}}', description: 'MIME-Type' },
  { key: '{{document.extension}}', description: 'Datei-Erweiterung' },
  { key: '{{document.uploader}}', description: 'Uploader-Name' },
  { key: '{{document.tenant}}', description: 'Tenant-Name' },
  { key: '{{document.id}}', description: 'Document-UUID' },
  { key: '{{document.url}}', description: 'Download-URL' }
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

// Header management functions
function addHeader() {
  if (!localData.value.headers) {
    localData.value.headers = []
  }
  localData.value.headers.push({ key: '', value: '' })
  emitUpdate()
}

function removeHeader(index: number) {
  localData.value.headers.splice(index, 1)
  emitUpdate()
}

// Form data management functions
function addFormField() {
  if (!localData.value.body.formData) {
    localData.value.body.formData = []
  }
  localData.value.body.formData.push({ key: '', value: '' })
  emitUpdate()
}

function removeFormField(index: number) {
  localData.value.body.formData.splice(index, 1)
  emitUpdate()
}

// Body type management
function updateBodyType(type: string) {
  if (!localData.value.body) {
    localData.value.body = { type: 'json', json: '', formData: [], raw: '' }
  }
  localData.value.body.type = type
  emitUpdate()
}

// Variable helper functions
function copyVariable(variable: string) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(variable).then(() => {
      // Could show a toast here
      console.log('Variable copied:', variable)
    })
  }
}
</script>

<style scoped>
/* Keine zusätzlichen Styles erforderlich */
</style>