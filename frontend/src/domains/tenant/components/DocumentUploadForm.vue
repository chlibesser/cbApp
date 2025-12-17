<template>
  <v-form ref="form" @submit.prevent="handleSubmit">
    <!-- File Drop Zone -->
    <v-card
      :class="[
        'file-drop-zone mb-4',
        { 'dragover': dragover, 'has-files': selectedFiles.length > 0 }
      ]"
      @drop="onDrop"
      @dragover.prevent="onDragOver"
      @dragleave="onDragLeave"
      @click="triggerFileInput"
    >
      <v-card-text class="text-center pa-8">
        <div v-if="selectedFiles.length === 0">
          <v-icon size="64" color="primary" class="mb-4">mdi-cloud-upload</v-icon>
          <h3 class="text-h6 mb-2">Dateien hier ablegen oder klicken zum Auswählen</h3>
          <p class="text-body-2 text-medium-emphasis">
            Unterstützte Formate: PDF, Word, Excel, PowerPoint, Bilder<br>
            Maximale Dateigröße: 10 MB pro Datei
          </p>
        </div>
        
        <div v-else>
          <v-icon size="48" color="success" class="mb-2">mdi-file-check</v-icon>
          <h4 class="text-h6 mb-2">{{ selectedFiles.length }} Datei(en) ausgewählt</h4>
          <v-btn variant="outlined" @click.stop="clearFiles" class="mt-2">
            <v-icon start>mdi-close</v-icon>
            Auswahl löschen
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Hidden file input -->
    <input
      ref="fileInput"
      type="file"
      multiple
      accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt"
      @change="onFileSelect"
      style="display: none"
    />

    <!-- Selected Files List -->
    <v-card v-if="selectedFiles.length > 0" class="mb-4">
      <v-card-title class="d-flex align-center">
        <v-icon start>mdi-file-multiple</v-icon>
        Ausgewählte Dateien ({{ selectedFiles.length }})
      </v-card-title>
      
      <v-list>
        <v-list-item
          v-for="(file, index) in selectedFiles"
          :key="index"
          class="px-4"
        >
          <template #prepend>
            <v-icon :icon="getFileIcon(file.type)" :color="getFileIconColor(file.type)" />
          </template>
          
          <v-list-item-title>{{ file.name }}</v-list-item-title>
          <v-list-item-subtitle>
            {{ formatFileSize(file.size) }} • {{ file.type || 'Unbekannter Typ' }}
            <span v-if="fileUploadStatus[index]?.message" class="ml-2" :class="getStatusTextColor(fileUploadStatus[index]?.status)">
              {{ fileUploadStatus[index]?.message }}
            </span>
          </v-list-item-subtitle>

          <template #append>
            <!-- Status Icon -->
            <v-icon 
              v-if="fileUploadStatus[index]"
              :icon="getStatusIcon(fileUploadStatus[index]?.status)" 
              :color="getStatusColor(fileUploadStatus[index]?.status)"
              size="20"
              class="mr-2"
            />
            
            <!-- Remove Button (only show if not uploading) -->
            <v-btn
              v-if="!uploading"
              icon="mdi-close"
              variant="text"
              size="small"
              @click="removeFile(index)"
            />
          </template>
        </v-list-item>
      </v-list>
    </v-card>

    <!-- Upload Configuration -->
    <v-card v-if="selectedFiles.length > 0" class="mb-4">
      <v-card-title>Upload-Einstellungen</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-select
              v-model="uploadSettings.visibility"
              :items="visibilityOptions"
              label="Sichtbarkeit"
              prepend-icon="mdi-eye"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="uploadSettings.expires_at"
              label="Ablaufdatum (optional)"
              type="datetime-local"
              prepend-icon="mdi-calendar-clock"
              clearable
            />
          </v-col>
        </v-row>
        
        <!-- Bulk settings for multiple files -->
        <v-row v-if="selectedFiles.length > 1">
          <v-col cols="12">
            <v-switch
              v-model="uploadSettings.apply_to_all"
              label="Einstellungen auf alle Dateien anwenden"
              color="primary"
            />
          </v-col>
        </v-row>

        <!-- Individual file settings -->
        <v-expansion-panels v-if="selectedFiles.length <= 5" class="mt-4">
          <v-expansion-panel
            v-for="(file, index) in selectedFiles"
            :key="index"
            :title="file.name"
          >
            <v-expansion-panel-text>
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="fileSettings[index].title"
                    :label="`Titel für ${file.name}`"
                    :placeholder="getDefaultTitle(file.name)"
                    prepend-icon="mdi-format-title"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="fileSettings[index].description"
                    :label="`Beschreibung für ${file.name}`"
                    rows="2"
                    prepend-icon="mdi-text"
                  />
                </v-col>
              </v-row>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <!-- Category Assignment -->
        <div class="mt-4">
          <h4 class="text-subtitle-1 mb-3">Automatische Kategorisierung</h4>
          <v-switch
            v-model="uploadSettings.auto_categorize"
            label="AI-Kategorisierung aktivieren"
            color="primary"
            class="mb-3"
          />
          
          <v-expand-transition>
            <div v-if="!uploadSettings.auto_categorize">
              <CategorySelector
                v-model="uploadSettings.manual_category_id"
                :tenant-id="currentTenant?.id"
                label="Kategorie manuell zuweisen (optional)"
                clearable
              />
            </div>
          </v-expand-transition>
        </div>
      </v-card-text>
    </v-card>

    <!-- Upload Progress -->
    <v-card v-if="uploading" class="mb-4">
      <v-card-text>
        <div class="d-flex align-center mb-2">
          <v-icon start color="primary">mdi-upload</v-icon>
          <span class="text-subtitle-1">Upload läuft...</span>
          <v-spacer />
          <span class="text-body-2">{{ uploadProgress }}%</span>
        </div>
        
        <v-progress-linear
          :model-value="uploadProgress"
          color="primary"
          height="6"
          rounded
        />
        
        <div v-if="currentFile" class="text-caption text-medium-emphasis mt-2">
          Aktuell: {{ currentFile }}
        </div>
      </v-card-text>
    </v-card>

    <!-- Action Buttons -->
    <div class="d-flex justify-end gap-3">
      <v-btn
        variant="outlined"
        @click="handleCancel"
        :disabled="uploading"
      >
        Abbrechen
      </v-btn>
      
      <v-btn
        type="submit"
        color="primary"
        :disabled="selectedFiles.length === 0 || uploading"
        :loading="uploading"
        prepend-icon="mdi-upload"
      >
        {{ selectedFiles.length === 1 ? 'Datei hochladen' : `${selectedFiles.length} Dateien hochladen` }}
      </v-btn>
    </div>
  </v-form>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useNotifications } from '@/core/composables/useNotifications'
