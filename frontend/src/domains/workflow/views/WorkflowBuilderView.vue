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
        <div class="workflow-title">
          <h1 v-if="!editingName" @click="editingName = true" class="clickable-title">
            {{ pageTitle }}
            <v-icon size="small" class="ml-2 edit-icon">mdi-pencil</v-icon>
          </h1>
          <v-text-field
            v-if="editingName"
            v-model="workflowName"
            density="compact"
            variant="outlined"
            hide-details
            autofocus
            @blur="finishNameEdit"
            @keyup.enter="finishNameEdit"
            @keyup.escape="cancelNameEdit"
            class="name-editor"
          />
        </div>
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
        :key="vueFlowKey"
        v-model:nodes="nodes"
        v-model:edges="edges"
        :node-types="nodeTypes"
        @paneContextMenu="onPaneContextMenu"
        @connect="onConnect"
        @node-click="onNodeClick"
        @node-double-click="onNodeDoubleClick"
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
            <v-list-item @click="addTriggerNode" class="context-menu-item">
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

    <!-- Node Properties Panel -->
    <NodePropertiesPanel
      v-model="showPropertiesPanel"
      :selected-node="selectedNode"
      @nodeUpdated="handleNodeUpdated"
      @nodeDeleted="handleNodeDeleted"
    />

    <!-- Snackbar für Feedback -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="4000"
      location="bottom right"
    >
      {{ snackbarText }}
      <template v-slot:actions>
        <v-btn
          variant="text"
          @click="snackbar = false"
        >
          Schließen
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { workflowService, type Workflow } from '../services/workflowService'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'

// Import custom node components
import TriggerNode from '../components/TriggerNode.vue'
import ActionNode from '../components/ActionNode.vue'
import ConditionNode from '../components/ConditionNode.vue'
import EndNode from '../components/EndNode.vue'
import NodePropertiesPanel from '../components/NodePropertiesPanel.vue'

// Import types
import type { WorkflowNode } from '../types'

// Import Vue Flow CSS
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'

const router = useRouter()
const route = useRoute()

// Node type registry for Vue Flow
const nodeTypes = {
  trigger: TriggerNode,
  action: ActionNode,
  condition: ConditionNode,
  end: EndNode
}

// State
const nodes = ref<any[]>([])
const edges = ref<any[]>([])
const vueFlowKey = ref(0)
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  nodePosition: { x: 0, y: 0 }
})

// Properties Panel State
const showPropertiesPanel = ref(false)
const selectedNode = ref<WorkflowNode | null>(null)

// Workflow metadata
const currentWorkflowId = ref<string | null>(null)
const workflowName = ref<string>('')
const workflowDescription = ref<string>('')
const loading = ref(false)

// Snackbar state
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Name editing state
const editingName = ref(false)
const originalName = ref('')


// Computed
const isEditMode = computed(() => !!currentWorkflowId.value)
const pageTitle = computed(() => 
  isEditMode.value ? `Workflow bearbeiten: ${workflowName.value}` : 'Neuer Workflow'
)
const saveButtonText = computed(() => 
  isEditMode.value ? 'Änderungen speichern' : 'Workflow speichern'
)

// Snackbar functions
function showSuccess(message: string) {
  snackbarText.value = message
  snackbarColor.value = 'success'
  snackbar.value = true
}

function showError(message: string) {
  snackbarText.value = message
  snackbarColor.value = 'error'
  snackbar.value = true
}


function onPaneContextMenu(event: any) {
  const mouseEvent = event.event || event
  if (mouseEvent && mouseEvent.preventDefault) {
    mouseEvent.preventDefault()
  }
  
  const nodeX = mouseEvent?.offsetX || mouseEvent?.clientX || 100
  const nodeY = mouseEvent?.offsetY || mouseEvent?.clientY || 100
  
  contextMenu.value = {
    show: true,
    x: mouseEvent?.clientX || 200,
    y: mouseEvent?.clientY || 200,
    nodePosition: { x: nodeX, y: nodeY }
  }
}

function addTriggerNode() {
  const id = `trigger_${Date.now()}`
  
  const newNode: WorkflowNode = {
    id,
    type: 'trigger',
    position: contextMenu.value.nodePosition,
    data: {
      id,
      label: 'Event Trigger',
      triggerType: 'event',
      eventType: '',
      description: 'Startet bei bestimmten Events'
    }
  }
  
  nodes.value.push(newNode)
  contextMenu.value.show = false
  
  // Open properties panel for the new node
  selectedNode.value = newNode
  showPropertiesPanel.value = true
}

