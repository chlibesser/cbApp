<template>
  <div class="action-properties-extended">
    <v-row>
      <!-- Left Column: API Configuration -->
      <v-col cols="12" lg="6">
        <v-card class="mb-4" variant="outlined" v-if="localData.actionType === 'api'">
          <v-card-title class="text-h6 pb-2">
            <v-icon start>mdi-web</v-icon>
            API-Konfiguration
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="6">
                <v-select
                  v-model="localData.method"
                  :items="httpMethods"
                  label="HTTP-Methode"
                  prepend-icon="mdi-web"
                  variant="outlined"
                  density="comfortable"
                  @update:model-value="emitUpdate"
                />
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model.number="localData.timeout"
                  label="Timeout (Sekunden)"
                  prepend-icon="mdi-timer"
                  variant="outlined"
                  density="comfortable"
                  type="number"
                  min="1"
                  max="3600"
                  @update:model-value="emitUpdate"
                />
              </v-col>
            </v-row>
            
            <v-text-field
              v-model="localData.endpoint"
              label="Endpoint URL"
              prepend-icon="mdi-link"
              variant="outlined"
              density="comfortable"
              placeholder="https://api.example.com/{{document.name}}"
              hint="Verwenden Sie Variablen wie {{document.name}}, {{document.size}}, etc."
              persistent-hint
              @update:model-value="emitUpdate"
            />
          </v-card-text>
        </v-card>

        <!-- HTTP Headers -->
        <v-card class="mb-4" variant="outlined" v-if="localData.actionType === 'api'">
          <v-card-title class="text-h6 pb-2">
            <v-icon start>mdi-format-list-bulleted</v-icon>
            HTTP Headers
            <v-spacer />
            <v-btn
              prepend-icon="mdi-plus"
              variant="outlined"
              size="small"
              @click="addHeader"
            >
              Header hinzufügen
            </v-btn>
          </v-card-title>
          <v-card-text>
            <div v-if="localData.headers && localData.headers.length > 0">
              <v-row
                v-for="(header, index) in localData.headers"
                :key="index"
                class="align-center mb-2"
              >
                <v-col cols="4">
                  <v-text-field
                    v-model="header.key"
                    label="Header Name"
                    variant="outlined"
                    density="compact"
                    placeholder="Authorization"
                    @update:model-value="emitUpdate"
                  />
                </v-col>
                <v-col cols="7">
                  <v-text-field
                    v-model="header.value"
                    label="Header Value"
                    variant="outlined"
                    density="compact"
                    placeholder="Bearer {{token}} oder Bearer xyz123"
                    @update:model-value="emitUpdate"
                  />
                </v-col>
                <v-col cols="1" class="d-flex justify-end">
                  <v-btn
                    icon="mdi-delete"
                    variant="text"
                    size="small"
                    color="error"
                    @click="removeHeader(index)"
                  />
                </v-col>
              </v-row>
            </div>
            <v-alert
              v-else
              type="info"
              variant="tonal"
              density="compact"
              class="ma-2"
            >
              <template #prepend>
                <v-icon>mdi-information</v-icon>
              </template>
              Noch keine Header konfiguriert. Fügen Sie Header für Authorization, Content-Type, etc. hinzu.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right Column: Request Body + Variables -->
      <v-col cols="12" lg="6">
        <!-- Request Body Configuration -->
        <v-card 
          class="mb-4" 
          variant="outlined" 
          v-if="localData.actionType === 'api' && ['POST', 'PUT', 'PATCH'].includes(localData.method)"
        >
          <v-card-title class="text-h6 pb-2">
            <v-icon start>mdi-code-json</v-icon>
            Request Body
          </v-card-title>
          <v-card-text>
            <v-tabs v-model="bodyTab" class="mb-3" density="compact">
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
                  rows="12"
                  placeholder='{"filename": "{{document.name}}", "size": {{document.size}}, "type": "{{document.type}}"}'
                  hint="Verwenden Sie JSON-Format mit Variablen"
                  persistent-hint
                  @update:model-value="updateBodyType('json')"
                />
              </v-window-item>

              <!-- Form Data Body -->
              <v-window-item value="form">
                <div v-if="localData.body.formData && localData.body.formData.length > 0">
                  <v-row
                    v-for="(field, index) in localData.body.formData"
                    :key="index"
                    class="align-center mb-2"
                  >
                    <v-col cols="5">
                      <v-text-field
                        v-model="field.key"
                        label="Field Name"
                        variant="outlined"
                        density="compact"
                        placeholder="filename"
                        @update:model-value="updateBodyType('form')"
                      />
                    </v-col>
                    <v-col cols="6">
                      <v-text-field
                        v-model="field.value"
                        label="Field Value"
                        variant="outlined"
                        density="compact"
                        placeholder="{{document.name}}"
                        @update:model-value="updateBodyType('form')"
                      />
                    </v-col>
                    <v-col cols="1">
                      <v-btn
                        icon="mdi-delete"
                        variant="text"
                        size="small"
                        color="error"
                        @click="removeFormField(index)"
                      />
                    </v-col>
                  </v-row>
                </div>
                
                <v-btn
                  prepend-icon="mdi-plus"
                  variant="outlined"
                  size="small"
                  block
                  @click="addFormField"
                  class="mt-2"
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
                  rows="12"
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
        <v-card class="mb-4" variant="tonal" color="info">
          <v-card-title class="text-h6 pb-2">
            <v-icon start>mdi-variable</v-icon>
            Verfügbare Variablen
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col
                v-for="variable in availableVariables"
                :key="variable.key"
                cols="12" sm="6" md="4"
              >
                <v-chip
                  :text="variable.key"
                  variant="outlined"
                  size="small"
                  class="mb-2 mr-2 cursor-pointer"
                  @click="copyVariable(variable.key)"
                  :title="variable.description"
                >
                  <template #prepend>
                    <v-icon size="small">mdi-content-copy</v-icon>
                  </template>
                </v-chip>
              </v-col>
            </v-row>
            <v-divider class="my-3" />
            <p class="text-caption text-medium-emphasis mb-2">
              <v-icon size="small" class="mr-1">mdi-lightbulb</v-icon>
              <strong>Tipps:</strong>
            </p>
            <ul class="text-caption text-medium-emphasis">
              <li>Klicken Sie auf eine Variable, um sie in die Zwischenablage zu kopieren</li>
              <li>Variablen werden zur Laufzeit durch echte Document-Werte ersetzt</li>
              <li>Verwenden Sie &#123;&#123;document.name&#125;&#125; für Dateinamen in URLs und Headers</li>
              <li>&#123;&#123;document.size&#125;&#125; und &#123;&#123;document.type&#125;&#125; sind nützlich für API-Metadaten</li>
            </ul>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
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

const httpMethods = [
  { title: 'GET', value: 'GET' },
  { title: 'POST', value: 'POST' },
  { title: 'PUT', value: 'PUT' },
  { title: 'PATCH', value: 'PATCH' },
  { title: 'DELETE', value: 'DELETE' }
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
    parameters: newData.parameters || {},
    headers: newData.headers || [],
    body: newData.body || {
      type: 'json',
      json: '',
      formData: [],
      raw: ''
    }
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
.cursor-pointer {
  cursor: pointer;
}

:deep(.v-chip) {
  transition: all 0.2s ease;
}

:deep(.v-chip):hover {
  transform: scale(1.05);
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>