import { useApi } from '@/core/api'
import CategorySelector from '@/shared/components/form/CategorySelector.vue'

interface Props {
  loading?: boolean
}

interface Emits {
  (e: 'uploaded', document: any): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<Emits>()
const { showSuccess, showError } = useNotifications()
const api = useApi()

// Reactive data
const form = ref()
const fileInput = ref<HTMLInputElement>()
const selectedFiles = ref<File[]>([])
const dragover = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const currentFile = ref('')

const uploadSettings = reactive({
  visibility: 'internal',
  expires_at: null,
  apply_to_all: true,
  auto_categorize: true,
  manual_category_id: null
})

const fileSettings = ref<Array<{
  title: string
  description: string
}>>([])

const fileUploadStatus = ref<Array<{
  status: 'pending' | 'uploading' | 'success' | 'error'
  message?: string
}>>([])

// Computed
const currentTenant = computed(() => {
  // TODO: Get from auth store
  return { id: 'current-tenant-id' }
})

const visibilityOptions = [
  { title: 'Intern', value: 'internal' },
  { title: 'Vertraulich', value: 'confidential' },
  { title: 'Eingeschränkt', value: 'restricted' },
  { title: 'Öffentlich', value: 'public' }
]

// Watchers
watch(() => selectedFiles.value.length, (newLength, oldLength) => {
  if (newLength > oldLength) {
    // Add settings for new files
    for (let i = oldLength; i < newLength; i++) {
      fileSettings.value.push({
        title: '',
        description: ''
      })
      fileUploadStatus.value.push({
        status: 'pending'
      })
    }
  } else if (newLength < oldLength) {
    // Remove settings for removed files
    fileSettings.value = fileSettings.value.slice(0, newLength)
    fileUploadStatus.value = fileUploadStatus.value.slice(0, newLength)
  }
})

// Methods
const triggerFileInput = () => {
  fileInput.value?.click()
}

const onFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    addFiles(Array.from(target.files))
  }
}

const onDrop = (event: DragEvent) => {
  event.preventDefault()
  dragover.value = false
  
  if (event.dataTransfer?.files) {
    addFiles(Array.from(event.dataTransfer.files))
  }
}

const onDragOver = () => {
  dragover.value = true
}

const onDragLeave = () => {
  dragover.value = false
}

const addFiles = (files: File[]) => {
  const validFiles = files.filter(file => {
    // File size check (10MB)
    if (file.size > 10 * 1024 * 1024) {
      showError(`Datei "${file.name}" ist zu groß (max. 10 MB)`)
      return false
    }
    
    // Check for duplicates
    if (selectedFiles.value.some(f => f.name === file.name && f.size === file.size)) {
      showError(`Datei "${file.name}" wurde bereits ausgewählt`)
      return false
    }
    
    return true
  })
  
  selectedFiles.value.push(...validFiles)
}

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
}