function addNode(nodeType: string) {
  const id = `${nodeType}_${Date.now()}`
  
  // Create node data based on type
  const nodeDataMap = {
    action: {
      id,
      label: 'Neue Action',
      actionType: 'api' as const,
      method: 'GET',
      endpoint: '',
      description: '',
      parameters: {},
      timeout: 30,
      retries: 3
    },
    condition: {
      id,
      label: 'Neue Condition',
      conditionType: 'if-else' as const,
      operator: 'equals' as const,
      leftValue: '',
      rightValue: '',
      description: ''
    },
    end: {
      id,
      label: 'Workflow Ende',
      endType: 'success' as const,
      message: 'Workflow erfolgreich beendet',
      description: ''
    }
  }
  
  const nodeData = nodeDataMap[nodeType as keyof typeof nodeDataMap] || nodeDataMap.action
  
  const newNode = {
    id,
    type: nodeType,
    position: contextMenu.value.nodePosition,
    data: nodeData
  }
  
  nodes.value.push(newNode)
  contextMenu.value.show = false
}

function onConnect(connection: any) {
  const newEdge = {
    id: `edge_${Date.now()}`,
    source: connection.source,
    target: connection.target,
    sourceHandle: connection.sourceHandle,
    targetHandle: connection.targetHandle
  }
  
  edges.value.push(newEdge)
}

// Node interaction handlers
function onNodeClick(event: any) {
  const node = event.node || event
  if (node) {
    // Always get the latest node data from the nodes array
    const currentNode = nodes.value.find(n => n.id === node.id)
    selectedNode.value = currentNode || node
  }
}

function onNodeDoubleClick(event: any) {
  const node = event.node || event
  if (node) {
    // Always get the latest node data from the nodes array
    const currentNode = nodes.value.find(n => n.id === node.id)
    selectedNode.value = currentNode || node
    showPropertiesPanel.value = true
  }
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
    originalName.value = workflow.name
    
    // Set nodes and edges
    nodes.value = workflow.nodes || []
    edges.value = workflow.edges || []
    
  } catch (error: any) {
    console.error('Error loading workflow:', error)
    showError(error.response?.data?.message || 'Fehler beim Laden des Workflows')
    
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
      result = await workflowService.updateWorkflow(currentWorkflowId.value, workflowData)
      showSuccess(`Workflow "${result.name}" erfolgreich aktualisiert`)

    } else {
      // Create new workflow - name should be set from route query or ask for it
      if (!workflowName.value) {
        const name = prompt('Workflow Name:')
        if (!name) {
          loading.value = false
          return
        }
        workflowName.value = name
        workflowData.name = name
      } else {
        workflowData.name = workflowName.value
      }
      
      if (!workflowData.description) {
        workflowData.description = workflowDescription.value || `Workflow mit ${nodes.value.length} Nodes und ${edges.value.length} Verbindungen`
      }
      
      result = await workflowService.createWorkflow(workflowData)
      showSuccess(`Workflow "${result.name}" erfolgreich erstellt`)
      
      currentWorkflowId.value = result.id
      workflowName.value = result.name
      workflowDescription.value = result.description || ''
      
      // Clear query parameters by replacing the current route
      router.replace({ 
        name: 'workflow-builder', 
        params: { id: result.id }
      })
    }
    
  } catch (error: any) {
    console.error('❌ Error saving workflow:', error)
    showError(error.response?.data?.message || 'Fehler beim Speichern des Workflows')
  } finally {

    loading.value = false
  }
}


function handleBack() {
  router.push({ name: 'workflows' })
}

// Name editing functions
function finishNameEdit() {
  editingName.value = false
  if (workflowName.value.trim() !== originalName.value) {
    // Auto-save the name change
    saveWorkflow()
  }
}

function cancelNameEdit() {
  workflowName.value = originalName.value
  editingName.value = false
}

// Node properties panel handlers
function handleNodeUpdated(updatedNode: WorkflowNode) {
  // Find and update the node in the nodes array
  const nodeIndex = nodes.value.findIndex(n => n.id === updatedNode.id)
  if (nodeIndex !== -1) {
    // Force complete replacement to trigger reactivity
    nodes.value.splice(nodeIndex, 1, updatedNode)
    
    // Update selected node
    if (selectedNode.value && selectedNode.value.id === updatedNode.id) {
      selectedNode.value = updatedNode
    }
    
    // Force Vue Flow to re-render by updating key
    vueFlowKey.value++
  }
}

function handleNodeDeleted() {
  // The deletion is already handled by onNodeDeleted
  selectedNode.value = null
  showSuccess('Node gelöscht')
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
  } else {
    // Set name from query parameters for new workflows
    const queryName = route.query.name
    const queryDescription = route.query.description
    
    if (queryName && typeof queryName === 'string') {
      workflowName.value = queryName
      originalName.value = queryName
    }
    
    if (queryDescription && typeof queryDescription === 'string') {
      workflowDescription.value = queryDescription
    }
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
  background: rgba(33, 150, 243, 0.08);
}

.workflow-title {
  flex: 1;
}

.clickable-title {
  cursor: pointer;
  transition: color 0.2s ease;
  display: flex;
  align-items: center;
}

.clickable-title:hover {
  color: rgb(33, 150, 243);
}

.edit-icon {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.clickable-title:hover .edit-icon {
  opacity: 1;
}

.name-editor {
  max-width: 400px;
}
</style>