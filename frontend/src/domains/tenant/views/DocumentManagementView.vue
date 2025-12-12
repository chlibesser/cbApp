<template>
  <div class="document-management">
    <!-- Header -->
    <div class="d-flex align-center mb-6">
      <div class="flex-grow-1">
        <h1 class="text-h4 font-weight-bold text-primary">Dokumente</h1>
        <p class="text-body-1 text-medium-emphasis mt-1">
          Verwalten Sie alle Dokumente Ihres Mandanten
          <v-chip v-if="documentCount !== null" size="small" color="primary" variant="outlined" class="ml-2">
            {{ documentCount }} Dokumente
          </v-chip>
        </p>
      </div>
      <v-btn 
        color="primary" 
        size="large"
        @click="openUploadDialog"
        prepend-icon="mdi-upload"
      >
        Dokument hochladen
      </v-btn>
    </div>

    <!-- Statistics Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" md="3">
        <v-card>
          <v-card-text class="text-center">
            <v-icon color="primary" size="32" class="mb-2">mdi-file-document</v-icon>
            <div class="text-h6 font-weight-bold">{{ statistics.total_documents || 0 }}</div>
            <div class="text-caption text-medium-emphasis">Gesamt Dokumente</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card>
          <v-card-text class="text-center">
            <v-icon color="success" size="32" class="mb-2">mdi-harddisk</v-icon>
            <div class="text-h6 font-weight-bold">{{ formatFileSize(statistics.total_size || 0) }}</div>
            <div class="text-caption text-medium-emphasis">Speicherplatz</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card>
          <v-card-text class="text-center">
            <v-icon color="info" size="32" class="mb-2">mdi-robot</v-icon>
            <div class="text-h6 font-weight-bold">{{ aiProcessedCount }}</div>
            <div class="text-caption text-medium-emphasis">AI-verarbeitet</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card>
          <v-card-text class="text-center">
            <v-icon color="warning" size="32" class="mb-2">mdi-calendar-month</v-icon>
            <div class="text-h6 font-weight-bold">{{ thisMonthCount }}</div>
            <div class="text-caption text-medium-emphasis">Diesen Monat</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Advanced Data Table -->
    <v-card>
      <AdvancedDataTable
        :columns="tableColumns"
        :api-endpoint="apiEndpoint"
        :default-sort="{ key: 'created_at', order: 'desc' }"
        @create="handleCreate"
        @item-selected="handleItemSelected"
        @bulk-action="handleBulkAction"
        ref="documentsTable"
      >
        <!-- Custom slots for specific columns -->
        <template #item.original_filename="{ item, value }">
          <div class="d-flex align-center">
            <v-icon 
              :icon="getFileIcon(item.mime_type)" 
              :color="getFileIconColor(item.mime_type)"
              size="20" 
              class="mr-2"
            />
            <div>
              <div class="font-weight-medium">{{ value }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.mime_type }}</div>
            </div>
          </div>
        </template>

        <template #item.file_size="{ value }">
          <span class="text-body-2">{{ formatFileSize(value) }}</span>
        </template>

        <template #item.status="{ item, value }">
          <v-chip 
            :color="getStatusColor(value)" 
            size="small" 
            variant="flat"
          >
            <v-icon 
              :icon="getStatusIcon(value)" 
              size="14" 
              start
            />
            {{ getStatusLabel(value) }}
          </v-chip>
        </template>

        <template #item.ai_processing_status="{ item, value }">
          <v-chip 
            :color="getAIStatusColor(value)" 
            size="small" 
            variant="outlined"
          >
            <v-icon 
              :icon="getAIStatusIcon(value)" 
              size="12" 
              start
            />
            {{ getAIStatusLabel(value) }}
          </v-chip>
        </template>

        <template #item.categories="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <v-chip
              v-for="category in item.categories.slice(0, 2)"
              :key="category.id"
              size="x-small"
              :color="category.color || 'primary'"
              variant="outlined"
            >
              <v-icon
                v-if="category.icon"
                :icon="category.icon"
                size="10"
                start
              />
              {{ category.name }}
            </v-chip>
            <v-chip
              v-if="item.categories.length > 2"
              size="x-small"
              color="grey"
              variant="outlined"
            >
              +{{ item.categories.length - 2 }}
            </v-chip>
          </div>
        </template>

        <template #item.uploader="{ item }">
          <div class="d-flex align-center">
            <v-avatar size="24" class="mr-2">
              <span class="text-caption">{{ getInitials(item.uploader?.full_name || 'U') }}</span>
            </v-avatar>
            <span class="text-body-2">{{ item.uploader?.full_name || 'Unbekannt' }}</span>
          </div>
        </template>

        <template #item.created_at="{ value }">
          <span class="text-body-2">{{ formatDate(value) }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex">
            <v-btn
              icon="mdi-download"
              variant="text"
              size="small"
              @click.stop="downloadDocument(item)"
              :disabled="item.status === 'deleted'"
            />
            <v-btn
              icon="mdi-eye"
              variant="text"
              size="small"
              @click.stop="viewDocument(item)"
            />
            <v-btn
              icon="mdi-dots-vertical"
              variant="text"
              size="small"
              @click.stop="showDocumentMenu(item, $event)"
            />
          </div>
        </template>

        <!-- Bulk action buttons -->
        <template #bulk-actions="{ selectedItems }">
          <v-btn
            variant="outlined"
            prepend-icon="mdi-tag-multiple"
            @click="bulkCategorize(selectedItems)"
          >
            Kategorisieren
          </v-btn>
          <v-btn
            variant="outlined" 
            color="purple"
            prepend-icon="mdi-robot"
            @click="bulkAiCategorize(selectedItems)"
            :loading="bulkAiProcessing"
          >
            Mit Claude kategorisieren
          </v-btn>
          <v-btn
            variant="outlined"
            color="error"
            prepend-icon="mdi-delete"
            @click="bulkDelete(selectedItems)"
          >
            Löschen
          </v-btn>
        </template>
      </AdvancedDataTable>
    </v-card>

    <!-- Right Side Drawer for Document Management -->
    <GenericRSDWrapper />

    <!-- Upload Dialog -->
    <v-dialog v-model="uploadDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-upload" class="mr-2" />
          Dokument hochladen
        </v-card-title>
        
        <v-card-text>
          <DocumentUploadForm
            @uploaded="handleDocumentUploaded"
            @cancel="uploadDialog = false"
            :loading="uploading"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Bulk Categorize Dialog -->
    <v-dialog v-model="bulkCategorizeDialog" max-width="500px">
      <v-card>
        <v-card-title>Dokumente kategorisieren</v-card-title>
        <v-card-text>
          <p class="mb-4">
            {{ selectedDocuments.length }} Dokument(e) kategorisieren:
          </p>
          
          <CategorySelector
            v-model="selectedCategoryId"
            :tenant-id="currentTenant?.id"
            label="Kategorie auswählen"
          />
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="bulkCategorizeDialog = false">Abbrechen</v-btn>
          <v-btn 
            color="primary" 
            @click="executeBulkCategorize"
            :disabled="!selectedCategoryId"
            :loading="bulkCategorizing"
          >
            Kategorisieren
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Document Context Menu -->
    <v-menu
      v-model="documentMenu.show"
      :position-x="documentMenu.x"
      :position-y="documentMenu.y"
      absolute
      offset-y
    >
      <v-list dense>
        <v-list-item @click="editDocument(documentMenu.item)">
          <v-list-item-title>
            <v-icon icon="mdi-pencil" size="16" class="mr-2" />
            Bearbeiten
          </v-list-item-title>
        </v-list-item>
        <v-list-item @click="shareDocument(documentMenu.item)">
          <v-list-item-title>
            <v-icon icon="mdi-share-variant" size="16" class="mr-2" />
            Teilen
          </v-list-item-title>
        </v-list-item>
        <v-list-item @click="manageCategories(documentMenu.item)">
          <v-list-item-title>
            <v-icon icon="mdi-tag-multiple" size="16" class="mr-2" />
            Kategorien verwalten
          </v-list-item-title>
        </v-list-item>
        <v-list-item @click="processDocumentWithAi(documentMenu.item)">
          <v-list-item-title class="text-purple">
            <v-icon icon="mdi-robot" size="16" class="mr-2" />
            Mit Claude kategorisieren
          </v-list-item-title>
        </v-list-item>
        <v-divider />
        <v-list-item 
          @click="deleteDocument(documentMenu.item)"
          :disabled="documentMenu.item?.status === 'deleted'"
        >
          <v-list-item-title class="text-error">
            <v-icon icon="mdi-delete" size="16" class="mr-2" />
            Löschen
          </v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useToast } from '@/shared/composables/useToast'