const clearFiles = () => {
  selectedFiles.value = []
  fileSettings.value = []
  fileUploadStatus.value = []
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const getDefaultTitle = (filename: string): string => {
  return filename.replace(/\.[^/.]+$/, '') // Remove file extension
}

const handleSubmit = async () => {
  if (selectedFiles.value.length === 0) return
  
  uploading.value = true
  uploadProgress.value = 0
  
  // Reset all file statuses to uploading
  fileUploadStatus.value.forEach((status, index) => {
    status.status = 'uploading'
    status.message = undefined
  })
  
  try {
    const response = await uploadFilesBulk()
    const result = response.data.data // Extract nested data from response
    const message = response.data.message // Extract message
    
    // Ensure result has the expected structure
    if (!result || !result.successful || !result.failed || !result.summary) {
      console.error('Invalid response structure:', { response, result })
      throw new Error('Invalid response format from server')
    }
    
    // Update file statuses based on results
    fileUploadStatus.value.forEach((status, index) => {
      status.status = 'pending' // Default
      status.message = undefined
    })
    
    // Mark successful uploads
    result.successful.forEach((success: any) => {
      if (success.index < fileUploadStatus.value.length) {
        fileUploadStatus.value[success.index].status = 'success'
        fileUploadStatus.value[success.index].message = success.message
      }
    })
    
    // Mark failed uploads
    result.failed.forEach((failed: any) => {
      if (failed.index < fileUploadStatus.value.length) {
        fileUploadStatus.value[failed.index].status = 'error'
        fileUploadStatus.value[failed.index].message = failed.error
      }
    })
    
    // Show result message
    if (result.summary.success_count > 0 && result.summary.error_count > 0) {
      showSuccess(message)
    } else if (result.summary.success_count === result.summary.total) {
      showSuccess(message)
    } else {
      showError(message)
    }
    
    // Emit success with details
    emit('uploaded', {
      success: result.summary.success_count > 0,
      data: result
    })
    
    // Reset form only if all files were successful
    if (result.summary.error_count === 0) {
      clearFiles()
    }
    
  } catch (error) {
    console.error('Upload error:', error)
    showError('Fehler beim Upload')
    // Mark all files as error
    fileUploadStatus.value.forEach((status) => {
      status.status = 'error'
      status.message = 'Netzwerkfehler'
    })
  } finally {
    uploading.value = false
    uploadProgress.value = 0
    currentFile.value = ''
  }
}

const uploadFilesBulk = async (): Promise<any> => {
  const formData = new FormData()
  
  // Add all files
  selectedFiles.value.forEach((file, index) => {
    formData.append(`files[${index}]`, file)
  })
  
  // Add common settings
  formData.append('common_settings[visibility]', uploadSettings.visibility)
  if (uploadSettings.expires_at) {
    formData.append('common_settings[expires_at]', uploadSettings.expires_at)
  }
  formData.append('common_settings[auto_categorize]', uploadSettings.auto_categorize.toString())
  if (uploadSettings.manual_category_id) {
    formData.append('common_settings[manual_category_id]', uploadSettings.manual_category_id)
  }
  formData.append('common_settings[upload_batch_id]', Date.now().toString())
  
  // Add individual file settings
  fileSettings.value.forEach((settings, index) => {
    if (settings?.title) {
      formData.append(`file_settings[${index}][title]`, settings.title)
    }
    if (settings?.description) {
      formData.append(`file_settings[${index}][description]`, settings.description)
    }
  })
  
  const response = await api.postFile('/tenant/documents/bulk-upload', formData)
  return response
}

const handleCancel = () => {
  if (uploading.value) return
  
  clearFiles()
  emit('cancel')
}

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const getFileIcon = (mimeType: string): string => {
  if (mimeType.startsWith('image/')) return 'mdi-file-image'
  if (mimeType === 'application/pdf') return 'mdi-file-pdf-box'
  if (mimeType.includes('word')) return 'mdi-file-word'
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'mdi-file-excel'
  if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'mdi-file-powerpoint'
  if (mimeType.startsWith('text/')) return 'mdi-file-document'
  return 'mdi-file'
}

const getFileIconColor = (mimeType: string): string => {
  if (mimeType.startsWith('image/')) return 'green'
  if (mimeType === 'application/pdf') return 'red'
  if (mimeType.includes('word')) return 'blue'
  if (mimeType.includes('excel')) return 'green'
  if (mimeType.includes('powerpoint')) return 'orange'
  return 'grey'
}

const getStatusIcon = (status?: string): string => {
  switch (status) {
    case 'uploading': return 'mdi-loading'
    case 'success': return 'mdi-check-circle'
    case 'error': return 'mdi-alert-circle'
    case 'pending':
    default: return 'mdi-circle-outline'
  }
}

const getStatusColor = (status?: string): string => {
  switch (status) {
    case 'uploading': return 'primary'
    case 'success': return 'success'
    case 'error': return 'error'
    case 'pending':
    default: return 'grey'
  }
}

const getStatusTextColor = (status?: string): string => {
  switch (status) {
    case 'success': return 'text-success'
    case 'error': return 'text-error'
    default: return 'text-medium-emphasis'
  }
}

</script>

<style scoped>
.file-drop-zone {
  border: 2px dashed #ccc;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.file-drop-zone:hover,
.file-drop-zone.dragover {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.file-drop-zone.has-files {
  border-color: rgb(var(--v-theme-success));
  background-color: rgba(var(--v-theme-success), 0.05);
}
</style>