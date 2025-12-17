<template>
  <div class="document-detail">
    <!-- Loading State -->
    <div v-if="loading" class="d-flex justify-center align-center pa-8">
      <v-progress-circular indeterminate size="64" />
    </div>

    <!-- Error State -->
    <v-alert v-else-if="error" type="error" class="ma-4">
      {{ error }}
    </v-alert>

    <!-- Document Content -->
    <div v-else-if="document" class="document-content">
      <!-- Header with Navigation -->
      <div class="document-header mb-6">
        <v-breadcrumbs :items="breadcrumbs" class="pa-0 mb-4">
          <template #prepend>
            <v-icon size="small">mdi-folder</v-icon>
          </template>
        </v-breadcrumbs>

        <div class="d-flex align-center mb-4">
          <v-icon 
            :icon="getFileIcon(document.mime_type)" 
            :color="getFileIconColor(document.mime_type)"
            size="40" 
            class="mr-4"
          />
          <div class="flex-grow-1">
            <h1 class="text-h4 font-weight-bold">{{ document.title || document.original_filename }}</h1>
            <p class="text-body-1 text-medium-emphasis mb-0">
              {{ document.mime_type }} • {{ formatFileSize(document.file_size) }}
              <v-chip 
                :color="getStatusColor(document.status)" 
                size="small" 
                variant="flat"
                class="ml-2"
              >
                <v-icon 
                  :icon="getStatusIcon(document.status)" 
                  size="12" 
                  start
                />
                {{ getStatusLabel(document.status) }}
              </v-chip>
            </p>
          </div>
          
          <div class="d-flex gap-2">
            <v-btn
              color="primary"
              prepend-icon="mdi-download"
              @click="downloadDocument"
              :disabled="document.status === 'deleted'"
            >
              Download
            </v-btn>
            
            <v-btn
              variant="outlined"
              prepend-icon="mdi-pencil"
              @click="editDocument"
            >
              Bearbeiten
            </v-btn>
            
            <v-menu>
              <template #activator="{ props: menuProps }">
                <v-btn
                  v-bind="menuProps"
                  icon="mdi-dots-vertical"
                  variant="outlined"
                />
              </template>
              
              <v-list density="compact">
                <v-list-item @click="shareDocument" prepend-icon="mdi-share-variant">
                  <v-list-item-title>Teilen</v-list-item-title>
                </v-list-item>
                <v-list-item @click="manageCategories" prepend-icon="mdi-tag-multiple">
                  <v-list-item-title>Kategorien</v-list-item-title>
                </v-list-item>
                <v-list-item @click="processWithAI" prepend-icon="mdi-robot" v-if="document.ai_processing_status !== 'completed'">
                  <v-list-item-title>AI-Verarbeitung</v-list-item-title>
                </v-list-item>
                <v-divider />
                <v-list-item 
                  @click="deleteDocument" 
                  prepend-icon="mdi-delete" 
                  class="text-error"
                  :disabled="document.status === 'deleted'"
                >
                  <v-list-item-title>Löschen</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </div>
        </div>

        <!-- Status Cards -->
        <v-row class="mb-4">
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" class="text-center">
              <v-card-text class="pa-4">
                <v-chip 
                  :color="getAIStatusColor(document.ai_processing_status)" 
                  size="small" 
                  variant="outlined"
                  class="mb-2"
                >
                  <v-icon 
                    :icon="getAIStatusIcon(document.ai_processing_status)" 
                    size="12" 
                    start
                  />
                  {{ getAIStatusLabel(document.ai_processing_status) }}
                </v-chip>
                <div class="text-caption">AI-Status</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" class="text-center">
              <v-card-text class="pa-4">
                <div class="text-h6 font-weight-bold">{{ document.download_count }}</div>
                <div class="text-caption">Downloads</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" class="text-center">
              <v-card-text class="pa-4">
                <div class="text-h6 font-weight-bold">{{ document.view_count }}</div>
                <div class="text-caption">Aufrufe</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" class="text-center">
              <v-card-text class="pa-4">
                <v-chip 
                  :color="getVisibilityColor(document.visibility)" 
                  size="small"
                  variant="outlined"
                  class="mb-2"
                >
                  {{ getVisibilityLabel(document.visibility) }}
                </v-chip>
                <div class="text-caption">Sichtbarkeit</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </div>

      <!-- Main Content Area -->
      <v-row>
        <!-- Left Column - Main Content -->
        <v-col cols="12" lg="8">
          <!-- Document Preview Card -->
          <v-card v-if="document.mime_type === 'application/pdf'" class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-file-pdf-box</v-icon>
              Dokumenten-Vorschau
              <v-spacer />
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-fullscreen"
                @click="openFullscreenPDF"
              >
                Vollbild
              </v-btn>
            </v-card-title>
            <v-card-text class="pa-0">
              <div class="pdf-preview-container">
                <iframe
                  v-if="signedUrls?.preview_url && signedUrls.supports_preview"
                  :src="signedUrls.preview_url"
                  class="pdf-viewer"
                  frameborder="0"
                  @load="onPDFLoaded"
                  @error="onPDFError"
                />
                <div v-if="pdfLoading || !signedUrls?.preview_url" class="pdf-loading">
                  <v-progress-circular indeterminate />
                  <p class="mt-2">PDF wird geladen...</p>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Image Preview Card -->
          <v-card v-else-if="document.mime_type.startsWith('image/')" class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-file-image</v-icon>
              Bildvorschau
            </v-card-title>
            <v-card-text class="text-center pa-4">
              <img
                :src="imagePreviewUrl"
                :alt="document.original_filename"
                class="image-preview"
                @load="onImageLoaded"
                @error="onImageError"
              />
            </v-card-text>
          </v-card>

          <!-- Text File Preview -->
          <v-card v-else-if="document.mime_type.startsWith('text/') && document.extracted_text" class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-file-document</v-icon>
              Text-Vorschau
            </v-card-title>
            <v-card-text>
              <div class="text-file-preview">
                {{ document.extracted_text.substring(0, 2000) }}
                <span v-if="document.extracted_text.length > 2000">...</span>
              </div>
            </v-card-text>
          </v-card>

          <!-- No Preview Available -->
          <v-card v-else class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-file</v-icon>
              Dokumenten-Info
            </v-card-title>
            <v-card-text class="text-center pa-8">
              <v-icon size="64" color="grey-lighten-2" class="mb-4">
                {{ getFileIcon(document.mime_type) }}
              </v-icon>
              <h3 class="text-h6 mb-2">{{ document.original_filename }}</h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Vorschau für {{ document.mime_type }} nicht verfügbar
              </p>
              <v-btn
                color="primary"
                variant="outlined"
                prepend-icon="mdi-download"
                @click="downloadDocument"
              >
                Datei herunterladen
              </v-btn>
            </v-card-text>
          </v-card>

          <!-- AI Summary Card -->
          <v-card v-if="document.ai_summary" class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-text-long</v-icon>
              AI-Zusammenfassung
            </v-card-title>
            <v-card-text>
              <p class="text-body-1">{{ document.ai_summary }}</p>
            </v-card-text>
          </v-card>

          <!-- Extracted Text Card -->
          <v-card v-if="document.extracted_text" class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-text-recognition</v-icon>
              Extrahierter Text
              <v-spacer />
              <v-chip size="small" color="info">
                {{ document.extracted_text.length }} Zeichen
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="extracted-text-preview">
                {{ showFullText ? document.extracted_text : document.extracted_text.substring(0, 1000) }}
                <span v-if="!showFullText && document.extracted_text.length > 1000">...</span>
              </div>
              <v-btn 
                v-if="document.extracted_text.length > 1000"
                variant="text" 
                size="small"
                class="mt-3"
                @click="showFullText = !showFullText"
              >
                {{ showFullText ? 'Weniger anzeigen' : 'Vollständigen Text anzeigen' }}
              </v-btn>
            </v-card-text>
          </v-card>

          <!-- AI Tags -->
          <v-card v-if="document.ai_tags?.length" class="mb-6">
            <v-card-title>
              <v-icon start>mdi-tag-text</v-icon>
              AI-Tags
            </v-card-title>
            <v-card-text>
              <div class="d-flex flex-wrap gap-2">
                <v-chip 
                  v-for="tag in document.ai_tags" 
                  :key="tag"
                  size="small"
                  color="info"
                  variant="outlined"
                >
                  {{ tag }}
                </v-chip>
              </div>
            </v-card-text>
          </v-card>

          <!-- AI Metadata -->
          <v-card v-if="document.ai_metadata" class="mb-6">
            <v-card-title>
              <v-icon start>mdi-database</v-icon>
              AI-Metadaten
            </v-card-title>
            <v-card-text>
              <pre class="ai-metadata">{{ JSON.stringify(document.ai_metadata, null, 2) }}</pre>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Right Column - Sidebar -->
        <v-col cols="12" lg="4">
          <!-- Document Details -->
          <v-card class="mb-6">
            <v-card-title>Dokumentinformationen</v-card-title>
            <v-card-text>
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Originalname</div>
                <div class="text-body-2">{{ document.original_filename }}</div>
              </div>
              
              <div class="mb-3" v-if="document.description">
                <div class="text-caption text-medium-emphasis">Beschreibung</div>
                <div class="text-body-2">{{ document.description }}</div>
              </div>
              
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Dateigröße</div>
                <div class="text-body-2">{{ formatFileSize(document.file_size) }}</div>
              </div>
              
              <div class="mb-3" v-if="document.language">
                <div class="text-caption text-medium-emphasis">Sprache</div>
                <div class="text-body-2">{{ document.language }}</div>
              </div>
              
              <div class="mb-3" v-if="document.page_count">
                <div class="text-caption text-medium-emphasis">Seitenzahl</div>
                <div class="text-body-2">{{ document.page_count }}</div>
              </div>
              
              <div v-if="document.expires_at">
                <div class="text-caption text-medium-emphasis">Läuft ab am</div>
                <div class="text-body-2">{{ formatDateTime(document.expires_at) }}</div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Upload Information -->
          <v-card class="mb-6">
            <v-card-title>Upload-Informationen</v-card-title>
            <v-card-text>
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Hochgeladen von</div>
                <div class="d-flex align-center mt-1">
                  <v-avatar size="24" class="mr-2">
                    <span class="text-caption">
                      {{ getInitials(document.uploader?.full_name || 'U') }}
                    </span>
                  </v-avatar>
                  <span>{{ document.uploader?.full_name || 'Unbekannt' }}</span>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Hochgeladen am</div>
                <div class="text-body-2">{{ formatDateTime(document.created_at) }}</div>
              </div>
              
              <div class="mb-3" v-if="document.updated_at !== document.created_at">
                <div class="text-caption text-medium-emphasis">Zuletzt geändert</div>
                <div class="text-body-2">{{ formatDateTime(document.updated_at) }}</div>
              </div>
              
              <div v-if="document.last_accessed_at">
                <div class="text-caption text-medium-emphasis">Letzter Zugriff</div>
                <div class="text-body-2">{{ formatDateTime(document.last_accessed_at) }}</div>
                <div class="text-caption" v-if="document.last_accessed_by_user">
                  von {{ document.last_accessed_by_user.full_name }}
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Categories -->
          <v-card class="mb-6">
            <v-card-title class="d-flex align-center">
              <v-icon start>mdi-tag-multiple</v-icon>
              Kategorien
              <v-spacer />
              <v-btn 
                color="primary" 
                variant="text"
                size="small"
                prepend-icon="mdi-pencil"
                @click="manageCategories"
              >
                Bearbeiten
              </v-btn>
            </v-card-title>
            
            <v-card-text>
              <div v-if="document.categories?.length">
                <div 
                  v-for="category in document.categories" 
                  :key="category.id"
                  class="mb-3 last:mb-0"
                >
                  <v-card variant="outlined" class="pa-3">
                    <div class="d-flex align-center">
                      <v-icon 
                        v-if="category.icon"
                        :icon="category.icon" 
                        :color="category.color"
                        size="16"
                        class="mr-2"
                      />
                      <div class="flex-grow-1">
                        <div class="text-body-2 font-weight-medium">{{ category.name }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ category.category_group?.name }}
                        </div>
                      </div>
                    </div>
                    
                    <!-- Assignment details -->
                    <div v-if="category.pivot" class="mt-2">
                      <v-chip 
                        size="x-small" 
                        :color="getAssignmentTypeColor(category.pivot.assignment_type)"
                        variant="outlined"
                      >
                        {{ getAssignmentTypeLabel(category.pivot.assignment_type) }}
                      </v-chip>
                      
                      <span v-if="category.pivot.confidence_score" class="text-caption ml-2">
                        {{ Math.round(category.pivot.confidence_score * 100) }}% Vertrauen
                      </span>
                    </div>
                  </v-card>
                </div>
              </div>
              
              <div v-else class="text-center py-4">
                <v-icon size="32" color="grey-lighten-2" class="mb-2">mdi-tag-off</v-icon>
                <p class="text-body-2 text-medium-emphasis">Keine Kategorien zugewiesen</p>
                <v-btn 
                  color="primary" 
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-plus"
                  @click="manageCategories"
                >
                  Hinzufügen
                </v-btn>
              </div>
            </v-card-text>
          </v-card>

          <!-- Activities -->
          <v-card v-if="document.activities?.length">
            <v-card-title>
              <v-icon start>mdi-history</v-icon>
              Letzte Aktivitäten
            </v-card-title>
            
            <v-card-text>
              <v-timeline density="compact">
                <v-timeline-item
                  v-for="activity in document.activities.slice(0, 5)"
                  :key="activity.id"
                  :dot-color="getActivityColor(activity.activity_level)"
                  size="small"
                >
                  <template #icon>
                    <v-icon 
                      :icon="getActivityIcon(activity.activity_type)" 
                      size="12"
                    />
                  </template>
                  
                  <div class="text-body-2">{{ activity.activity_description }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDateTime(activity.occurred_at) }}
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/core/api'
import { useNotifications } from '@/core/composables/useNotifications'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import type { Document } from '@/shared/types/document'

