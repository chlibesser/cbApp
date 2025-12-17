<template>
  <div 
    class="condition-node"
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
        background: '#ff9800',
        border: '2px solid white',
        width: '12px',
        height: '12px'
      }" 
    />
    
    <div class="node-header">
      <v-icon class="node-icon" color="white" size="14">mdi-help-rhombus</v-icon>
      <span class="node-title">Condition</span>
    </div>
    
    <div class="node-content">
      <div class="condition-type">
        {{ data.conditionType || 'If/Else' }}
      </div>
      
      <div class="condition-expression" v-if="data.expression">
        {{ data.expression }}
      </div>
    </div>
    
    <!-- True Output Handle -->
    <Handle 
      id="true"
      type="source" 
      position="right" 
      :style="{ 
        background: '#4caf50',
        border: '2px solid white',
        width: '12px',
        height: '12px',
        top: '30%'
      }" 
    />
    
    <!-- False Output Handle -->
    <Handle 
      id="false"
      type="source" 
      position="right" 
      :style="{ 
        background: '#f44336',
        border: '2px solid white',
        width: '12px',
        height: '12px',
        top: '70%'
      }" 
    />
    
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import { Handle, useNode } from '@vue-flow/core'

export interface ConditionNodeData {
  label: string
  conditionType?: string
  expression?: string
  operator?: 'equals' | 'greater' | 'less' | 'contains' | 'exists'
  leftValue?: any
  rightValue?: any
  description?: string
}

interface Props {
  data: ConditionNodeData
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
.condition-node {
  background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
  border: 2px solid #ff9800;
  border-radius: 12px;
  padding: 8px 12px;
  min-width: 120px;
  max-width: 180px;
  color: white;
  font-family: 'Roboto', sans-serif;
  box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.condition-node:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
  border-color: #ffb74d;
}

.condition-node.node-selected {
  border-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.3);
}

.condition-node.node-dragging {
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

.condition-type {
  font-weight: 500;
  margin-bottom: 2px;
}

.condition-expression {
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
  border-radius: 50%;
}

.condition-node:hover :deep(.vue-flow__handle) {
  opacity: 1;
}
</style>