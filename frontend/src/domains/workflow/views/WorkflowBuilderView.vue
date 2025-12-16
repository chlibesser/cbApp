<template>
  <div class="workflow-builder-view">
    <div class="d-flex align-center justify-space-between mb-4">
      <div class="d-flex align-center">
        <v-btn
          icon="mdi-arrow-left"
          variant="text"
          size="small"
          class="mr-2"
          @click="handleBack"
        />
        <h1>{{ pageTitle }}</h1>
      </div>
      <div class="d-flex align-center gap-2">
        <v-btn
          color="primary"
          prepend-icon="mdi-content-save"
          @click="saveWorkflow"
          :disabled="nodes.length === 0 || loading"
          :loading="loading"
        >
          {{ saveButtonText }}
        </v-btn>
      </div>
    </div>
    
    <!-- Vue Flow Canvas -->
    <div class="workflow-canvas">
      <VueFlow 
        v-model:nodes="nodes"
        v-model:edges="edges"
        @paneContextMenu="onPaneContextMenu"
        @connect="onConnect"
      >
        <Background />
        <MiniMap />
        <Controls />
      </VueFlow>
      
      <!-- Context Menu -->
      <div 
        v-if="contextMenu.show"
        class="context-menu"
        :style="{
          position: 'fixed',
          left: contextMenu.x + 'px',
          top: contextMenu.y + 'px',
          zIndex: 9999
        }"
        @click.stop
      >
        <v-card elevation="8" rounded="lg">
          <v-list density="compact" class="pa-0">
            <v-list-item @click="addNode('trigger')" class="context-menu-item">
              <template #prepend>
                <v-icon color="success">mdi-flash</v-icon>
              </template>
              <v-list-item-title>Trigger hinzufügen</v-list-item-title>
            </v-list-item>
            
            <v-list-item @click="addNode('action')" class="context-menu-item">
              <template #prepend>
                <v-icon color="primary">mdi-cog</v-icon>
              </template>
              <v-list-item-title>Action hinzufügen</v-list-item-title>
            </v-list-item>
            
            <v-list-item @click="addNode('condition')" class="context-menu-item">
              <template #prepend>
                <v-icon color="orange">mdi-help-rhombus</v-icon>
              </template>
              <v-list-item-title>Condition hinzufügen</v-list-item-title>
            </v-list-item>
            
            <v-list-item @click="addNode('end')" class="context-menu-item">
              <template #prepend>
                <v-icon color="error">mdi-stop-circle</v-icon>
              </template>
              <v-list-item-title>End hinzufügen</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from '@/shared/composables/useToast'
import { workflowService, type Workflow } from '../services/workflowService'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'

// Import Vue Flow CSS
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// State
const nodes = ref([])
const edges = ref([])
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  nodePosition: { x: 0, y: 0 }
})

// Workflow metadata
const currentWorkflowId = ref<string | null>(null)
const workflowName = ref<string>('')
const workflowDescription = ref<string>('')
const loading = ref(false)

// Computed
const isEditMode = computed(() => !!currentWorkflowId.value)
const pageTitle = computed(() => 
  isEditMode.value ? `Workflow bearbeiten: ${workflowName.value}` : 'Neuer Workflow'
)
const saveButtonText = computed(() => 
  isEditMode.value ? 'Änderungen speichern' : 'Workflow speichern'
)

function onPaneContextMenu(event: any) {
  console.log('Full context menu event:', event)
  console.log('Event keys:', Object.keys(event))
  
  const mouseEvent = event.event || event
  if (mouseEvent && mouseEvent.preventDefault) {
    mouseEvent.preventDefault()
  }
  
  console.log('Available properties:', {
    flowTransform: event.flowTransform,
    position: event.position,
    clientX: mouseEvent?.clientX,
    clientY: mouseEvent?.clientY,
    offsetX: mouseEvent?.offsetX,
    offsetY: mouseEvent?.offsetY
  })
  
  const nodeX = mouseEvent?.offsetX || mouseEvent?.clientX || 100
  const nodeY = mouseEvent?.offsetY || mouseEvent?.clientY || 100
  
  contextMenu.value = {
    show: true,
    x: mouseEvent?.clientX || 200,
    y: mouseEvent?.clientY || 200,
    nodePosition: { x: nodeX, y: nodeY }
  }
}

