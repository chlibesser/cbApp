<template>
  <div class="workflows-view">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="flex-grow-1">
        <div class="d-flex align-center mb-2">
          <h1 class="text-h4 font-weight-bold">
            Workflows
          </h1>
          <v-chip
            color="primary"
            size="small"
            variant="tonal"
            class="ml-3"
          >
            {{ workflows.length }} Workflows
          </v-chip>
        </div>
        
        <p class="text-body-1 text-medium-emphasis mb-0">
          Workflow-Management und -Erstellung
        </p>
      </div>
    </div>

    <!-- Main Content -->
    <v-card elevation="2" rounded="lg" min-height="400">
      <v-card-text class="pa-0">
        <!-- Toolbar -->
        <div class="border-b pa-4">
          <div class="d-flex align-center gap-4">
            <!-- Workflow Statistics -->
            <div class="d-flex align-center gap-2">
              <v-chip
                color="success"
                variant="tonal"
                size="small"
              >
                <v-icon start size="small">mdi-sitemap</v-icon>
                {{ workflows.filter(w => w.status === 'active').length }} Aktiv
              </v-chip>
              <v-chip
                color="warning"
                variant="tonal"
                size="small"
              >
                <v-icon start size="small">mdi-file-edit</v-icon>
                {{ workflows.filter(w => w.status === 'draft').length }} Entwürfe
              </v-chip>
            </div>

            <v-spacer />

            <!-- Actions -->
            <div class="d-flex align-center gap-2">
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="createWorkflow"
              >
                Neuer Workflow
              </v-btn>
            </div>
          </div>
        </div>

        <!-- Advanced Data Table -->
        <AdvancedDataTable
          :columns="workflowColumns"
          api-endpoint="/tenant/workflows"
          @item-selected="handleItemSelected"
          @item-double-click="editWorkflow"
        >
          <template #item.status="{ item, value }">
            <v-chip 
              size="small"
              :color="getStatusColor(value)"
              variant="tonal"
            >
              {{ getStatusLabel(value) }}
            </v-chip>
          </template>

          <template #item.node_count="{ item }">
            <div class="d-flex align-center gap-1">
              <v-icon size="small" color="primary">mdi-circle</v-icon>
              <span>{{ item.metadata?.nodeCount || 0 }}</span>
            </div>
          </template>

          <template #item.edge_count="{ item }">
            <div class="d-flex align-center gap-1">
              <v-icon size="small" color="info">mdi-arrow-right</v-icon>
              <span>{{ item.metadata?.edgeCount || 0 }}</span>
            </div>
          </template>

          <template #item.created_at="{ value }">
            {{ formatDate(value) }}
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex align-center gap-1">
              <v-btn
                icon="mdi-pencil"
                variant="text"
                size="small"
                @click.stop="editWorkflow(item)"
                title="Bearbeiten"
              />
              <v-btn
                icon="mdi-play"
                variant="text"
                size="small"
                color="success"
                @click.stop="runWorkflow(item)"
                title="Ausführen"
                :disabled="item.status !== 'active'"
              />
              <v-btn
                icon="mdi-delete"
                variant="text"
                size="small"
                color="error"
                @click.stop="deleteWorkflow(item)"
                title="Löschen"
              />
            </div>
          </template>
        </AdvancedDataTable>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from '@/shared/composables/useToast'
import AdvancedDataTable from '@/shared/components/tables/AdvancedDataTable.vue'
import { workflowService, type Workflow } from '../services/workflowService'

const router = useRouter()
const toast = useToast()

// State
const workflows = ref<Workflow[]>([])
const loading = ref(false)

// ADT Column Configuration
const workflowColumns = [
  {
    key: 'name',
    label: 'Name',
    sortable: true,
    width: '200px'
  },
  {
    key: 'description',
    label: 'Beschreibung',
    sortable: false,
    width: '300px'
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true,
    width: '100px'
  },
  {
    key: 'node_count',
    label: 'Nodes',
    sortable: true,
    width: '80px'
  },
  {
    key: 'edge_count',
    label: 'Verbindungen',
    sortable: true,
    width: '120px'
  },
  {
    key: 'created_at',
    label: 'Erstellt',
    sortable: true,
    width: '120px'
  },
  {
    key: 'actions',
    label: 'Aktionen',
    sortable: false,
    width: '140px'
  }
]

// Computed for statistics based on ADT data
const workflowStats = computed(() => {
  const activeCount = workflows.value.filter(w => w.status === 'active').length
  const draftCount = workflows.value.filter(w => w.status === 'draft').length
  return { activeCount, draftCount }
})

// Methods
function createWorkflow() {
  router.push({ name: 'workflow-builder' })
}

function editWorkflow(workflow: any) {
  router.push({ name: 'workflow-builder', params: { id: workflow.id } })
}

async function runWorkflow(workflow: Workflow) {
  try {
    loading.value = true
    const result = await workflowService.executeWorkflow(workflow.id)
    toast.success(`Workflow "${workflow.name}" wird ausgeführt (ID: ${result.execution_id})`)
  } catch (error: any) {
    console.error('Fehler beim Ausführen des Workflows:', error)
    toast.error(error.response?.data?.message || 'Fehler beim Ausführen des Workflows')
  } finally {
    loading.value = false
  }
}

async function deleteWorkflow(workflow: Workflow) {
  if (confirm(`Workflow "${workflow.name}" wirklich löschen?`)) {
    try {
      loading.value = true
      await workflowService.deleteWorkflow(workflow.id)
      toast.success(`Workflow "${workflow.name}" wurde gelöscht`)
      
      // Refresh the ADT data by triggering a reload
      window.location.reload()
    } catch (error: any) {
      console.error('Fehler beim Löschen des Workflows:', error)
      toast.error(error.response?.data?.message || 'Fehler beim Löschen des Workflows')
    } finally {
      loading.value = false
    }
  }
}

function handleItemSelected(item: any) {
  console.log('Selected workflow:', item)
}

function getStatusColor(status: string) {
  switch (status) {
    case 'active': return 'success'
    case 'draft': return 'warning' 
    case 'inactive': return 'error'
    default: return 'grey'
  }
}

function getStatusLabel(status: string) {
  switch (status) {
    case 'active': return 'Aktiv'
    case 'draft': return 'Entwurf'
    case 'inactive': return 'Inaktiv' 
    default: return status
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('de-DE')
}

// Remove demo data loading - ADT handles API calls automatically
</script>

<style scoped>
.workflows-view {
  padding: 24px;
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>