const route = useRoute()
const router = useRouter()
const { showSuccess, showError } = useNotifications()
const rsdStore = useRSDStore()
const api = useApi()

// State
const loading = ref(false)
const error = ref<string | null>(null)
const document = ref<Document | null>(null)
const showFullText = ref(false)
const pdfLoading = ref(true)
const imageLoading = ref(true)
const signedUrls = ref<{
  preview_url: string | null
  download_url: string | null
  supports_preview: boolean
} | null>(null)

// Computed
const breadcrumbs = computed(() => [
  { title: 'Dokumente', to: { name: 'tenant-documents' } },
  { title: document.value?.title || document.value?.original_filename || 'Dokument', disabled: true }
])

const imagePreviewUrl = computed(() => {
  if (!document.value || !signedUrls.value?.supports_preview) return ''
  if (!document.value.mime_type.startsWith('image/')) return ''
  
  return signedUrls.value.preview_url || ''
})

// Methods
onMounted(async () => {
  await loadDocument()
})

onBeforeUnmount(() => {
  // No cleanup needed for signed URLs
})

const loadDocument = async () => {
  const documentId = route.params.id
  if (!documentId) {
    error.value = 'Dokument-ID fehlt'
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await api.get(`/tenant/documents/${documentId}`)
    document.value = response.data.data
    
    // Load signed URLs for preview
    await loadSignedUrls()
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Fehler beim Laden des Dokuments'
  } finally {
    loading.value = false
  }
}