function addNode(nodeType: string) {
  const id = `${nodeType}_${Date.now()}`
  
  const nodeConfig = {
    trigger: { label: 'Trigger Node', color: '#4caf50' },
    action: { label: 'Action Node', color: '#2196f3' },
    condition: { label: 'Condition Node', color: '#ff9800' },
    end: { label: 'End Node', color: '#f44336' }
  }
  
  const config = nodeConfig[nodeType] || nodeConfig.trigger
  
  const newNode = {
    id,
    type: 'default',
    position: contextMenu.value.nodePosition,
    data: {
      label: config.label
    },
    style: {
      backgroundColor: config.color,
      color: 'white',
      border: `2px solid ${config.color}`,
      borderRadius: '8px'
    }
  }
  
  nodes.value.push(newNode)
  contextMenu.value.show = false
}

function onConnect(connection: any) {
  console.log('New connection:', connection)
  
  const newEdge = {
    id: `edge_${Date.now()}`,
    source: connection.source,
    target: connection.target,
    sourceHandle: connection.sourceHandle,
    targetHandle: connection.targetHandle
  }
  
  edges.value.push(newEdge)
}

// Load existing workflow data
async function loadWorkflow(workflowId: string) {
  try {
    loading.value = true
    const workflow = await workflowService.getWorkflow(workflowId)
    
    // Set workflow metadata
    currentWorkflowId.value = workflow.id
    workflowName.value = workflow.name
    workflowDescription.value = workflow.description || ''
    
    // Set nodes and edges
    nodes.value = workflow.nodes || []
    edges.value = workflow.edges || []
    
  } catch (error: any) {
    console.error('Error loading workflow:', error)
    toast.error(error.response?.data?.message || 'Fehler beim Laden des Workflows')
    
    // Navigate back to workflows list on error
    router.push({ name: 'workflows' })
  } finally {
    loading.value = false
  }
}

async function saveWorkflow() {
  if (nodes.value.length === 0) {
    return
  }

  try {
    loading.value = true
    
    const workflowData = {
      name: workflowName.value,
      description: workflowDescription.value,
      nodes: nodes.value,
      edges: edges.value,
      metadata: {
        nodeCount: nodes.value.length,
        edgeCount: edges.value.length
      }
    }

    let result: Workflow

    if (isEditMode.value && currentWorkflowId.value) {
      // Update existing workflow
      result = await workflowService.updateWorkflow(currentWorkflowId.value, workflowData)
      toast.success(`Workflow "${result.name}" erfolgreich aktualisiert`)
    } else {
      // Create new workflow - ask for name first
      const name = prompt('Workflow Name:')
      if (!name) {
        loading.value = false
        return
      }
      
      workflowData.name = name
      workflowData.description = `Workflow mit ${nodes.value.length} Nodes und ${edges.value.length} Verbindungen`
      
      result = await workflowService.createWorkflow(workflowData)
      toast.success(`Workflow "${result.name}" erfolgreich erstellt`)
      
      // Update state for newly created workflow
      currentWorkflowId.value = result.id
      workflowName.value = result.name
      workflowDescription.value = result.description || ''
    }
    
  } catch (error: any) {
    console.error('Error saving workflow:', error)
    toast.error(error.response?.data?.message || 'Fehler beim Speichern des Workflows')
  } finally {
    loading.value = false
  }
}

function handleBack() {
  router.push({ name: 'workflows' })
}

onMounted(async () => {
  // Context menu event listener
  document.addEventListener('click', () => {
    contextMenu.value.show = false
  })

  // Load workflow if ID exists in route
  const workflowId = route.params.id
  if (workflowId && typeof workflowId === 'string') {
    await loadWorkflow(workflowId)
  }
})
</script>

<style scoped>
.workflow-builder-view {
  padding: 24px;
}

.workflow-canvas {
  height: 600px;
  border: 1px solid #ccc;
  border-radius: 8px;
}

.context-menu {
  user-select: none;
}

.context-menu-item:hover {
  background: rgba(var(--v-theme-primary), 0.08);
}
</style>