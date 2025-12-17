<template>
  <div class="trigger-properties">
    <v-card class="mb-4" variant="outlined">
      <v-card-title class="text-h6 pb-2">Trigger-Konfiguration</v-card-title>
      <v-card-text>
        <!-- Trigger Type -->
        <v-select
          v-model="localData.triggerType"
          :items="triggerTypes"
          label="Trigger-Typ"
          prepend-icon="mdi-flash"
          variant="outlined"
          density="compact"
          @update:model-value="emitUpdate"
        />
        
        <!-- Event Type (for event triggers) -->
        <v-text-field
          v-if="localData.triggerType === 'event'"
          v-model="localData.eventType"
          label="Event-Typ"
          prepend-icon="mdi-calendar-clock"
          variant="outlined"
          density="compact"
          placeholder="z.B. document.uploaded, user.created"
          @update:model-value="emitUpdate"
        />
        
        <!-- Schedule (for scheduled triggers) -->
        <v-text-field
          v-if="localData.triggerType === 'schedule'"
          v-model="localData.schedule"
          label="Schedule (Cron)"
          prepend-icon="mdi-timer"
          variant="outlined"
          density="compact"
          placeholder="z.B. 0 9 * * 1-5 (werktags 9 Uhr)"
          @update:model-value="emitUpdate"
        />
        
        <!-- Webhook URL (for webhook triggers) -->
        <v-text-field
          v-if="localData.triggerType === 'webhook'"
          v-model="localData.webhookUrl"
          label="Webhook URL"
          prepend-icon="mdi-webhook"
          variant="outlined"
          density="compact"
          placeholder="https://api.example.com/webhook"
          @update:model-value="emitUpdate"
        />
        
        <!-- File Pattern (for file triggers) -->
        <v-text-field
          v-if="localData.triggerType === 'file'"
          v-model="localData.filePattern"
          label="Dateimuster"
          prepend-icon="mdi-file-search"
          variant="outlined"
          density="compact"
          placeholder="z.B. *.pdf, invoice_*.xlsx"
          @update:model-value="emitUpdate"
        />
      </v-card-text>
    </v-card>
    
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { TriggerNodeData } from '../../types'

interface Props {
  data: TriggerNodeData
}

interface Emits {
  (e: 'update:data', data: TriggerNodeData): void
  (e: 'update', data: TriggerNodeData): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const localData = ref<TriggerNodeData>({ ...props.data })

const triggerTypes = [
  { title: 'Event-basiert', value: 'event' },
  { title: 'Zeitgesteuert', value: 'schedule' },
  { title: 'Webhook', value: 'webhook' },
  { title: 'Manuell', value: 'manual' },
  { title: 'Datei-Upload', value: 'file' }
]

// Watch for external changes
watch(() => props.data, (newData) => {
  localData.value = { ...newData }
}, { deep: true })

// Emit changes
function emitUpdate() {
  const updatedData = { ...localData.value }
  emit('update:data', updatedData)
  emit('update', updatedData)
}

</script>

<style scoped>
/* Keine zusätzlichen Styles erforderlich */
</style>