const loadSignedUrls = async () => {
  if (!document.value) return
  
  try {
    console.log('Loading signed URLs for document:', document.value.id)
    const response = await api.get(`/tenant/documents/${document.value.id}/signed-urls`)
    signedUrls.value = response.data.data
    console.log('Signed URLs loaded:', signedUrls.value)
  } catch (error: any) {
    console.error('Error loading signed URLs:', error)
    showError('Fehler beim Laden der Signed URLs')
  } finally {
    pdfLoading.value = false
  }
}


const downloadDocument = async () => {
  if (!document.value || !signedUrls.value?.download_url) return
  
  try {
    window.open(signedUrls.value.download_url, '_blank')
    showSuccess('Download gestartet')
  } catch (error) {
    showError('Download fehlgeschlagen')
  }
}

const editDocument = () => {
  if (document.value) {
    rsdStore.openEdit('document', document.value)
  }
}

const shareDocument = () => {
  if (document.value) {
    rsdStore.openEdit('documentShare', { document: document.value })
  }
}

const manageCategories = () => {
  if (document.value) {
    rsdStore.openEdit('documentCategories', { document: document.value })
  }
}

const processWithAI = async () => {
  if (!document.value) return
  
  try {
    await api.post(`/tenant/documents/${document.value.id}/process-with-ai`)
    showSuccess('AI-Verarbeitung gestartet')
    // Reload document to get updated status
    setTimeout(() => loadDocument(), 1000)
  } catch (error: any) {
    showError(error.response?.data?.message || 'Fehler bei AI-Verarbeitung')
  }
}

