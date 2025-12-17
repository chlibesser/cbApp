<template>
  <div 
    class="trigger-node"
    :class="{ 
      'node-selected': selected,
      'node-dragging': dragging 
    }"
    @mouseenter="onHover"
    @mouseleave="onLeave"
  >
    <div class="node-header">
      <v-icon class="node-icon" color="white" size="16">mdi-flash</v-icon>
      <span class="node-title">{{ getTriggerTitle() }}</span>
    </div>
    
    <div class="node-content">
      <div class="trigger-type">
        {{ props.data.label || 'Neuer Trigger' }}
      </div>
      
      <div class="trigger-description" v-if="props.data.description">
        {{ props.data.description }}
      </div>
    </div>
    
    <!-- Output Handle -->
    <Handle 
      type="source" 
      position="right" 
      :style="{ 
        background: '#4caf50',
        border: '2px solid white',
        width: '12px',
        height: '12px'
      }" 
    />
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import { Handle, useNode } from '@vue-flow/core'

export interface TriggerNodeData {
  label: string
  triggerType?: string
  description?: string
  eventType?: string
  schedule?: string
  conditions?: any[]
}

interface Props {
  data: TriggerNodeData
  selected?: boolean
  dragging?: boolean
}

const props = defineProps<Props>()

// Vue Flow node utilities
const { node } = useNode()

// Event handlers
function onHover() {
  // Add hover effects or show more details
}

function onLeave() {
  // Remove hover effects
}

// Computed
const nodeId = computed(() => node.value?.id)

// Methods
function getTriggerTitle() {
  const triggerTypeMap = {
    'event': 'Event',
    'schedule': 'Zeitplan',
    'webhook': 'Webhook',
    'manual': 'Manuell',
    'file': 'Datei'
  }
  
  return triggerTypeMap[props.data.triggerType as keyof typeof triggerTypeMap] || 'Trigger'
}
</script>

<style scoped>
.trigger-node {
  background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
  border: 2px solid #4caf50;
  border-radius: 12px;
  padding: 8px 12px;
  min-width: 120px;
  max-width: 180px;
  color: white;
  font-family: 'Roboto', sans-serif;
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.trigger-node:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
  border-color: #66bb6a;
}

.trigger-node.node-selected {
  border-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.3);
}

.trigger-node.node-dragging {
  transform: rotate(2deg);
  opacity: 0.8;
}

.node-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
}

.node-icon {
  flex-shrink: 0;
}

.node-title {
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.node-content {
  font-size: 11px;
  line-height: 1.3;
}

.trigger-type {
  font-weight: 500;
  margin-bottom: 2px;
}

.trigger-description {
  opacity: 0.9;
  font-size: 10px;
  max-height: 24px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Handle positioning */
:deep(.vue-flow__handle) {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.trigger-node:hover :deep(.vue-flow__handle) {
  opacity: 1;
}
</style>