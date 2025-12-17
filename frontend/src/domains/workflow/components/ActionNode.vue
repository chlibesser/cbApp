<template>
  <div 
    class="action-node"
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
        background: '#2196f3',
        border: '2px solid white',
        width: '12px',
        height: '12px'
      }" 
    />
    
    <div class="node-header">
      <v-icon class="node-icon" color="white" size="16">mdi-cog</v-icon>
      <span class="node-title">Action</span>
    </div>
    
    <div class="node-content">
      <div class="action-type">
        {{ data.actionType || 'Execute Action' }}
      </div>
      
      <div class="action-description" v-if="data.description">
        {{ data.description }}
      </div>
      
      <div class="action-status" v-if="data.status">
        <v-chip 
          size="x-small" 
          :color="getStatusColor(data.status)"
          variant="flat"
        >
          {{ data.status }}
        </v-chip>
      </div>
    </div>
    
    <!-- Output Handle -->
    <Handle 
      type="source" 
      position="right" 
      :style="{ 
        background: '#2196f3',
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

export interface ActionNodeData {
  label: string
  actionType?: string
  description?: string
  method?: string
  endpoint?: string
  parameters?: any
  status?: 'ready' | 'running' | 'completed' | 'failed'
}

interface Props {
  data: ActionNodeData
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

// Status color mapping
function getStatusColor(status: string): string {
  const colors = {
    ready: 'grey',
    running: 'orange',
    completed: 'success',
    failed: 'error'
  }
  return colors[status] || 'grey'
}

// Computed
const nodeId = computed(() => node.value?.id)
</script>

<style scoped>
.action-node {
  background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
  border: 2px solid #2196f3;
  border-radius: 8px;
  padding: 10px 14px;
  min-width: 140px;
  max-width: 200px;
  color: white;
  font-family: 'Roboto', sans-serif;
  box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.action-node:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
  border-color: #42a5f5;
}

.action-node.node-selected {
  border-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.3);
}

.action-node.node-dragging {
  transform: rotate(-1deg);
  opacity: 0.8;
}

.node-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
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

.action-type {
  font-weight: 500;
  margin-bottom: 4px;
}

.action-description {
  opacity: 0.9;
  font-size: 10px;
  max-height: 30px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 4px;
}

.action-status {
  display: flex;
  justify-content: flex-end;
}

/* Handle positioning */
:deep(.vue-flow__handle) {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.action-node:hover :deep(.vue-flow__handle) {
  opacity: 1;
}
</style>