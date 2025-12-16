<template>
  <div class="workflows-view">
    <h1>Workflows</h1>
    
    <!-- Vue Flow Canvas -->
    <div class="workflow-canvas">
      <VueFlow 
        v-model:nodes="nodes"
        @paneContextMenu="onPaneContextMenu"
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
                <v-icon color="success">mdi-flash-circle</v-icon>
              </template>
              <v-list-item-title>Trigger hinzufügen</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'

// Import Vue Flow CSS
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'

// State
const nodes = ref([])
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  nodePosition: { x: 0, y: 0 }
})

function onPaneContextMenu(event: any) {
  console.log('Full context menu event:', event)
  console.log('Event keys:', Object.keys(event))
  
  // Versuche verschiedene Event-Strukturen
  const mouseEvent = event.event || event
  if (mouseEvent && mouseEvent.preventDefault) {
    mouseEvent.preventDefault()
  }
  
  // Debug alle verfügbaren Properties
  console.log('Available properties:', {
    flowTransform: event.flowTransform,
    position: event.position,
    clientX: mouseEvent?.clientX,
    clientY: mouseEvent?.clientY,
    offsetX: mouseEvent?.offsetX,
    offsetY: mouseEvent?.offsetY
  })
  
  // Verwende Maus-Koordinaten als Canvas-Position
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
  
  const newNode = {
    id,
    type: 'default',
    position: contextMenu.value.nodePosition,
    data: {
      label: 'Trigger Node'
    }
  }
  
  nodes.value.push(newNode)
  contextMenu.value.show = false
}

// Close context menu on outside click
onMounted(() => {
  document.addEventListener('click', () => {
    contextMenu.value.show = false
  })
})
</script>

<style scoped>
.workflows-view {
  padding: 24px;
}

.workflow-canvas {
  height: 600px;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-top: 16px;
}

.context-menu {
  user-select: none;
}

.context-menu-item:hover {
  background: rgba(var(--v-theme-primary), 0.08);
}
</style>