const deleteDocument = async () => {
  if (!document.value) return
  
  if (!confirm(`Möchten Sie das Dokument "${document.value.original_filename}" wirklich löschen?`)) {
    return
  }
  
  try {
    await api.delete(`/tenant/documents/${document.value.id}`)
    showSuccess('Dokument erfolgreich gelöscht')
    router.push({ name: 'tenant-documents' })
  } catch (error: any) {
    showError(error.response?.data?.message || 'Fehler beim Löschen')
  }
}

// Preview Methods
const onPDFLoaded = () => {
  pdfLoading.value = false
}

const onPDFError = () => {
  pdfLoading.value = false
  showError('PDF konnte nicht geladen werden')
}

const onImageLoaded = () => {
  imageLoading.value = false
}

const onImageError = () => {
  imageLoading.value = false
  showError('Bild konnte nicht geladen werden')
}

const openFullscreenPDF = () => {
  if (signedUrls.value?.preview_url) {
    window.open(signedUrls.value.preview_url, '_blank')
  }
}

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDateTime = (date: string): string => {
  return new Date(date).toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getInitials = (name: string): string => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
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

// Status utility functions
const getStatusColor = (status: string): string => {
  switch (status) {
    case 'active': return 'success'
    case 'draft': return 'warning'
    case 'archived': return 'info'
    case 'deleted': return 'error'
    default: return 'grey'
  }
}

const getStatusIcon = (status: string): string => {
  switch (status) {
    case 'active': return 'mdi-check-circle'
    case 'draft': return 'mdi-pencil'
    case 'archived': return 'mdi-archive'
    case 'deleted': return 'mdi-delete'
    default: return 'mdi-help-circle'
  }
}

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'active': return 'Aktiv'
    case 'draft': return 'Entwurf'
    case 'archived': return 'Archiviert'
    case 'deleted': return 'Gelöscht'
    default: return status
  }
}

