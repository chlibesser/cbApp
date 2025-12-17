<template>
  <div class="condition-properties">
    <v-card class="mb-4" variant="outlined">
      <v-card-title class="text-h6 pb-2">Condition-Konfiguration</v-card-title>
      <v-card-text>
        <!-- Condition Type -->
        <v-select
          v-model="localData.conditionType"
          :items="conditionTypes"
          label="Condition-Typ"
          prepend-icon="mdi-help-rhombus"
          variant="outlined"
          density="compact"
          @update:model-value="emitUpdate"
        />
        
        <!-- Simple If/Else Configuration -->
        <template v-if="localData.conditionType === 'if-else'">
          <v-text-field
            v-model="localData.leftValue"
            label="Linker Wert"
            prepend-icon="mdi-numeric-1-box"
            variant="outlined"
            density="compact"
            placeholder="z.B. document.size, user.role"
            @update:model-value="emitUpdate"
          />
          
          <v-select
            v-model="localData.operator"
            :items="operators"
            label="Operator"
            prepend-icon="mdi-compare"
            variant="outlined"
            density="compact"
            @update:model-value="emitUpdate"
          />
          
          <v-text-field
            v-model="localData.rightValue"
            label="Rechter Wert"
            prepend-icon="mdi-numeric-2-box"
            variant="outlined"
            density="compact"
            placeholder="z.B. 1000, admin"
            @update:model-value="emitUpdate"
          />
          
          <v-switch
            v-model="localData.caseSensitive"
            label="Groß-/Kleinschreibung beachten"
            color="primary"
            inset
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Complex Expression -->
        <template v-if="localData.conditionType === 'switch'">
          <v-textarea
            v-model="localData.expression"
            label="Switch Expression"
            prepend-icon="mdi-code-braces"
            variant="outlined"
            density="compact"
            rows="3"
            placeholder="z.B. document.type"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Loop Configuration -->
        <template v-if="localData.conditionType === 'loop'">
          <v-text-field
            v-model="localData.expression"
            label="Schleifenbedingung"
            prepend-icon="mdi-repeat"
            variant="outlined"
            density="compact"
            placeholder="z.B. counter < 10"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Exists Check -->
        <template v-if="localData.conditionType === 'exists'">
          <v-text-field
            v-model="localData.leftValue"
            label="Zu prüfender Pfad"
            prepend-icon="mdi-file-search"
            variant="outlined"
            density="compact"
            placeholder="z.B. data.user.email, file.path"
            @update:model-value="emitUpdate"
          />
        </template>
        
        <!-- Compare Configuration -->
        <template v-if="localData.conditionType === 'compare'">
          <v-textarea
            v-model="localData.expression"
            label="Vergleichsausdruck"
            prepend-icon="mdi-function"
            variant="outlined"
            density="compact"
            rows="2"
            placeholder="z.B. (a > b) && (c < d)"
            @update:model-value="emitUpdate"
          />
        </template>
      </v-card-text>
    </v-card>
    
    <!-- Output Configuration -->
    <v-card variant="outlined">
      <v-card-title class="text-h6 pb-2">Ausgabe-Konfiguration</v-card-title>
      <v-card-text>
        <div class="d-flex gap-2 mb-3">
          <v-icon color="success">mdi-check-circle</v-icon>
          <span class="font-weight-medium">True-Pfad</span>
        </div>
        
        <div class="d-flex gap-2 mb-3">
          <v-icon color="error">mdi-close-circle</v-icon>
          <span class="font-weight-medium">False-Pfad</span>
        </div>
        
        <v-alert
          type="info"
          variant="tonal"
          density="compact"
          class="mt-4"
        >
          <template #prepend>
            <v-icon>mdi-information</v-icon>
          </template>
          Verbinden Sie die entsprechenden Ausgänge mit den gewünschten nachfolgenden Nodes.
        </v-alert>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { ConditionNodeData } from '../../types'

interface Props {
  data: ConditionNodeData
}

interface Emits {
  (e: 'update', data: ConditionNodeData): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const localData = ref<ConditionNodeData>({ ...props.data })

const conditionTypes = [
  { title: 'Wenn/Dann (If/Else)', value: 'if-else' },
  { title: 'Switch/Case', value: 'switch' },
  { title: 'Schleife', value: 'loop' },
  { title: 'Existenz prüfen', value: 'exists' },
  { title: 'Vergleich', value: 'compare' }
]

const operators = [
  { title: 'Gleich (=)', value: 'equals' },
  { title: 'Größer als (>)', value: 'greater' },
  { title: 'Kleiner als (<)', value: 'less' },
  { title: 'Enthält', value: 'contains' },
  { title: 'Existiert', value: 'exists' },
  { title: 'Existiert nicht', value: 'not_exists' }
]

// Watch for external changes
watch(() => props.data, (newData) => {
  localData.value = { ...newData }
}, { deep: true })

// Emit changes
function emitUpdate() {
  emit('update', { ...localData.value })
}
</script>

<style scoped>
/* Keine zusätzlichen Styles erforderlich */
</style>