import { useApi } from '@/core/api'
import AdvancedDataTable from '@/shared/components/tables/AdvancedDataTable.vue'
import GenericRSDWrapper from '@/shared/components/GenericRSDWrapper.vue'
import DocumentUploadForm from '../components/DocumentUploadForm.vue'
import CategorySelector from '@/shared/components/form/CategorySelector.vue'
import type { TableColumn } from '@/shared/types/table'
import type { Document, DocumentStatistics } from '@/shared/types/document'

const router = useRouter()
const rsdStore = useRSDStore()
const { showSuccess, showError } = useToast()
const api = useApi()

// Table configuration
const apiEndpoint = '/tenant/documents'

const tableColumns: TableColumn[] = [
  {
    key: 'original_filename',
    label: 'Dateiname',
    type: 'text',
    sortable: true,
    searchable: true,
    minWidth: 250
  },
  {
    key: 'file_size',
    label: 'Größe',
    type: 'number',
    sortable: true,
    width: 120
  },
  {
    key: 'status',
    label: 'Status',
    type: 'enum',
    sortable: true,
    filterable: true,
    width: 120,
    options: [
      { value: 'draft', label: 'Entwurf' },
      { value: 'active', label: 'Aktiv' },
      { value: 'archived', label: 'Archiviert' },
      { value: 'deleted', label: 'Gelöscht' }
    ]
  },
  {
    key: 'ai_processing_status',
    label: 'AI Status',
    type: 'enum',
    filterable: true,
    width: 130,
    options: [
      { value: 'pending', label: 'Ausstehend' },
      { value: 'processing', label: 'Wird verarbeitet' },
      { value: 'completed', label: 'Abgeschlossen' },
      { value: 'failed', label: 'Fehlgeschlagen' }
    ]
  },
  {
    key: 'categories',
    label: 'Kategorien',
    type: 'custom',
    width: 200
  },
  {
    key: 'uploader',
    label: 'Hochgeladen von',
    type: 'text',
    sortable: true,
    width: 150
  },
  {
    key: 'created_at',
    label: 'Erstellt am',
    type: 'datetime',
    sortable: true,
    width: 150
  },
  {
    key: 'actions',
    label: 'Aktionen',
    type: 'actions',
    sortable: false,
    width: 120
  }
]