const getAIStatusColor = (status: string): string => {
  switch (status) {
    case 'completed': return 'success'
    case 'processing': return 'info'
    case 'pending': return 'warning'
    case 'failed': return 'error'
    default: return 'grey'
  }
}

const getAIStatusIcon = (status: string): string => {
  switch (status) {
    case 'completed': return 'mdi-check'
    case 'processing': return 'mdi-cog'
    case 'pending': return 'mdi-clock'
    case 'failed': return 'mdi-alert'
    default: return 'mdi-help'
  }
}

const getAIStatusLabel = (status: string): string => {
  switch (status) {
    case 'completed': return 'Abgeschlossen'
    case 'processing': return 'Verarbeitung'
    case 'pending': return 'Ausstehend'
    case 'failed': return 'Fehler'
    default: return status
  }
}

const getVisibilityColor = (visibility: string): string => {
  switch (visibility) {
    case 'public': return 'green'
    case 'internal': return 'blue'
    case 'confidential': return 'orange'
    case 'restricted': return 'red'
    default: return 'grey'
  }
}

const getVisibilityLabel = (visibility: string): string => {
  switch (visibility) {
    case 'public': return 'Öffentlich'
    case 'internal': return 'Intern'
    case 'confidential': return 'Vertraulich'
    case 'restricted': return 'Eingeschränkt'
    default: return visibility
  }
}

