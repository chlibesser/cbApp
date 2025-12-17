<template>
  <div 
    class="end-node"
    :class="{ 
      'node-selected': selected,
      'node-dragging': dragging 
    }"
    @mouseenter="onHover"
    @mouseleave="onLeave"
  >
    <!-- Input Handle -->
    <Handle 
      type="target" 
      position="left" 
      :style="{ 
        background: '#f44336',
        border: '2px solid white',
        width: '12px',
        height: '12px'
      }" 
    />
    
    <div class="node-content">
      <v-icon class="node-icon" color="white" size="20">mdi-stop-circle</v-icon>
      <div class="node-title">End</div>
      
      <div class="end-type" v-if="data.endType">
        {{ data.endType }}
      </div>
      
      <div class="end-message" v-if="data.message">
        {{ data.message }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import { Handle, useNode } from '@vue-flow/core'

export interface EndNodeData {
  label: string
  endType?: 'success' | 'failure' | 'stop' | 'terminate'
  message?: string
  returnValue?: any
  cleanup?: boolean
}

interface Props {
  data: EndNodeData
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
</script>

<style scoped>
.end-node {
  background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
  border: 2px solid #f44336;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  color: white;
  font-family: 'Roboto', sans-serif;
  box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.end-node:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
  border-color: #ef5350;
}

.end-node.node-selected {
  border-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.3);
}

.end-node.node-dragging {
  transform: scale(0.95);
  opacity: 0.8;
}

.node-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  text-align: center;
}

.node-icon {
  flex-shrink: 0;
  margin-bottom: 2px;
}

.node-title {
  font-weight: 600;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.end-type {
  font-size: 8px;
  font-weight: 500;
  opacity: 0.9;
  text-transform: capitalize;
}

.end-message {
  font-size: 7px;
  opacity: 0.8;
  max-width: 60px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Handle positioning */
:deep(.vue-flow__handle) {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.end-node:hover :deep(.vue-flow__handle) {
  opacity: 1;
}
</style>