// Reactive data
const documentsTable = ref()
const statistics = ref<DocumentStatistics>({})
const uploadDialog = ref(false)
const uploading = ref(false)
const bulkCategorizeDialog = ref(false)
const bulkCategorizing = ref(false)
const bulkAiProcessing = ref(false)
const selectedDocuments = ref<Document[]>([])
const selectedCategoryId = ref<string>('')

const documentMenu = ref({
  show: false,
  x: 0,
  y: 0,
  item: null as Document | null
})

// Computed properties
const documentCount = computed(() => statistics.value.total_documents || null)
const aiProcessedCount = computed(() => {
  const aiStats = statistics.value.ai_processing_status || {}
  return aiStats.completed || 0
})
const thisMonthCount = computed(() => {
  const monthStats = statistics.value.documents_by_month || {}
  const currentMonth = new Date().toISOString().slice(0, 7)
  return monthStats[currentMonth] || 0
})

const currentTenant = computed(() => {
  // TODO: Get from auth store
  return { id: 'current-tenant-id' }
})

// Methods
const loadStatistics = async () => {
  try {
    const response = await api.get('/tenant/documents/statistics')
    statistics.value = response.data.data
  } catch (error) {
    console.error('Fehler beim Laden der Statistiken:', error)
  }
}

const refreshData = () => {
  documentsTable.value?.refresh()
  loadStatistics()
}

const openUploadDialog = () => {
  uploadDialog.value = true
}

const handleDocumentUploaded = (document: Document) => {
  uploadDialog.value = false
  showSuccess('Dokument erfolgreich hochgeladen')
  refreshData()
}