const getAssignmentTypeColor = (type: string): string => {
  switch (type) {
    case 'ai_auto': return 'purple'
    case 'ai_assisted': return 'indigo'
    case 'manual': return 'blue'
    case 'rule_based': return 'green'
    case 'bulk': return 'orange'
    default: return 'grey'
  }
}

const getAssignmentTypeLabel = (type: string): string => {
  switch (type) {
    case 'ai_auto': return 'AI-automatisch'
    case 'ai_assisted': return 'AI-unterstützt'
    case 'manual': return 'Manuell'
    case 'rule_based': return 'Regel-basiert'
    case 'bulk': return 'Bulk-Zuweisung'
    default: return type
  }
}

const getActivityColor = (level: string): string => {
  switch (level) {
    case 'info': return 'blue'
    case 'warning': return 'orange'
    case 'error': return 'red'
    case 'critical': return 'red-darken-2'
    default: return 'grey'
  }
}

const getActivityIcon = (type: string): string => {
  switch (type) {
    case 'uploaded': return 'mdi-upload'
    case 'viewed': return 'mdi-eye'
    case 'downloaded': return 'mdi-download'
    case 'shared': return 'mdi-share-variant'
    case 'categorized': return 'mdi-tag'
    case 'updated': return 'mdi-pencil'
    case 'deleted': return 'mdi-delete'
    case 'restored': return 'mdi-restore'
    case 'ai_processed': return 'mdi-robot'
    default: return 'mdi-information'
  }
}
</script>

<style scoped>
.extracted-text-preview {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  background: rgba(var(--v-theme-surface-variant), 0.1);
  padding: 16px;
  border-radius: 4px;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 400px;
  overflow-y: auto;
}

.ai-metadata {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.75rem;
  background: rgba(var(--v-theme-surface-variant), 0.1);
  padding: 12px;
  border-radius: 4px;
  max-height: 300px;
  overflow-y: auto;
}

.last\:mb-0:last-child {
  margin-bottom: 0;
}

/* Document Preview Styles */
.pdf-preview-container {
  position: relative;
  height: 600px;
  min-height: 400px;
  background: #f5f5f5;
}

.pdf-viewer {
  width: 100%;
  height: 100%;
  border: none;
}

.pdf-loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 2;
}

.image-preview {
  max-width: 100%;
  max-height: 500px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.text-file-preview {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.875rem;
  line-height: 1.6;
  background: rgba(var(--v-theme-surface-variant), 0.1);
  padding: 16px;
  border-radius: 4px;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 400px;
  overflow-y: auto;
}
</style>