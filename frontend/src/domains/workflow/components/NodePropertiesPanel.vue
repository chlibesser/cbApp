<template>
  <v-navigation-drawer
    v-model="isVisible"
    location="right"
    width="400"
    temporary
    class="node-properties-panel"
  >
    <v-toolbar density="compact" color="primary" dark>
      <v-toolbar-title class="text-subtitle-1">
        <v-icon class="mr-2">{{ getNodeIcon(selectedNode?.type) }}</v-icon>
        {{ getNodeTitle(selectedNode?.type) }} Eigenschaften
      </v-toolbar-title>
      
      <v-spacer />
      
      <v-btn
        icon="mdi-arrow-expand"
        variant="text"
        @click="expandToDialog"
        title="Erweiterte Ansicht"
        class="mr-2"
      />
      
      <v-btn
        icon="mdi-close"
        variant="text"
        @click="closePanel"
      />
    </v-toolbar>
    
    <v-container class="pa-4" v-if="selectedNode">
      <!-- Basic Properties -->
      <v-card class="mb-4" variant="outlined">
        <v-card-title class="text-h6 pb-2">Grundeinstellungen</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="nodeData.label"
            label="Name"
            prepend-icon="mdi-tag"
            variant="outlined"
            density="compact"
            @update:model-value="handleBasicFieldUpdate"
          />
          
          <v-textarea
            v-model="nodeData.description"
            label="Beschreibung"
            prepend-icon="mdi-text"
            variant="outlined"
            density="compact"
            rows="2"
            @update:model-value="handleBasicFieldUpdate"
          />
        </v-card-text>
      </v-card>
      
      <!-- Node-specific Properties -->
      <component 
        :is="getPropertiesComponent(selectedNode.type)"
        :data="nodeData"
        @update:data="handleDataUpdate"
        @update="handleDataUpdate"
      />
      
      <!-- Info -->
      <v-alert
        type="info"
        variant="tonal"
        density="compact"
        class="mb-4"
      >
        <template #prepend>
          <v-icon size="small">mdi-information</v-icon>
        </template>
        Änderungen werden automatisch übernommen. Speichern Sie den Workflow über den Hauptspeicher-Button.
      </v-alert>
      
      <!-- Actions -->
      <v-card variant="outlined">
        <v-card-text>
          <v-btn
            color="error"
            variant="outlined"
            block
            @click="deleteNode"
          >
            Node löschen
          </v-btn>
        </v-card-text>
      </v-card>
    </v-container>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useVueFlow } from '@vue-flow/core'
import type { WorkflowNode, WorkflowNodeData } from '../types'

// Import node-specific property components
import TriggerProperties from './properties/TriggerProperties.vue'
import ActionProperties from './properties/ActionProperties.vue'
import ConditionProperties from './properties/ConditionProperties.vue'
import EndProperties from './properties/EndProperties.vue'

interface Props {
  modelValue: boolean
  selectedNode?: WorkflowNode | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'nodeUpdated', node: WorkflowNode): void
  (e: 'nodeDeleted', nodeId: string): void
  (e: 'expandToDialog'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { updateNode, removeNodes } = useVueFlow()

// Local state
const nodeData = ref<WorkflowNodeData | any>({})

// Computed
const isVisible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
})

// Watch for selected node changes
watch(() => props.selectedNode, (newNode) => {
  if (newNode) {
    // Only update if the node actually changed, not just when panel reopens
    if (!nodeData.value.id || nodeData.value.id !== newNode.data.id) {
      nodeData.value = { ...newNode.data }
    }
  }
}, { immediate: true })

// Methods
function closePanel() {
  isVisible.value = false
}

function expandToDialog() {
  emit('expandToDialog')
}

function getNodeIcon(nodeType?: string): string {
  const icons = {
    trigger: 'mdi-flash',
    action: 'mdi-cog',
    condition: 'mdi-help-rhombus',
    end: 'mdi-stop-circle'
  }
  return icons[nodeType as keyof typeof icons] || 'mdi-circle'
}

function getNodeTitle(nodeType?: string): string {
  const titles = {
    trigger: 'Trigger',
    action: 'Action',
    condition: 'Condition',
    end: 'End'
  }
  return titles[nodeType as keyof typeof titles] || 'Node'
}

function getPropertiesComponent(nodeType?: string) {
  const components = {
    trigger: TriggerProperties,
    action: ActionProperties,
    condition: ConditionProperties,
    end: EndProperties
  }
  return components[nodeType as keyof typeof components] || TriggerProperties
}

async function updateNodeData() {
  if (!props.selectedNode) return
  
  // Create completely new node object 
  const updatedNode = {
    id: props.selectedNode.id,
    type: props.selectedNode.type,
    position: props.selectedNode.position,
    data: { ...nodeData.value }
  }
  
  // Skip Vue Flow updateNode - just emit to parent
  emit('nodeUpdated', updatedNode)
}

function handleDataUpdate(newData: any) {
  // Merge the new data with existing nodeData
  nodeData.value = { ...nodeData.value, ...newData }
  updateNodeData()
}

function handleBasicFieldUpdate() {
  // Trigger update when basic fields (name, description) change
  updateNodeData()
}


function deleteNode() {
  if (!props.selectedNode) return
  
  if (confirm(`Möchten Sie den Node "${props.selectedNode.data.label}" wirklich löschen?`)) {
    removeNodes([props.selectedNode.id])
    emit('nodeDeleted', props.selectedNode.id)
    closePanel()
  }
}
</script>

<style scoped>
.node-properties-panel {
  z-index: 1000;
}

:deep(.v-navigation-drawer__content) {
  display: flex;
  flex-direction: column;
}

:deep(.v-container) {
  flex: 1;
  overflow-y: auto;
}
</style>