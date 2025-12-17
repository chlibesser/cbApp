<template>
  <v-dialog
    v-model="isVisible"
    max-width="1800"
    persistent
    class="node-properties-dialog"
  >
    <v-card>
      <!-- Header -->
      <v-card-title class="d-flex align-center bg-primary text-white">
        <v-icon class="mr-3" color="white">{{ getNodeIcon(selectedNode?.type) }}</v-icon>
        <span class="text-h6">{{ getNodeTitle(selectedNode?.type) }} Properties</span>
        
        <v-spacer />
        
        <v-btn
          icon="mdi-arrow-collapse"
          variant="text"
          @click="collapseToPanel"
          title="Zurück zur Seitenleiste"
          class="mr-2 text-white"
        />
        
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeDialog"
          class="text-white"
        />
      </v-card-title>

      <!-- Content -->
      <v-card-text class="pa-6" v-if="selectedNode">
        <v-container fluid>
          <v-row>
            <!-- Left Column: Basic + Type-specific -->
            <v-col cols="12" lg="3">
              <!-- Basic Properties -->
              <v-card class="mb-4" variant="outlined">
                <v-card-title class="text-h6 pb-2">
                  <v-icon start>mdi-cog</v-icon>
                  Grundeinstellungen
                </v-card-title>
                <v-card-text>
                  <v-text-field
                    v-model="nodeData.label"
                    label="Name"
                    prepend-icon="mdi-tag"
                    variant="outlined"
                    density="comfortable"
                    @update:model-value="handleBasicFieldUpdate"
                  />
                  
                  <v-textarea
                    v-model="nodeData.description"
                    label="Beschreibung"
                    prepend-icon="mdi-text"
                    variant="outlined"
                    density="comfortable"
                    rows="3"
                    @update:model-value="handleBasicFieldUpdate"
                  />
                </v-card-text>
              </v-card>

              <!-- Node Type Specific (basic only for actions) -->
              <v-card variant="outlined" class="mb-4" v-if="selectedNode.type !== 'action'">
                <v-card-title class="text-h6 pb-2">
                  <v-icon start>{{ getNodeIcon(selectedNode.type) }}</v-icon>
                  {{ getNodeTitle(selectedNode.type) }} Konfiguration
                </v-card-title>
                <v-card-text>
                  <component 
                    :is="getPropertiesComponent(selectedNode.type)"
                    :data="nodeData"
                    :compact="true"
                    @update:data="handleDataUpdate"
                    @update="handleDataUpdate"
                  />
                </v-card-text>
              </v-card>

              <!-- Action Type Selection only -->
              <v-card variant="outlined" class="mb-4" v-if="selectedNode.type === 'action'">
                <v-card-title class="text-h6 pb-2">
                  <v-icon start>mdi-cog</v-icon>
                  Action-Typ
                </v-card-title>
                <v-card-text>
                  <v-select
                    v-model="nodeData.actionType"
                    :items="actionTypes"
                    label="Action-Typ"
                    prepend-icon="mdi-cog"
                    variant="outlined"
                    density="comfortable"
                    @update:model-value="handleActionTypeChange"
                  />
                </v-card-text>
              </v-card>

              <!-- Actions -->
              <v-card variant="outlined">
                <v-card-title class="text-h6 pb-2">
                  <v-icon start>mdi-wrench</v-icon>
                  Aktionen
                </v-card-title>
                <v-card-text>
                  <v-btn
                    color="success"
                    variant="tonal"
                    block
                    class="mb-3"
                    prepend-icon="mdi-check"
                    @click="saveAndClose"
                  >
                    Speichern & Schließen
                  </v-btn>
                  
                  <v-btn
                    color="error"
                    variant="outlined"
                    block
                    prepend-icon="mdi-delete"
                    @click="deleteNode"
                  >
                    Node löschen
                  </v-btn>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Middle + Right Columns: Extended Properties (for API nodes) -->
            <v-col cols="12" lg="9" v-if="selectedNode.type === 'action'">
              <ActionPropertiesExtended
                :data="nodeData"
                @update:data="handleDataUpdate"
                @update="handleDataUpdate"
              />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useVueFlow } from '@vue-flow/core'
import type { WorkflowNode, WorkflowNodeData } from '../types'

// Import components
import TriggerProperties from './properties/TriggerProperties.vue'
import ActionProperties from './properties/ActionProperties.vue'
import ConditionProperties from './properties/ConditionProperties.vue'
import EndProperties from './properties/EndProperties.vue'
import ActionPropertiesExtended from './properties/ActionPropertiesExtended.vue'

interface Props {
  modelValue: boolean
  selectedNode?: WorkflowNode | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'nodeUpdated', node: WorkflowNode): void
  (e: 'nodeDeleted', nodeId: string): void
  (e: 'collapseToPanel'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { updateNode, removeNodes } = useVueFlow()

// Local state
const nodeData = ref<WorkflowNodeData | any>({})

// Action types for the select dropdown
const actionTypes = [
  { title: 'API-Aufruf', value: 'api' },
  { title: 'E-Mail senden', value: 'email' },
  { title: 'Datenbank-Operation', value: 'database' },
  { title: 'Datei-Operation', value: 'file' },
  { title: 'Benachrichtigung', value: 'notification' },
  { title: 'Benutzerdefiniert', value: 'custom' }
]

// Computed
const isVisible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
})

// Watch for selectedNode changes
watch(() => props.selectedNode, (newNode) => {
  if (newNode) {
    nodeData.value = { ...newNode.data }
  }
}, { immediate: true })

// Methods
function closeDialog() {
  isVisible.value = false
}

function collapseToPanel() {
  emit('collapseToPanel')
}

function saveAndClose() {
  updateNodeData()
  closeDialog()
}

function getNodeIcon(nodeType?: string): string {
  const icons = {
    trigger: 'mdi-flash',
    action: 'mdi-cog',
    condition: 'mdi-code-braces',
    end: 'mdi-stop-circle'
  }
  return icons[nodeType as keyof typeof icons] || 'mdi-help-circle'
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
  
  // Emit to parent
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

function handleActionTypeChange() {
  // Update node data when action type changes
  updateNodeData()
}

function deleteNode() {
  if (!props.selectedNode) return
  
  if (confirm(`Möchten Sie den Node "${props.selectedNode.data.label}" wirklich löschen?`)) {
    removeNodes([props.selectedNode.id])
    emit('nodeDeleted', props.selectedNode.id)
    closeDialog()
  }
}
</script>

<style scoped>
.node-properties-dialog {
  z-index: 2000;
}

:deep(.v-card) {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

:deep(.v-card-text) {
  flex: 1;
  overflow-y: auto;
}
</style>