const handleCreate = () => {
  openUploadDialog()
}

const handleItemSelected = (item: Document) => {
  viewDocument(item)
}

const handleBulkAction = (action: string, items: Document[]) => {
  selectedDocuments.value = items
  
  switch (action) {
    case 'categorize':
      bulkCategorize(items)
      break
    case 'delete':
      bulkDelete(items)
      break
  }
}

const viewDocument = (document: Document) => {
  router.push({ 
    name: 'tenant-document-detail', 
    params: { id: document.id } 
  })
}

const editDocument = (document: Document) => {
  rsdStore.openEdit('document', document)
}

const downloadDocument = async (document: Document) => {
  try {
    const blob = await api.downloadFile(`/tenant/documents/${document.id}/download`)
    
    const url = window.URL.createObjectURL(blob)
    const a = globalThis.document.createElement('a')
    a.href = url
    a.download = document.original_filename
    globalThis.document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    globalThis.document.body.removeChild(a)
    
    showSuccess('Download gestartet')
  } catch (error) {
    showError('Download fehlgeschlagen')
  }
}

const deleteDocument = async (document: Document) => {
  if (!confirm(`Möchten Sie das Dokument "${document.original_filename}" wirklich löschen?`)) {
    return
  }
  
  try {
    await api.delete(`/tenant/documents/${document.id}`)
    showSuccess('Dokument erfolgreich gelöscht')
    refreshData()
  } catch (error) {
    showError('Löschen fehlgeschlagen')
  }
}

const shareDocument = (document: Document) => {
  rsdStore.openEdit('documentShare', { document })
}

const manageCategories = (document: Document) => {
  rsdStore.openEdit('documentCategories', { document })
}

const showDocumentMenu = (document: Document, event: MouseEvent) => {
  documentMenu.value = {
    show: true,
    x: event.clientX,
    y: event.clientY,
    item: document
  }
}

const bulkCategorize = (documents: Document[]) => {
  selectedDocuments.value = documents
  bulkCategorizeDialog.value = true
}

const executeBulkCategorize = async () => {
  if (!selectedCategoryId.value) return
  
  bulkCategorizing.value = true
  
  try {
    const response = await api.post('/tenant/documents/bulk-categorize', {
      document_ids: selectedDocuments.value.map(d => d.id),
      category_id: selectedCategoryId.value
    })
    
    showSuccess(response.data.message)
    bulkCategorizeDialog.value = false
    refreshData()
  } catch (error) {
    showError('Kategorisierung fehlgeschlagen')
  } finally {
    bulkCategorizing.value = false
  }
}

const bulkAiCategorize = async (documents: Document[]) => {
  if (documents.length === 0) {
    showError('Bitte wählen Sie Dokumente aus')
    return
  }
  
  if (!confirm(`Sollen ${documents.length} Dokument(e) mit Claude kategorisiert werden?`)) {
    return
  }
  
  bulkAiProcessing.value = true
  
  try {
    const response = await api.post('/tenant/documents/bulk-ai-categorize', {
      document_ids: documents.map(d => d.id)
    })
    
    showSuccess(response.data.message)
    refreshData()
  } catch (error: any) {
    const message = error.response?.data?.message || 'AI-Kategorisierung fehlgeschlagen'
    showError(message)
  } finally {
    bulkAiProcessing.value = false
  }
}

const processDocumentWithAi = async (document: Document) => {
  try {
    const response = await api.post(`/tenant/documents/${document.id}/process-ai`)
    showSuccess(response.data.message)
    refreshData()
  } catch (error: any) {
    const message = error.response?.data?.message || 'AI-Verarbeitung fehlgeschlagen'
    showError(message)
  }
}

const bulkDelete = async (documents: Document[]) => {
  if (!confirm(`Möchten Sie ${documents.length} Dokument(e) wirklich löschen?`)) {
    return
  }
  
  // TODO: Implement bulk delete API
  showError('Bulk-Löschen noch nicht implementiert')
}

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date: string): string => {
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

// Lifecycle
onMounted(() => {
  loadStatistics()
})
</script>

<style scoped>
.document-management {
  padding: 24px;
}
</style>