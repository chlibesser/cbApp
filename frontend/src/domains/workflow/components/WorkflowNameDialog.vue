<template>
  <v-dialog 
    v-model="isVisible" 
    max-width="500px" 
    persistent
  >
    <v-card>
      <v-card-title class="text-h5 pb-2">
        <v-icon class="mr-2" color="primary">mdi-plus-circle</v-icon>
        Neuen Workflow erstellen
      </v-card-title>
      
      <v-card-text>
        <p class="text-body-1 mb-4">
          Geben Sie einen Namen für Ihren neuen Workflow ein:
        </p>
        
        <v-text-field
          v-model="workflowName"
          label="Workflow Name"
          prepend-icon="mdi-sitemap"
          variant="outlined"
          density="comfortable"
          autofocus
          :error-messages="errorMessage"
          @keyup.enter="handleCreate"
          @input="errorMessage = ''"
        />
        
        <v-text-field
          v-model="workflowDescription"
          label="Beschreibung (optional)"
          prepend-icon="mdi-text"
          variant="outlined"
          density="comfortable"
          @keyup.enter="handleCreate"
        />
      </v-card-text>
      
      <v-card-actions>
        <v-spacer />
        <v-btn
          variant="text"
          @click="handleCancel"
        >
          Abbrechen
        </v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          :disabled="!workflowName.trim()"
          :loading="loading"
          @click="handleCreate"
        >
          Workflow erstellen
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface Props {
  modelValue: boolean
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'create', data: { name: string; description?: string }): void
  (e: 'cancel'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const workflowName = ref('')
const workflowDescription = ref('')
const errorMessage = ref('')
const loading = ref(false)

// Computed
const isVisible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
})

// Watch for dialog opening to reset form
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    // Reset form when dialog opens
    workflowName.value = ''
    workflowDescription.value = ''
    errorMessage.value = ''
    loading.value = false
  }
})

// Methods
function handleCreate() {
  const name = workflowName.value.trim()
  
  if (!name) {
    errorMessage.value = 'Bitte geben Sie einen Workflow-Namen ein'
    return
  }
  
  if (name.length < 3) {
    errorMessage.value = 'Der Name muss mindestens 3 Zeichen lang sein'
    return
  }
  
  if (name.length > 100) {
    errorMessage.value = 'Der Name darf maximal 100 Zeichen lang sein'
    return
  }
  
  loading.value = true
  
  emit('create', {
    name,
    description: workflowDescription.value.trim() || undefined
  })
  
  // loading.value wird von parent component zurückgesetzt
}

function handleCancel() {
  emit('cancel')
  isVisible.value = false
}
</script>

<style scoped>
/* Keine zusätzlichen Styles erforderlich */
</style>